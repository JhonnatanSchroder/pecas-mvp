<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
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

        $contact = Contact::factory()->for($company)->create(['name' => 'Maria Silva', 'whatsapp_number' => '5511999999999']);
        $conversation = Conversation::factory()->for($company)->for($contact)->create();
        Message::factory()->for($company)->for($conversation)->create(['body' => 'Olá, preciso de uma tela para iPhone 15.']);
        Message::factory()->for($company)->for($conversation)->create(['body' => 'Olá! Vou verificar para você.', 'direction' => 'outbound', 'sender' => 'bot']);
    }
}
