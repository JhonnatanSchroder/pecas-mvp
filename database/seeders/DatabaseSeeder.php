<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Part;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $company = Company::firstOrCreate(['name' => 'Example Parts Supplier']);

        User::factory()->for($company)->admin()->create([
            'name' => 'Local Admin',
            'email' => 'admin@example.test',
        ]);

        Part::factory()->for($company)->count(5)->create();
    }
}
