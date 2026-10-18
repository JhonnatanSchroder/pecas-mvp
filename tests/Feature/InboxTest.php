<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class InboxTest extends TestCase
{
    use RefreshDatabase;

    public function test_inbox_searches_contacts_by_name_and_whatsapp_within_company(): void
    {
        [$company, $user, $conversation] = $this->conversationFor('attendant', 'Maria Silva', '5511999999999');
        [, , $otherConversation] = $this->conversationFor('admin', 'Maria Other', '5511888888888');

        $this->actingAs($user)->get(route('inbox.index', ['search' => 'Maria']))->assertOk()->assertInertia(fn ($page) => $page->component('inbox/Index')->has('conversations', 1)->where('conversations.0.id', $conversation->id));
        $this->actingAs($user)->get(route('inbox.index', ['search' => '99999999']))->assertInertia(fn ($page) => $page->has('conversations', 1));
        $this->assertNotSame($conversation->id, $otherConversation->id);
        $this->assertSame($company->id, $conversation->company_id);
    }

    public function test_users_cannot_access_conversations_or_messages_from_another_company(): void
    {
        [, $user] = $this->conversationFor('admin', 'Maria', '5511999999999');
        [, , $other] = $this->conversationFor('attendant', 'João', '5511888888888');

        $this->actingAs($user)->get(route('inbox.show', $other))->assertForbidden();
        $this->actingAs($user)->post(route('inbox.take-over', $other))->assertForbidden();
        $this->actingAs($user)->post(route('inbox.resume-automation', $other))->assertForbidden();
    }

    public function test_admin_and_attendant_can_take_over_for_exactly_two_hours_and_resume_automation(): void
    {
        Carbon::setTestNow('2026-10-17 10:00:00');
        [, $user, $conversation] = $this->conversationFor('attendant', 'Maria', '5511999999999');

        $this->actingAs($user)->post(route('inbox.take-over', $conversation))->assertRedirect(route('inbox.show', $conversation));
        $conversation->refresh();
        $this->assertSame('human_paused', $conversation->automation_state);
        $this->assertSame($user->id, $conversation->handled_by);
        $this->assertTrue($conversation->human_paused_until->equalTo(now()->addHours(2)));

        $this->actingAs($user)->post(route('inbox.resume-automation', $conversation))->assertRedirect(route('inbox.show', $conversation));
        $conversation->refresh();
        $this->assertSame('automated', $conversation->automation_state);
        $this->assertNull($conversation->handled_by);
        $this->assertNull($conversation->human_paused_until);
        Carbon::setTestNow();
    }

    public function test_history_is_displayed_in_chronological_order(): void
    {
        [, $user, $conversation] = $this->conversationFor('admin', 'Maria', '5511999999999');
        Message::factory()->for($conversation->company)->for($conversation)->create(['body' => 'Primeira', 'created_at' => now()->subMinute()]);
        Message::factory()->for($conversation->company)->for($conversation)->create(['body' => 'Segunda', 'created_at' => now()]);

        $this->actingAs($user)->get(route('inbox.show', $conversation))->assertOk()->assertInertia(fn ($page) => $page->component('inbox/Show')->where('conversation.messages.0.body', 'Primeira')->where('conversation.messages.1.body', 'Segunda'));
    }

    /** @return array{Company, User, Conversation} */
    private function conversationFor(string $role, string $name, string $number): array
    {
        $company = Company::factory()->create();
        $user = User::factory()->for($company)->{$role}()->create();
        $contact = Contact::factory()->for($company)->create(['name' => $name, 'whatsapp_number' => $number]);
        $conversation = Conversation::factory()->for($company)->for($contact)->create();

        return [$company, $user, $conversation];
    }
}
