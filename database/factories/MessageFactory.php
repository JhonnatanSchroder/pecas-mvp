<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Message> */
class MessageFactory extends Factory
{
    public function definition(): array
    {
        return ['company_id' => Company::factory(), 'conversation_id' => Conversation::factory(), 'body' => fake()->sentence(), 'direction' => 'inbound', 'sender' => 'customer', 'provider_message_id' => null];
    }
}
