<?php

namespace Database\Factories;

use App\Models\Apartment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Apartment>
 */
class ApartmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        static $number = 1;
        
        return [
            'number' => 'АП' . $number++,
            'floor' => $this->faker->numberBetween(1, 7),
            'owner_name' => $this->faker->name,
            'email' => $this->faker->optional(0.7)->email,
            'phone' => $this->faker->optional(0.6)->phoneNumber,
            'notes' => $this->faker->optional(0.3)->sentence(3),
        ];
    }

    /**
     * Configure the model factory to create a store/shop apartment.
     */
    public function storeApartment(): static
    {
        static $storeNumber = 1;
        
        return $this->state(fn (array $attributes) => [
            'number' => 'МАГ' . $storeNumber . ' (' . $this->faker->company . ')',
            'floor' => 0,
            'notes' => 'Магазин ' . $storeNumber++,
        ]);
    }

    /**
     * Configure the model factory to create an atelier apartment.
     */
    public function atelier(): static
    {
        static $atelierNumber = 1;
        
        return $this->state(fn (array $attributes) => [
            'number' => 'AT' . $atelierNumber,
            'floor' => 0,
            'notes' => 'Ателие ' . $atelierNumber++,
        ]);
    }

    /**
     * Configure the model factory to have multiple owners.
     */
    public function multipleOwners(int $count = 2): static
    {
        return $this->state(function (array $attributes) use ($count) {
            $names = collect(range(1, $count))
                ->map(fn () => $this->faker->name)
                ->implode(', ');
            
            $emails = null;
            if (rand(0, 100) > 30) {
                $emails = collect(range(1, $count))
                    ->map(fn () => $this->faker->email)
                    ->implode(', ');
            }
            
            $phones = null;
            if (rand(0, 100) > 40) {
                $phones = collect(range(1, $count))
                    ->map(fn () => $this->faker->phoneNumber)
                    ->implode(', ');
            }
            
            return [
                'owner_name' => $names,
                'email' => $emails,
                'phone' => $phones,
            ];
        });
    }
}
