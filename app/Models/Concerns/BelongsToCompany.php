<?php

namespace App\Models\Concerns;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * @property int $company_id
 * @property-read Company $company
 */
trait BelongsToCompany
{
    public static function bootBelongsToCompany(): void
    {
        static::creating(function (self $model): void {
            /** @var User|null $user */
            $user = auth()->user();

            $companyId = $user !== null ? $user->company_id : request()->attributes->get('integration_company_id');

            if ($companyId !== null) {
                $model->company_id = $companyId;
            }
        });
    }

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Scope records to the authenticated user's company.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForCurrentCompany(Builder $query): Builder
    {
        /** @var User|null $user */
        $user = auth()->user();

        $companyId = $user !== null ? $user->company_id : request()->attributes->get('integration_company_id');

        if ($companyId === null) {
            throw new LogicException('Company-scoped queries require an authenticated user with a company.');
        }

        return $query->where($query->qualifyColumn('company_id'), $companyId);
    }
}
