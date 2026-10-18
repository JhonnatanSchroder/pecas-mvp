<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\InventoryMovement;
use App\Models\Part;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<InventoryMovement> */
class InventoryMovementFactory extends Factory
{
    protected $model = InventoryMovement::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'part_id' => Part::factory(),
            'user_id' => User::factory(),
            'type' => 'adjustment',
            'quantity_delta' => 1,
            'balance_after' => 1,
            'reason' => fake()->sentence(),
        ];
    }
}
