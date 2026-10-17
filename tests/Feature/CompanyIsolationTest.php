<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_current_company_scope_excludes_users_from_another_company(): void
    {
        $firstCompany = Company::factory()->create();
        $secondCompany = Company::factory()->create();
        $user = User::factory()->for($firstCompany)->create();
        $otherCompanyUser = User::factory()->for($secondCompany)->create();

        $this->actingAs($user);

        $this->assertSame([$user->id], User::query()->forCurrentCompany()->pluck('id')->all());
        $this->assertNotContains($otherCompanyUser->id, User::query()->forCurrentCompany()->pluck('id')->all());
    }

    public function test_user_policy_denies_cross_company_access(): void
    {
        $firstCompany = Company::factory()->create();
        $secondCompany = Company::factory()->create();
        $user = User::factory()->for($firstCompany)->create();
        $otherCompanyUser = User::factory()->for($secondCompany)->create();

        $this->assertTrue($user->can('view', $user));
        $this->assertFalse($user->can('view', $otherCompanyUser));
    }
}
