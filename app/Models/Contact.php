<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Database\Factories\ContactFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contact extends Model
{
    /** @use HasFactory<ContactFactory> */
    use BelongsToCompany, HasFactory;

    protected $fillable = ['name', 'whatsapp_number'];

    /** @return HasMany<Conversation, $this> */
    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }
}
