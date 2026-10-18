<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;

class ConversationPolicy extends CompanyScopedPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Conversation $conversation): bool
    {
        return $this->belongsToUsersCompany($user, $conversation);
    }

    public function takeOver(User $user, Conversation $conversation): bool
    {
        return $this->belongsToUsersCompany($user, $conversation);
    }

    public function resumeAutomation(User $user, Conversation $conversation): bool
    {
        return $this->belongsToUsersCompany($user, $conversation);
    }
}
