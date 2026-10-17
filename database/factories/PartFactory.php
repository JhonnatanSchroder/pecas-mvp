<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Part;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Part> */
class PartFactory extends Factory
{
    protected $model = Part::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->words(3, true),
            'phone_brand' => fake()->randomElement(['Apple', 'Samsung', 'Motorola']),
            'phone_model' => fake()->bothify('Model ##'),
            'sku' => fake()->unique()->bothify('SKU-####'),
            'price_cents' => fake()->numberBetween(1000, 100000),
            'stock_quantity' => fake()->numberBetween(0, 100),
            'aliases' => [fake()->word()],
            'is_active' => true,
        ];
    }
}
