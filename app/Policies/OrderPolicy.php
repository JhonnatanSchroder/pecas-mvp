<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy extends CompanyScopedPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Order $order): bool
    {
        return $this->belongsToUsersCompany($user, $order);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function confirm(User $user, Order $order): bool
    {
        return $this->belongsToUsersCompany($user, $order);
    }

    public function cancel(User $user, Order $order): bool
    {
        return $this->belongsToUsersCompany($user, $order);
    }
}
