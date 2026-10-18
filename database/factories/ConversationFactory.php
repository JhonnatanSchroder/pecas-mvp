<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Conversation;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Conversation> */
class ConversationFactory extends Factory
{
    public function definition(): array
    {
        return ['company_id' => Company::factory(), 'contact_id' => Contact::factory(), 'status' => 'open', 'automation_state' => 'automated', 'human_paused_until' => null, 'handled_by' => null, 'ai_summary' => null];
    }
}
