<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Database\Factories\MessageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    /** @use HasFactory<MessageFactory> */
    use BelongsToCompany, HasFactory;

    protected $fillable = ['conversation_id', 'body', 'direction', 'sender', 'provider_message_id'];

    /** @return BelongsTo<Conversation, $this> */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }
}
