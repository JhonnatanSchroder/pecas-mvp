<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Contact> */
class ContactFactory extends Factory
{
    public function definition(): array
    {
        return ['company_id' => Company::factory(), 'name' => fake()->name(), 'whatsapp_number' => fake()->unique()->numerify('55119########')];
    }
}
