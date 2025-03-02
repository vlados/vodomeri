<?php

namespace Database\Seeders;

use App\Models\Apartment;
use App\Models\Invitation;
use Illuminate\Database\Seeder;

class ApartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create regular apartments (АП1-АП17)
        $this->createRegularApartments();
        
        // Create ateliers (AT1-AT3)
        $this->createAteliers();
        
        // Create stores/shops (МАГ1-МАГ2)
        $this->createStores();
    }
    
    /**
     * Create regular apartments
     */
    private function createRegularApartments(): void
    {
        // Create apartments with single owner
        Apartment::factory()
            ->count(8)
            ->create()
            ->each(function ($apartment) {
                $this->processApartmentEmails($apartment);
            });
            
        // Create apartments with multiple owners
        Apartment::factory()
            ->count(9)
            ->multipleOwners(2)
            ->create()
            ->each(function ($apartment) {
                $this->processApartmentEmails($apartment);
            });
    }
    
    /**
     * Create atelier apartments
     */
    private function createAteliers(): void
    {
        Apartment::factory()
            ->count(1)
            ->atelier()
            ->create();
    }
    
    /**
     * Create store/shop apartments
     */
    private function createStores(): void
    {
        // Create a store with single owner
        Apartment::factory()
            ->storeApartment()
            ->create();
            
        // Create a store with multiple owners
        Apartment::factory()
            ->storeApartment()
            ->multipleOwners(3)
            ->create()
            ->each(function ($apartment) {
                $this->processApartmentEmails($apartment);
            });
    }
    
    /**
     * Process apartment emails and create invitations
     */
    private function processApartmentEmails(Apartment $apartment): void
    {
        // Process and split multiple emails
        if (! empty($apartment->email)) {
            $emails = array_map('trim', explode(',', $apartment->email));
            
            // Update apartment with first email
            $apartment->update(['email' => $emails[0]]);
            
            // Create invitations for all emails
            foreach ($emails as $email) {
                $this->createInvitationForEmail($apartment, $email);
            }
        }
    }

    /**
     * Create invitation for an email address
     */
    private function createInvitationForEmail(Apartment $apartment, string $email): void
    {
        // Skip empty emails or invalid format
        if (empty($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        // Create invitation with default expiration (7 days from now)
        Invitation::create([
            'apartment_id' => $apartment->id,
            'email' => $email,
            'expires_at' => now()->addDays(7),
        ]);
    }
}
