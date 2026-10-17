<?php

namespace App\Policies;

use App\Models\Part;
use App\Models\User;

class PartPolicy extends CompanyScopedPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Part $part): bool
    {
        return $this->belongsToUsersCompany($user, $part);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Part $part): bool
    {
        return $this->belongsToUsersCompany($user, $part);
    }

    public function delete(User $user, Part $part): bool
    {
        return $this->belongsToUsersCompany($user, $part);
    }
}
