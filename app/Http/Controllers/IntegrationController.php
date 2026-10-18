<?php

namespace App\Http\Controllers;

use App\Http\Requests\Integration\InboundWhatsappRequest;
use App\Http\Requests\Integration\StoreIntegrationMessageRequest;
use App\Http\Requests\Integration\UpdateConversationSummaryRequest;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Part;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class IntegrationController extends Controller
{
    public function inbound(InboundWhatsappRequest $request): JsonResponse
    {
        $companyId = $this->companyId($request);
        $data = $request->validated();

        $result = DB::transaction(function () use ($companyId, $data): array {
            $existing = Message::query()->forCurrentCompany()->where('provider_message_id', $data['provider_message_id'])->first();
            if ($existing !== null) {
                $conversation = Conversation::query()->forCurrentCompany()->findOrFail($existing->conversation_id);

                return [$conversation, false];
            }

            $contact = Contact::query()->forCurrentCompany()->firstOrCreate(
                ['whatsapp_number' => $data['whatsapp_number']],
                ['company_id' => $companyId, 'name' => $data['contact_name'] ?? null],
            );
            if (($data['contact_name'] ?? null) !== null && $contact->name !== $data['contact_name']) {
                $contact->update(['name' => $data['contact_name']]);
            }

            $conversation = Conversation::query()->forCurrentCompany()->where('contact_id', $contact->id)->where('status', 'open')->first();
            if ($conversation === null) {
                $conversation = Conversation::query()->create([
                    'company_id' => $companyId,
                    'contact_id' => $contact->id,
                    'status' => 'open',
                    'automation_state' => 'automated',
                ]);
            }

            Message::query()->create([
                'company_id' => $companyId,
                'conversation_id' => $conversation->id,
                'body' => $data['body'],
                'direction' => 'inbound',
                'sender' => 'customer',
                'provider_message_id' => $data['provider_message_id'],
                'created_at' => Carbon::parse($data['occurred_at']),
                'updated_at' => now(),
            ]);

            return [$conversation, true];
        });

        /** @var Conversation $conversation */
        [$conversation, $created] = $result;

        return response()->json($this->conversationState($conversation), $created ? 201 : 200);
    }

    public function context(Request $request, int $conversation): JsonResponse
    {
        $conversation = $this->conversation($conversation);
        $messages = $conversation->messages()->orderByDesc('created_at')->orderByDesc('id')->limit(12)->get()->reverse()->values();

        return response()->json([
            'conversation_id' => $conversation->id,
            'automation_state' => $conversation->automation_state,
            'human_paused_until' => $conversation->human_paused_until?->toIso8601String(),
            'ai_summary' => $conversation->ai_summary,
            'messages' => $messages->map(fn (Message $message): array => [
                'body' => $message->body,
                'direction' => $message->direction,
                'sender' => $message->sender,
                'occurred_at' => $message->created_at->toIso8601String(),
            ])->all(),
        ]);
    }

    public function searchParts(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('q', ''));
        if ($search === '') {
            return response()->json(['data' => []]);
        }
        $term = '%'.addcslashes($search, '%_\\').'%';
        $parts = Part::query()->forCurrentCompany()->where('is_active', true)->where('stock_quantity', '>', 0)
            ->where(function (Builder $query) use ($term): void {
                $query->where('name', 'like', $term)->orWhere('phone_brand', 'like', $term)->orWhere('phone_model', 'like', $term)
                    ->orWhere('sku', 'like', $term)->orWhere('aliases', 'like', $term);
            })->orderBy('name')->limit(3)->get();

        return response()->json(['data' => $parts->map(fn (Part $part): array => [
            'name' => $part->name,
            'model' => trim($part->phone_brand.' '.$part->phone_model),
            'price' => 'R$ '.number_format($part->price_cents / 100, 2, ',', '.'),
            'available' => true,
        ])->all()]);
    }

    public function storeMessage(StoreIntegrationMessageRequest $request, int $conversation): JsonResponse
    {
        $conversation = $this->conversation($conversation);
        $data = $request->validated();
        $message = null;
        $created = false;

        DB::transaction(function () use ($conversation, $data, &$message, &$created): void {
            if (($data['provider_message_id'] ?? null) !== null) {
                $message = Message::query()->forCurrentCompany()->where('provider_message_id', $data['provider_message_id'])->first();
            }
            if ($message === null) {
                $message = Message::query()->create([
                    'company_id' => $conversation->company_id,
                    'conversation_id' => $conversation->id,
                    'body' => $data['body'], 'direction' => 'outbound', 'sender' => $data['sender'],
                    'provider_message_id' => $data['provider_message_id'] ?? null,
                ]);
                $created = true;
            }
        });

        return response()->json(['message_id' => $message->id, 'conversation_id' => $message->conversation_id], $created ? 201 : 200);
    }

    public function handoff(int $conversation): JsonResponse
    {
        $conversation = $this->conversation($conversation);
        $conversation->update(['automation_state' => 'human_paused', 'human_paused_until' => now()->addHours(2), 'handled_by' => null]);

        return response()->json($this->conversationState($conversation));
    }

    public function summary(UpdateConversationSummaryRequest $request, int $conversation): JsonResponse
    {
        $conversation = $this->conversation($conversation);
        $conversation->update(['ai_summary' => $request->validated('ai_summary')]);

        return response()->json($this->conversationState($conversation));
    }

    private function conversation(int $id): Conversation
    {
        return Conversation::query()->forCurrentCompany()->findOrFail($id);
    }

    private function companyId(Request $request): int
    {
        return (int) $request->attributes->get('integration_company_id');
    }

    /** @return array{conversation_id: int, automation_state: string, human_paused_until: string|null} */
    private function conversationState(Conversation $conversation): array
    {
        return ['conversation_id' => $conversation->id, 'automation_state' => $conversation->automation_state, 'human_paused_until' => $conversation->human_paused_until?->toIso8601String()];
    }
}
