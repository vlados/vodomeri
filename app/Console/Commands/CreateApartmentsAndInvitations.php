<?php

namespace App\Console\Commands;

use App\Models\Apartment;
use App\Models\Invitation;
use App\Models\WaterMeter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CreateApartmentsAndInvitations extends Command
{
    protected $signature = 'app:create-apartments-and-invitations
                           {--apartments=* : List of apartment numbers to create (e.g. --apartments=1 --apartments=2)}
                           {--file= : Path to CSV file with apartment data}
                           {--skip-emails : Skip sending invitation emails}
                           {--force : Force creation even in production}';

    protected $description = 'Create apartments and send invitations';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Check if we're in production and not forcing
        if (app()->environment('production') && ! $this->option('force')) {
            $this->error('This command is not recommended for production use.');
            $this->info('If you want to run it anyway, use the --force option.');

            return Command::FAILURE;
        }

        // Get apartments from file or command line
        $apartmentsData = $this->getApartmentsData();

        if (empty($apartmentsData)) {
            $this->error('No apartment data provided.');

            return Command::FAILURE;
        }

        $this->info('Creating '.count($apartmentsData).' apartments...');

        DB::beginTransaction();

        try {
            foreach ($apartmentsData as $apartmentData) {
                $this->createApartmentWithMetersAndInvitation($apartmentData);
            }

            DB::commit();
            $this->info('All apartments, water meters, and invitations created successfully!');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('An error occurred: '.$e->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * Get apartments data from file or command line
     */
    private function getApartmentsData(): array
    {
        // If file is provided, parse it
        if ($filePath = $this->option('file')) {
            return $this->parseFile($filePath);
        }

        // Otherwise use command line arguments
        $apartmentNumbers = $this->option('apartments');

        if (empty($apartmentNumbers)) {
            // Interactive mode - ask for apartments
            return $this->collectApartmentsInteractively();
        }

        // Create basic data for each apartment number
        $apartmentsData = [];
        foreach ($apartmentNumbers as $number) {
            $apartmentsData[] = [
                'number' => $number,
                'floor' => floor(($number - 1) / 4) + 1, // Rough estimate: 4 apartments per floor
                'email' => null,
                'owner_name' => "Owner of Apartment $number",
                'phone' => null,
                'notes' => 'Created via command line',
                'meters' => 2, // Default 2 meters
                'invitation' => false, // No invitation by default
            ];
        }

        return $apartmentsData;
    }

    /**
     * Parse CSV or JSON file with apartment data
     */
    private function parseFile(string $filePath): array
    {
        if (! file_exists($filePath)) {
            $this->error("File not found: $filePath");

            return [];
        }

        $extension = pathinfo($filePath, PATHINFO_EXTENSION);

        if ($extension === 'csv') {
            return $this->parseCsvFile($filePath);
        } elseif ($extension === 'json') {
            return $this->parseJsonFile($filePath);
        } else {
            $this->error("Unsupported file format: $extension");

            return [];
        }
    }

    /**
     * Parse CSV file with apartment data
     */
    private function parseCsvFile(string $filePath): array
    {
        $header = null;
        $data = [];

        if (($handle = fopen($filePath, 'r')) !== false) {
            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                if ($header === null) {
                    $header = $row;

                    continue;
                }

                $data[] = array_combine($header, $row);
            }
            fclose($handle);
        }

        return $data;
    }

    /**
     * Parse JSON file with apartment data
     */
    private function parseJsonFile(string $filePath): array
    {
        $content = file_get_contents($filePath);

        return json_decode($content, true) ?? [];
    }

    /**
     * Collect apartments data in interactive mode
     */
    private function collectApartmentsInteractively(): array
    {
        $apartments = [];
        $this->info('Enter apartment details (leave blank to finish):');

        $count = 1;
        while (true) {
            $this->info("\nApartment #$count");
            $number = $this->ask('Apartment number');

            if (empty($number)) {
                break;
            }

            $floor = $this->ask('Floor', ceil($number / 4));
            $ownerName = $this->ask('Owner name');
            $email = $this->ask('Email (for invitation)');
            $phone = $this->ask('Phone number');
            $notes = $this->ask('Notes');
            $meterCount = (int) $this->ask('Number of water meters', 2);
            $sendInvitation = $this->confirm('Send invitation email?', ! empty($email));

            $apartments[] = [
                'number' => $number,
                'floor' => $floor,
                'owner_name' => $ownerName,
                'email' => $email,
                'phone' => $phone,
                'notes' => $notes,
                'meters' => $meterCount,
                'invitation' => $sendInvitation,
            ];

            $count++;
        }

        return $apartments;
    }

    /**
     * Create an apartment with water meters and invitation
     */
    private function createApartmentWithMetersAndInvitation(array $data): void
    {
        // Extract meters and invitation data
        $meterCount = $data['meters'] ?? 2;
        $sendInvitation = $data['invitation'] ?? false;

        // Remove non-apartment fields
        unset($data['meters'], $data['invitation']);

        // Create apartment
        $apartment = Apartment::firstOrCreate(
            ['number' => $data['number']],
            $data
        );

        $this->info("Apartment {$apartment->number} ".
            ($apartment->wasRecentlyCreated ? 'created' : 'already exists'));

        // Create water meters if needed
        $currentMeters = $apartment->waterMeters()->count();
        if ($currentMeters < $meterCount) {
            for ($i = $currentMeters + 1; $i <= $meterCount; $i++) {
                $location = $i === 1 ? 'Kitchen' : ($i === 2 ? 'Bathroom' : "Location $i");

                WaterMeter::create([
                    'apartment_id' => $apartment->id,
                    'serial_number' => 'M'.$apartment->number.'-'.$i,
                    'location' => $location,
                    'type' => $i % 2 === 1 ? 'hot' : 'cold',
                    'initial_reading' => 0,
                ]);
            }

            $this->info('Created '.($meterCount - $currentMeters).' water meters');
        }

        // Create and send invitation if requested
        if ($sendInvitation && ! empty($data['email'])) {
            // Check if there is already an active invitation
            $existingInvitation = Invitation::where('apartment_id', $apartment->id)
                ->where('email', $data['email'])
                ->where('used_at', null)
                ->where('expires_at', '>', now())
                ->first();

            if ($existingInvitation) {
                $this->info("Invitation for {$data['email']} already exists");
            } else {
                $invitation = Invitation::create([
                    'apartment_id' => $apartment->id,
                    'email' => $data['email'],
                    'expires_at' => now()->addDays(7),
                ]);

                $this->info("Invitation created for {$data['email']}");

                // Send invitation email unless --skip-emails is set
                if (! $this->option('skip-emails')) {
                    $invitation->sendInvitationEmail();
                    $this->info("Invitation email sent to {$data['email']}");
                }
            }
        }
    }
}
