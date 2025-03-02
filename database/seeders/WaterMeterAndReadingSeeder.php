<?php

namespace Database\Seeders;

use App\Models\Apartment;
use App\Models\Reading;
use App\Models\User;
use App\Models\WaterMeter;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class WaterMeterAndReadingSeeder extends Seeder
{
    // Store monthly cold water consumption for all apartments
    private $monthlyApartmentConsumption = [];
    // Water meter types
    const METER_TYPES = ['hot', 'cold', 'central'];
    
    // Possible locations for water meters
    const LOCATIONS = [
        'Кухня',
        'Баня',
        'Тоалетна',
        'Входно фоайе',
        'Килер',
        null
    ];
    
    // Serial number prefixes based on type
    const SERIAL_PREFIXES = [
        'hot' => ['HT', 'TW', 'HTW'],
        'cold' => ['CW', 'CLD', 'WM'],
        'central' => ['MN', 'BLD', 'CTR'],
    ];
    
    // Consumption ranges in cubic meters per month (min, max)
    const CONSUMPTION_RANGES = [
        'hot' => [0.5, 2.0],
        'cold' => [1.0, 3.5],
        'central' => [50.0, 90.0], // Building-wide consumption
    ];
    
    // Water loss percentage range (min, max)
    const WATER_LOSS_PERCENTAGE = [5, 15]; // 5-15% water loss
    
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all apartments
        $apartments = Apartment::all();
        
        // Get or create a test admin user for readings
        $adminUser = User::where('email', 'admin@example.com')->first();
        if (!$adminUser) {
            $adminUser = User::factory()->create([
                'name' => 'Admin User',
                'email' => 'admin@example.com',
            ]);
            $adminUser->assignRole('admin');
        }
        
        // Generate meters and readings for each apartment first
        foreach ($apartments as $apartment) {
            $this->generateMetersForApartment($apartment, $adminUser);
        }
        
        // Create a central building water meter with water loss
        // Do this AFTER generating apartment meters so we can add water loss
        $this->createCentralWaterMeter($adminUser);
        
        $this->command->info('Generated water meters and readings with realistic water loss');
    }
    
    /**
     * Create a central water meter for the building and generate readings
     */
    private function createCentralWaterMeter(User $user): void
    {
        // Generate a unique serial number for the central meter
        $prefix = self::SERIAL_PREFIXES['central'][array_rand(self::SERIAL_PREFIXES['central'])];
        $serialNumber = $prefix . rand(100000, 999999);
        
        // Set installation date to 5 years ago
        $installationDate = Carbon::now()->subYears(5);
        
        // Initial reading for the central meter (higher value)
        $initialReading = rand(1000, 5000);
        
        // Create the central water meter
        $waterMeter = new WaterMeter();
        $waterMeter->apartment_id = null; // Central meter is not tied to an apartment
        $waterMeter->serial_number = $serialNumber;
        $waterMeter->type = 'central';
        $waterMeter->location = 'Входно фоайе - Главен водомер на сградата';
        $waterMeter->installation_date = $installationDate;
        $waterMeter->initial_reading = $initialReading;
        $waterMeter->save();
        
        // Generate monthly readings for the past 2 years for better historical data
        $this->generateCentralReadings($waterMeter, $user, $initialReading);
    }
    
    /**
     * Generate monthly readings for the central water meter
     */
    private function generateCentralReadings(WaterMeter $waterMeter, User $user, float $initialValue): void
    {
        // Start from 24 months ago (2 years of data)
        $startDate = Carbon::now()->subMonths(24)->startOfMonth();
        $currentDate = Carbon::now()->startOfMonth();
        
        // Initial reading value
        $currentValue = $initialValue;
        
        // Generate a reading for each month
        while ($startDate <= $currentDate) {
            $monthKey = $startDate->format('Y-m');
            
            // Get the total cold water consumption from all apartments for this month
            $apartmentConsumption = $this->monthlyApartmentConsumption[$monthKey] ?? 0;
            
            // Add water loss - random percentage between min and max values
            $lossPercentage = rand(
                self::WATER_LOSS_PERCENTAGE[0] * 10, 
                self::WATER_LOSS_PERCENTAGE[1] * 10
            ) / 1000; // Convert to decimal (e.g., 7.5% -> 0.075)
            
            // Calculate water loss amount
            $waterLoss = $apartmentConsumption * $lossPercentage;
            
            // Ensure some water loss even if no apartment consumption
            if ($apartmentConsumption === 0 || $waterLoss < 0.5) {
                $waterLoss = rand(50, 150) / 100; // Random 0.5-1.5 m³
            }
            
            // The central meter should show apartment consumption plus water loss
            $consumption = $apartmentConsumption + $waterLoss;
            
            // For older readings, add more randomness to water loss (1-10 m³)
            $monthsAgo = $currentDate->diffInMonths($startDate);
            if ($monthsAgo > 12) {
                $extraRandomness = rand(100, 1000) / 100; // 1-10 m³
                $consumption += $extraRandomness;
            }
            
            // Add consumption to current value
            $currentValue += $consumption;
            
            // Reading date is typically early in the month for central meters (1-5)
            $readingDate = Carbon::create(
                $startDate->year,
                $startDate->month,
                rand(1, 5),
                rand(8, 12), // Morning hours 8 AM to 12 PM
                rand(0, 59)  // Random minute
            );
            
            // Create the reading
            $reading = new Reading();
            $reading->water_meter_id = $waterMeter->id;
            $reading->user_id = $user->id;
            $reading->reading_date = $readingDate;
            $reading->value = round($currentValue, 3);
            
            // Add water loss info to the notes
            $lossPercentFormatted = round($lossPercentage * 100, 1);
            $reading->notes = "Отчет на главен водомер за " . $readingDate->format('F Y') . 
                              ". Загуби: {$lossPercentFormatted}% (примерно " . round($waterLoss, 2) . " m³)";
            
            $reading->save();
            
            // Move to next month
            $startDate->addMonth();
        }
    }
    
    /**
     * Generate water meters for an apartment
     */
    private function generateMetersForApartment(Apartment $apartment, User $user): void
    {
        // Determine number of hot and cold water meters (1-3 of each type)
        $hotWaterMeters = rand(1, 2);  // Most apartments have 1-2 hot water meters
        $coldWaterMeters = rand(1, 3); // Most apartments have 1-3 cold water meters
        
        // Generate hot water meters
        for ($i = 0; $i < $hotWaterMeters; $i++) {
            $this->createWaterMeterWithReadings($apartment, 'hot', $user, $i);
        }
        
        // Generate cold water meters
        for ($i = 0; $i < $coldWaterMeters; $i++) {
            $this->createWaterMeterWithReadings($apartment, 'cold', $user, $i);
        }
    }
    
    /**
     * Create a water meter and its readings
     */
    private function createWaterMeterWithReadings(Apartment $apartment, string $type, User $user, int $index): void
    {
        // Get a random location for this meter
        $location = self::LOCATIONS[array_rand(self::LOCATIONS)];
        
        // Generate a unique serial number
        $prefix = self::SERIAL_PREFIXES[$type][array_rand(self::SERIAL_PREFIXES[$type])];
        $serialNumber = $prefix . rand(100000, 999999);
        
        // Set installation date to between 1-5 years ago
        $installationDate = Carbon::now()->subDays(rand(365, 365 * 5));
        
        // Initial reading between 0 and 100
        $initialReading = rand(0, 100);
        
        // Create the water meter
        $waterMeter = new WaterMeter();
        $waterMeter->apartment_id = $apartment->id;
        $waterMeter->serial_number = $serialNumber;
        $waterMeter->type = $type;
        $waterMeter->location = $location ? ($type === 'hot' ? "Топла вода - $location" : "Студена вода - $location") : null;
        $waterMeter->installation_date = $installationDate;
        $waterMeter->initial_reading = $initialReading;
        // Status field has been removed in a migration, so don't set it
        $waterMeter->save();
        
        // Generate monthly readings for the past year
        $this->generateReadings($waterMeter, $user, $initialReading);
    }
    
    /**
     * Generate monthly readings for a water meter
     */
    private function generateReadings(WaterMeter $waterMeter, User $user, float $initialValue): void
    {
        // Start from 24 months ago (same as central meter)
        $startDate = Carbon::now()->subMonths(24)->startOfMonth();
        $currentDate = Carbon::now()->startOfMonth();
        
        // Initial reading value
        $currentValue = $initialValue;
        
        // Generate a reading for each month
        while ($startDate <= $currentDate) {
            $monthKey = $startDate->format('Y-m');
            $apartmentId = $waterMeter->apartment_id;
            
            // Randomly skip some readings to simulate incomplete data
            // For apartments with odd floor numbers, 40% chance of missing a reading including recent months
            // For apartments with even floor numbers, 20% chance of missing readings, but not in the latest month
            $monthsAgo = $currentDate->diffInMonths($startDate);
            $isLastMonth = $monthsAgo === 0;
            $apartmentFloor = $waterMeter->apartment->floor ?? 0;
            
            // Add more variation to the data
            // Hot water meters have a higher chance of missing readings
            // Cold water meters on odd floors have higher chance of missing
            // Some meters have perfect records
            if ($waterMeter->type === 'hot' && $waterMeter->id % 3 === 0) {
                $skipReading = rand(1, 100) <= 60; // 60% chance of missing for some hot meters
            } else if ($waterMeter->type === 'hot') {
                $skipReading = rand(1, 100) <= 30; // 30% chance of missing for other hot meters
            } else if ($apartmentFloor % 2 === 1) { // Odd floor cold water
                $skipReading = rand(1, 100) <= 25; // 25% chance of missing for cold meters on odd floors
            } else if ($waterMeter->id % 5 === 0) {
                $skipReading = false; // Some meters have perfect records (every 5th ID)
            } else { 
                $skipReading = rand(1, 100) <= 20 && !$isLastMonth; // Regular pattern
            }
            
            if (!$skipReading) {
                // Random consumption based on meter type
                $consumption = $this->getRandomConsumption($waterMeter->type);
                
                // Add consumption to current value
                $currentValue += $consumption;
                
                // Reading date is random day in the month (1-28)
                $readingDate = Carbon::create(
                    $startDate->year,
                    $startDate->month,
                    rand(1, 28),
                    rand(8, 20), // Random hour between 8 AM and 8 PM
                    rand(0, 59)  // Random minute
                );
                
                // Create the reading with approved status
                $reading = new Reading();
                $reading->water_meter_id = $waterMeter->id;
                $reading->user_id = $user->id;
                $reading->reading_date = $readingDate;
                $reading->value = round($currentValue, 3);
                $reading->notes = $this->getRandomNote($readingDate);
                $reading->save();
                
                // Track cold water consumption for each apartment by month
                if ($waterMeter->type === 'cold' && $apartmentId) {
                    if (!isset($this->monthlyApartmentConsumption[$monthKey])) {
                        $this->monthlyApartmentConsumption[$monthKey] = 0;
                    }
                    
                    $this->monthlyApartmentConsumption[$monthKey] += $consumption;
                }
            } else {
                // If we skip this reading, the next reading needs to account for the missing month
                // We still generate consumption, but don't create a reading record
                $consumption = $this->getRandomConsumption($waterMeter->type);
                $currentValue += $consumption;
                
                // Still track cold water consumption even for skipped readings
                if ($waterMeter->type === 'cold' && $apartmentId) {
                    if (!isset($this->monthlyApartmentConsumption[$monthKey])) {
                        $this->monthlyApartmentConsumption[$monthKey] = 0;
                    }
                    
                    $this->monthlyApartmentConsumption[$monthKey] += $consumption;
                }
            }
            
            // Move to next month
            $startDate->addMonth();
        }
    }
    
    /**
     * Get random consumption based on meter type
     */
    private function getRandomConsumption(string $type): float
    {
        $range = self::CONSUMPTION_RANGES[$type];
        
        // Sometimes add a variation to make it look more realistic
        if (rand(1, 10) === 1) {
            // 10% chance of unusual consumption (higher or lower)
            $factor = rand(0, 1) ? rand(150, 250) / 100 : rand(20, 80) / 100;
        } else {
            // Normal variation
            $factor = rand(80, 120) / 100;
        }
        
        return round(rand($range[0] * 100, $range[1] * 100) / 100 * $factor, 3);
    }
    
    /**
     * Get a random note for a reading (most readings won't have notes)
     */
    private function getRandomNote(Carbon $date): ?string
    {
        // Only 15% of readings have notes
        if (rand(1, 100) > 15) {
            return null;
        }
        
        $notes = [
            'Нормален отчет',
            'Проверено и потвърдено',
            'Проверка на водомера',
            'Малко трудно се чете',
            'Отчетено присъствено',
            'Разлика с предходен месец',
            'Уредът е в изправност',
            'Извършена визуална проверка',
            'Показанието е потвърдено от собственика',
            'Отчетено от управителя',
            'Лесно се открива цифрата',
        ];
        
        // Add holiday-specific notes
        $month = (int)$date->format('m');
        $day = (int)$date->format('d');
        
        if ($month === 12 && $day > 20) {
            $notes[] = 'Отчет преди Коледа';
            $notes[] = 'Отчет за празниците';
        } elseif ($month === 1 && $day < 15) {
            $notes[] = 'Първи отчет за годината';
            $notes[] = 'Честита нова година';
        } elseif ($month === 3 && $day > 1 && $day < 8) {
            $notes[] = 'Отчет за Баба Марта';
        } elseif ($month === 5 && $day > 1 && $day < 10) {
            $notes[] = 'Отчет около Гергьовден';
        } elseif ($month === 7 || $month === 8) {
            $notes[] = 'Летен период, вероятно по-висока консумация';
        }
        
        return $notes[array_rand($notes)];
    }
}
