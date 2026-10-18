<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\InventoryMovement;
use App\Models\Part;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryMovementTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_part_with_initial_stock_creates_an_initial_movement(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->for($company)->admin()->create();

        $this->actingAs($user)->post(route('parts.store'), $this->partPayload())
            ->assertRedirect(route('parts.index'));

        $part = Part::query()->sole();
        $this->assertDatabaseHas('inventory_movements', [
            'company_id' => $company->id,
            'part_id' => $part->id,
            'user_id' => $user->id,
            'type' => 'initial',
            'quantity_delta' => 5,
            'balance_after' => 5,
            'reason' => null,
        ]);
    }

    public function test_admin_can_adjust_inventory_in_both_directions_and_movements_track_the_balance(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->for($company)->admin()->create();
        $part = Part::factory()->for($company)->create(['stock_quantity' => 5]);

        $this->actingAs($admin)->post(route('parts.inventory-adjustment.store', $part), [
            'quantity_delta' => 3,
            'reason' => 'Contagem recebida',
        ])->assertRedirect(route('parts.inventory-history', $part));

        $this->actingAs($admin)->post(route('parts.inventory-adjustment.store', $part), [
            'quantity_delta' => -2,
            'reason' => 'Correção de contagem',
        ])->assertRedirect(route('parts.inventory-history', $part));

        $this->assertSame(6, $part->refresh()->stock_quantity);
        $this->assertDatabaseHas('inventory_movements', [
            'company_id' => $company->id,
            'part_id' => $part->id,
            'type' => 'adjustment',
            'quantity_delta' => 3,
            'balance_after' => 8,
            'reason' => 'Contagem recebida',
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'part_id' => $part->id,
            'quantity_delta' => -2,
            'balance_after' => 6,
        ]);
    }

    public function test_negative_adjustment_rolls_back_without_changing_stock_or_creating_a_movement(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->for($company)->admin()->create();
        $part = Part::factory()->for($company)->create(['stock_quantity' => 2]);

        $this->actingAs($admin)->from(route('parts.inventory-adjustment.create', $part))
            ->post(route('parts.inventory-adjustment.store', $part), [
                'quantity_delta' => -3,
                'reason' => 'Correção',
            ])
            ->assertRedirect(route('parts.inventory-adjustment.create', $part))
            ->assertSessionHasErrors('quantity_delta');

        $this->assertSame(2, $part->refresh()->stock_quantity);
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_attendants_cannot_adjust_but_can_view_the_company_inventory_history(): void
    {
        $company = Company::factory()->create();
        $attendant = User::factory()->for($company)->attendant()->create();
        $part = Part::factory()->for($company)->create();
        InventoryMovement::factory()->for($company)->for($part)->create();

        $this->actingAs($attendant)->get(route('parts.inventory-history', $part))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('parts/InventoryHistory')
                ->has('movements', 1)
                ->where('canAdjustInventory', false));
        $this->actingAs($attendant)->get(route('parts.inventory-adjustment.create', $part))->assertForbidden();
        $this->actingAs($attendant)->post(route('parts.inventory-adjustment.store', $part), [
            'quantity_delta' => 1,
            'reason' => 'Tentativa',
        ])->assertForbidden();
    }

    public function test_companies_cannot_view_or_adjust_another_companys_inventory(): void
    {
        $admin = User::factory()->admin()->create();
        $otherPart = Part::factory()->create();
        InventoryMovement::factory()->for($otherPart->company)->for($otherPart)->create();

        $this->actingAs($admin)->get(route('parts.inventory-history', $otherPart))->assertForbidden();
        $this->actingAs($admin)->post(route('parts.inventory-adjustment.store', $otherPart), [
            'quantity_delta' => 1,
            'reason' => 'Tentativa',
        ])->assertForbidden();
    }

    public function test_stock_cannot_be_updated_through_the_part_edit_endpoint(): void
    {
        $user = User::factory()->create();
        $part = Part::factory()->for($user->company)->create(['stock_quantity' => 5]);

        $this->actingAs($user)->put(route('parts.update', $part), $this->partPayload([
            'stock_quantity' => 99,
        ]))->assertSessionHasErrors('stock_quantity');

        $this->assertSame(5, $part->refresh()->stock_quantity);
    }

    /** @return array<string, mixed> */
    private function partPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Screen assembly',
            'phone_brand' => 'Apple',
            'phone_model' => 'iPhone 15',
            'sku' => 'SCR-15',
            'price_cents' => 12990,
            'stock_quantity' => 5,
            'aliases' => ['display'],
            'is_active' => true,
        ], $overrides);
    }
}
