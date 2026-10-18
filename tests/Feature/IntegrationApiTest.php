<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Part;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class IntegrationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_or_invalid_integration_token_is_rejected(): void
    {
        $this->postJson('/api/integrations/whatsapp/inbound', $this->inboundPayload())->assertUnauthorized();
        $this->withHeader('X-Integration-Token', 'invalid')->postJson('/api/integrations/whatsapp/inbound', $this->inboundPayload())->assertUnauthorized();
    }

    public function test_inbound_event_is_idempotent_and_isolated_to_token_company(): void
    {
        [$company, $headers] = $this->integrationCompany();
        $payload = $this->inboundPayload();
        $first = $this->withHeaders($headers)->postJson('/api/integrations/whatsapp/inbound', $payload)->assertCreated()->json();
        $second = $this->withHeaders($headers)->postJson('/api/integrations/whatsapp/inbound', $payload)->assertOk()->json();

        $this->assertSame($first['conversation_id'], $second['conversation_id']);
        $this->assertDatabaseCount('contacts', 1);
        $this->assertDatabaseCount('conversations', 1);
        $this->assertDatabaseCount('messages', 1);
        $this->assertDatabaseHas('messages', ['company_id' => $company->id, 'provider_message_id' => 'provider-1', 'sender' => 'customer']);
    }

    public function test_context_and_mutations_do_not_leak_another_company_conversation(): void
    {
        [, $headers] = $this->integrationCompany();
        $other = Company::factory()->create();
        $contact = Contact::factory()->for($other)->create();
        $conversation = Conversation::factory()->for($other)->for($contact)->create();

        $this->withHeaders($headers)->getJson("/api/integrations/conversations/{$conversation->id}/context")->assertNotFound();
        $this->withHeaders($headers)->postJson("/api/integrations/conversations/{$conversation->id}/handoff")->assertNotFound();
        $this->withHeaders($headers)->postJson("/api/integrations/conversations/{$conversation->id}/summary", ['ai_summary' => 'x'])->assertNotFound();
    }

    public function test_context_returns_only_twelve_messages_in_chronological_order(): void
    {
        [$company, $headers] = $this->integrationCompany();
        $contact = Contact::factory()->for($company)->create();
        $conversation = Conversation::factory()->for($company)->for($contact)->create(['ai_summary' => 'Context summary']);
        for ($i = 1; $i <= 13; $i++) {
            Message::factory()->for($company)->for($conversation)->create(['body' => "Message {$i}", 'created_at' => now()->addSeconds($i)]);
        }

        $this->withHeaders($headers)->getJson("/api/integrations/conversations/{$conversation->id}/context")
            ->assertOk()->assertJsonPath('ai_summary', 'Context summary')->assertJsonCount(12, 'messages')
            ->assertJsonPath('messages.0.body', 'Message 2')->assertJsonPath('messages.11.body', 'Message 13');
    }

    public function test_part_search_respects_company_active_stock_and_aliases(): void
    {
        [$company, $headers] = $this->integrationCompany();
        Part::factory()->for($company)->create(['name' => 'Screen', 'phone_brand' => 'Apple', 'phone_model' => 'iPhone 15', 'price_cents' => 12990, 'aliases' => ['display'], 'stock_quantity' => 2]);
        Part::factory()->for($company)->create(['aliases' => ['display'], 'stock_quantity' => 0]);
        Part::factory()->for($company)->create(['aliases' => ['display'], 'is_active' => false]);
        Part::factory()->create(['aliases' => ['display'], 'stock_quantity' => 2]);

        $this->withHeaders($headers)->getJson('/api/integrations/parts/search?q=display')->assertOk()
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Screen')->assertJsonPath('data.0.price', 'R$ 129,90')
            ->assertJsonMissing(['stock_quantity' => 2]);
    }

    public function test_outbound_messages_are_idempotent_and_handoff_lasts_two_hours(): void
    {
        Carbon::setTestNow('2026-10-17 10:00:00');
        [$company, $headers] = $this->integrationCompany();
        $contact = Contact::factory()->for($company)->create();
        $conversation = Conversation::factory()->for($company)->for($contact)->create();
        $url = "/api/integrations/conversations/{$conversation->id}";

        $this->withHeaders($headers)->postJson("{$url}/messages", ['body' => 'Reply', 'sender' => 'bot', 'provider_message_id' => 'outbound-1'])->assertCreated();
        $this->withHeaders($headers)->postJson("{$url}/messages", ['body' => 'Changed', 'sender' => 'bot', 'provider_message_id' => 'outbound-1'])->assertOk();
        $this->assertDatabaseCount('messages', 1);
        $this->withHeaders($headers)->postJson("{$url}/handoff")->assertOk()->assertJsonPath('automation_state', 'human_paused');
        $conversation->refresh();
        $this->assertTrue($conversation->human_paused_until->equalTo(now()->addHours(2)));
        Carbon::setTestNow();
    }

    public function test_human_whatsapp_message_is_idempotent_and_pauses_the_open_conversation(): void
    {
        Carbon::setTestNow('2026-10-17 10:00:00');
        [$company, $headers] = $this->integrationCompany();
        $contact = Contact::factory()->for($company)->create(['whatsapp_number' => '5511988887777']);
        $conversation = Conversation::factory()->for($company)->for($contact)->create();
        $payload = ['provider_message_id' => 'human-1', 'whatsapp_number' => $contact->whatsapp_number, 'body' => 'Vou assumir este atendimento.', 'occurred_at' => now()->toIso8601String()];

        $this->withHeaders($headers)->postJson('/api/integrations/whatsapp/human-outbound', $payload)->assertCreated();
        $this->withHeaders($headers)->postJson('/api/integrations/whatsapp/human-outbound', $payload)->assertOk();

        $this->assertDatabaseCount('messages', 1);
        $this->assertDatabaseHas('messages', ['conversation_id' => $conversation->id, 'sender' => 'human', 'provider_message_id' => 'human-1']);
        $conversation->refresh();
        $this->assertSame('human_paused', $conversation->automation_state);
        $this->assertTrue($conversation->human_paused_until->equalTo(now()->addHours(2)));
        Carbon::setTestNow();
    }

    public function test_summary_validates_its_limit_and_updates_the_conversation(): void
    {
        [$company, $headers] = $this->integrationCompany();
        $contact = Contact::factory()->for($company)->create();
        $conversation = Conversation::factory()->for($company)->for($contact)->create();
        $url = "/api/integrations/conversations/{$conversation->id}/summary";

        $this->withHeaders($headers)->postJson($url, ['ai_summary' => str_repeat('a', 5001)])->assertUnprocessable();
        $this->withHeaders($headers)->postJson($url, ['ai_summary' => 'Updated'])->assertOk();
        $this->assertDatabaseHas('conversations', ['id' => $conversation->id, 'ai_summary' => 'Updated']);
    }

    /** @return array{Company, array<string, string>} */
    private function integrationCompany(): array
    {
        $token = 'test-token-'.fake()->uuid();
        $company = Company::factory()->create(['integration_token_hash' => hash('sha256', $token)]);

        return [$company, ['X-Integration-Token' => $token]];
    }

    /** @return array<string, string> */
    private function inboundPayload(): array
    {
        return ['provider_message_id' => 'provider-1', 'whatsapp_number' => '5511999999999', 'contact_name' => 'Maria', 'body' => 'Olá', 'occurred_at' => '2026-10-17T10:00:00-03:00'];
    }
}
