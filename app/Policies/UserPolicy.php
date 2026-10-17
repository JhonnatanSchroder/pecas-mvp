<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy extends CompanyScopedPolicy
{
    public function view(User $user, User $model): bool
    {
        return $this->belongsToUsersCompany($user, $model);
    }

    public function update(User $user, User $model): bool
    {
        return $this->belongsToUsersCompany($user, $model);
    }

    public function delete(User $user, User $model): bool
    {
        return $this->belongsToUsersCompany($user, $model);
    }
}
