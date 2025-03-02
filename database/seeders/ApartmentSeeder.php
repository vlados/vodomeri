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
        $apartments = [
            ['number' => 'AT1', 'floor' => 0, 'owner_name' => 'Виктор Кузманов', 'email' => null, 'phone' => null, 'notes' => 'Ателие 1'],
            ['number' => 'АП1', 'floor' => 1, 'owner_name' => 'Виктор Кузманов', 'email' => null, 'phone' => null, 'notes' => null],
            ['number' => 'АП2', 'floor' => 1, 'owner_name' => 'Петьо Петров', 'email' => 'petiopetrov@abv.bg', 'phone' => null, 'notes' => null],
            ['number' => 'АП3', 'floor' => 2, 'owner_name' => 'Николай Балтаджиев, Цветелина Балтаджиева', 'email' => 'nikolaybaltadzhiev@gmail.com', 'phone' => '0898652575, 0894624062', 'notes' => 'не'],
            ['number' => 'АП4', 'floor' => 2, 'owner_name' => 'Милена Славчева, Мартин Жечев', 'email' => 'milena.zhecheva@yahoo.com', 'phone' => '0877335499, 0888263323', 'notes' => 'не'],
            ['number' => 'АП5', 'floor' => 3, 'owner_name' => 'Петьо Петров', 'email' => null, 'phone' => null, 'notes' => null],
            ['number' => 'АП6', 'floor' => 3, 'owner_name' => 'Мила Ефтимова, Ефтим Ефтимов', 'email' => 'mila.taneva@gmail.com', 'phone' => '0883302804, 0899319022', 'notes' => 'не'],
            ['number' => 'АП7', 'floor' => 4, 'owner_name' => 'Кремена Георгиева', 'email' => 'nikolaeva.kremena@gmail.com', 'phone' => '0888665432', 'notes' => 'не'],
            ['number' => 'АП8', 'floor' => 4, 'owner_name' => 'Милена Славчева, Мартин Жечев', 'email' => 'martinzhechev@gmail.com', 'phone' => '0877335499, 0888263323', 'notes' => 'да'],
            ['number' => 'АП9', 'floor' => 5, 'owner_name' => 'Гаврил Гаврилов', 'email' => null, 'phone' => null, 'notes' => null],
            ['number' => 'АП10', 'floor' => 5, 'owner_name' => 'Росина Жечева', 'email' => 'rossina_r@abv.bg', 'phone' => '0878992603', 'notes' => 'да'],
            ['number' => 'АП11', 'floor' => 6, 'owner_name' => 'Росина Жечева', 'email' => 'rossina_r@abv.bg', 'phone' => '0878992603', 'notes' => 'не'],
            ['number' => 'АП12', 'floor' => 6, 'owner_name' => 'Петьо Петров', 'email' => null, 'phone' => null, 'notes' => null],
            ['number' => 'АП13', 'floor' => 7, 'owner_name' => 'Петьо Петров', 'email' => null, 'phone' => null, 'notes' => null],
            ['number' => 'АП14', 'floor' => 7, 'owner_name' => 'Благой Николов', 'email' => 'nikolovblagoy@gmail.com', 'phone' => '0888720060', 'notes' => 'не'],
            ['number' => 'АП15', 'floor' => 7, 'owner_name' => 'Ясена Стилиянова', 'email' => 'yassy.stil@gmail.com', 'phone' => '0889370571', 'notes' => 'не'],
            ['number' => 'АП16', 'floor' => 6, 'owner_name' => 'Владислав Стоицов', 'email' => 'vlados.01@gmail.com', 'phone' => '0876540555', 'notes' => null],
            ['number' => 'АП17', 'floor' => 6, 'owner_name' => 'Борис Михайлов, Мария Михайлова', 'email' => 'Bsmihaylov@gmail.com, Mimo4e@gmail.com', 'phone' => '0885677611, 0886851100', 'notes' => null],
            ['number' => 'МАГ1 (FloorDecor)', 'floor' => 0, 'owner_name' => 'Виктор Кузманов', 'email' => null, 'phone' => null, 'notes' => 'Магазин 1'],
            ['number' => 'МАГ2 (Берьозка)', 'floor' => 0, 'owner_name' => 'Росина Жечева, Милена Славчева, Мартин Жечев', 'email' => null, 'phone' => '0878992603, 0877335499, 0888263323', 'notes' => 'Магазин 2, да'],
        ];

        foreach ($apartments as $apartmentData) {
            // Process and split multiple emails
            $emails = null;
            if (! empty($apartmentData['email'])) {
                $emails = array_map('trim', explode(',', $apartmentData['email']));
                $apartmentData['email'] = $emails[0]; // Use the first email for the apartment record
            }

            // Create the apartment
            $apartment = Apartment::create($apartmentData);

            // Create invitations for emails if present
            if (! empty($emails)) {
                foreach ($emails as $email) {
                    $this->createInvitationForEmail($apartment, $email);
                }
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
