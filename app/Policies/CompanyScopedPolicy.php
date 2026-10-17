<?php

namespace App\Policies;

use App\Models\User;

abstract class CompanyScopedPolicy
{
    /**
     * Deny authorization whenever a company-owned record belongs to another company.
     */
    protected function belongsToUsersCompany(User $user, object $model): bool
    {
        return $user->company_id !== null
            && isset($model->company_id)
            && (int) $user->company_id === (int) $model->company_id;
    }
}
