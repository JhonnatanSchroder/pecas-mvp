<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Database\Factories\ConversationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/** @property Carbon|null $human_paused_until */
class Conversation extends Model
{
    /** @use HasFactory<ConversationFactory> */
    use BelongsToCompany, HasFactory;

    protected $fillable = ['contact_id', 'status', 'automation_state', 'human_paused_until', 'handled_by', 'ai_summary'];

    protected function casts(): array
    {
        return ['human_paused_until' => 'datetime'];
    }

    /** @return BelongsTo<Contact, $this> */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /** @return BelongsTo<User, $this> */
    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    /** @return HasMany<Message, $this> */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
}
