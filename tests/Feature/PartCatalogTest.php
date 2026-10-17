<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Part;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_attendants_can_create_parts_and_company_is_derived_from_the_authenticated_user(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->for($company)->attendant()->create();

        $this->actingAs($user)->post(route('parts.store'), $this->payload([
            'company_id' => Company::factory()->create()->id,
        ]))->assertRedirect(route('parts.index'));

        $this->assertDatabaseHas('parts', [
            'company_id' => $company->id,
            'name' => 'Screen assembly',
            'price_cents' => 12990,
            'stock_quantity' => 5,
        ]);
    }

    public function test_catalog_only_lists_parts_from_the_current_company_including_searches(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->for($company)->create();
        Part::factory()->for($company)->create(['name' => 'Battery', 'aliases' => ['energia']]);
        $otherPart = Part::factory()->create(['name' => 'Battery from another company', 'aliases' => ['energia']]);

        $this->actingAs($user)->get(route('parts.index', ['search' => 'energia']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('parts/Index')
                ->has('parts', 1)
                ->where('parts.0.name', 'Battery'));

        $this->actingAs($user)->get(route('parts.edit', $otherPart))->assertForbidden();
    }

    public function test_searches_name_brand_model_sku_and_aliases(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->for($company)->create();
        Part::factory()->for($company)->create([
            'name' => 'Screen assembly',
            'phone_brand' => 'Samsung',
            'phone_model' => 'Galaxy S24',
            'sku' => 'SCR-24',
            'aliases' => ['display'],
        ]);

        foreach (['Screen', 'Samsung', 'Galaxy', 'SCR-24', 'display'] as $search) {
            $this->actingAs($user)->get(route('parts.index', ['search' => $search]))
                ->assertOk()
                ->assertInertia(fn ($page) => $page->has('parts', 1));
        }
    }

    public function test_part_validation_rejects_negative_price_and_stock(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('parts.store'), $this->payload([
            'price_cents' => -1,
            'stock_quantity' => -1,
        ]))->assertSessionHasErrors(['price_cents', 'stock_quantity']);
    }

    public function test_users_cannot_update_toggle_or_delete_parts_from_another_company(): void
    {
        $user = User::factory()->create();
        $part = Part::factory()->create();

        $this->actingAs($user)->put(route('parts.update', $part), $this->payload())->assertForbidden();
        $this->actingAs($user)->patch(route('parts.toggle', $part))->assertForbidden();
        $this->actingAs($user)->delete(route('parts.destroy', $part))->assertForbidden();
        $this->assertDatabaseHas('parts', ['id' => $part->id, 'deleted_at' => null]);
    }

    /** @return array<string, mixed> */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Screen assembly',
            'phone_brand' => 'Apple',
            'phone_model' => 'iPhone 15',
            'sku' => 'SCR-15',
            'price_cents' => 12990,
            'stock_quantity' => 5,
            'aliases' => ['display', 'screen'],
            'is_active' => true,
        ], $overrides);
    }
}
