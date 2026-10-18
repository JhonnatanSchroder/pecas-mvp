<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class InboxController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Conversation::class);
        $search = trim((string) $request->string('search'));
        $status = (string) $request->string('status');
        $automationState = (string) $request->string('automation_state');

        $conversations = Conversation::query()->forCurrentCompany()->with(['contact', 'handler'])
            ->when($search !== '', function ($query) use ($search): void {
                $term = '%'.addcslashes($search, '%_\\').'%';
                $query->whereHas('contact', fn ($contactQuery) => $contactQuery->where('name', 'like', $term)->orWhere('whatsapp_number', 'like', $term));
            })
            ->when(in_array($status, ['open', 'closed'], true), fn ($query) => $query->where('status', $status))
            ->when(in_array($automationState, ['automated', 'human_paused'], true), fn ($query) => $query->where('automation_state', $automationState))
            ->latest()->get()->map(fn (Conversation $conversation): array => $this->conversationData($conversation));

        return Inertia::render('inbox/Index', ['conversations' => $conversations, 'filters' => compact('search', 'status', 'automationState')]);
    }

    public function show(Conversation $conversation): Response
    {
        Gate::authorize('view', $conversation);
        $conversation->load(['contact', 'handler', 'messages' => fn ($query) => $query->orderBy('created_at')->orderBy('id')]);

        return Inertia::render('inbox/Show', ['conversation' => $this->conversationData($conversation, true)]);
    }

    public function takeOver(Request $request, Conversation $conversation): RedirectResponse
    {
        Gate::authorize('takeOver', $conversation);
        $conversation->update(['automation_state' => 'human_paused', 'human_paused_until' => now()->addHours(2), 'handled_by' => $request->user()->id]);

        return to_route('inbox.show', $conversation)->with('success', 'Conversa assumida por duas horas.');
    }

    public function resumeAutomation(Conversation $conversation): RedirectResponse
    {
        Gate::authorize('resumeAutomation', $conversation);
        $conversation->update(['automation_state' => 'automated', 'human_paused_until' => null, 'handled_by' => null]);

        return to_route('inbox.show', $conversation)->with('success', 'Automação retomada.');
    }

    /** @return array<string, mixed> */
    private function conversationData(Conversation $conversation, bool $withMessages = false): array
    {
        return [
            'id' => $conversation->id, 'status' => $conversation->status, 'automation_state' => $conversation->automation_state,
            'human_paused_until' => $conversation->human_paused_until?->toIso8601String(), 'handled_by_name' => $conversation->handler?->name,
            'contact' => ['name' => $conversation->contact->name, 'whatsapp_number' => $conversation->contact->whatsapp_number],
            'messages' => $withMessages ? $conversation->messages->map(fn (Message $message): array => ['id' => $message->id, 'body' => $message->body, 'direction' => $message->direction, 'sender' => $message->sender, 'created_at' => $message->created_at->toIso8601String()])->all() : [],
        ];
    }
}
