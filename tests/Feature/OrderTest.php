<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Order;
use App\Models\Part;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_attendants_can_create_a_pending_order_with_frozen_prices_without_changing_stock(): void
    {
        [$company, $user, $part] = $this->companyUserAndPart('attendant', 5, 12990);

        $this->actingAs($user)->post(route('orders.store'), [
            'company_id' => Company::factory()->create()->id,
            'items' => [['part_id' => $part->id, 'quantity' => 2, 'unit_price_cents' => 1]],
        ])->assertRedirect();

        $order = Order::query()->sole();
        $this->assertSame($company->id, $order->company_id);
        $this->assertSame('pending', $order->status);
        $this->assertSame(5, $part->refresh()->stock_quantity);
        $this->assertDatabaseHas('order_items', ['company_id' => $company->id, 'order_id' => $order->id, 'part_id' => $part->id, 'quantity' => 2, 'unit_price_cents' => 12990]);
        $this->assertDatabaseCount('inventory_movements', 0);

        $part->update(['price_cents' => 9900]);
        $this->assertSame(12990, $order->items()->sole()->unit_price_cents);
    }

    public function test_confirmation_decreases_all_stock_and_creates_sale_movements(): void
    {
        [$company, $user, $firstPart] = $this->companyUserAndPart('admin', 5, 1000);
        $secondPart = Part::factory()->for($company)->create(['stock_quantity' => 4, 'price_cents' => 2000]);
        $order = $this->createOrder($user, [[$firstPart, 2], [$secondPart, 3]]);

        $this->actingAs($user)->post(route('orders.confirm', $order))->assertRedirect(route('orders.show', $order));

        $this->assertSame('confirmed', $order->refresh()->status);
        $this->assertSame(3, $firstPart->refresh()->stock_quantity);
        $this->assertSame(1, $secondPart->refresh()->stock_quantity);
        $this->assertDatabaseHas('inventory_movements', ['company_id' => $company->id, 'order_id' => $order->id, 'part_id' => $firstPart->id, 'type' => 'sale', 'quantity_delta' => -2, 'balance_after' => 3]);
        $this->assertDatabaseHas('inventory_movements', ['order_id' => $order->id, 'part_id' => $secondPart->id, 'type' => 'sale', 'quantity_delta' => -3, 'balance_after' => 1]);
    }

    public function test_insufficient_stock_rolls_back_every_item_and_keeps_order_pending(): void
    {
        [, $user, $firstPart] = $this->companyUserAndPart('admin', 5, 1000);
        $secondPart = Part::factory()->for($user->company)->create(['stock_quantity' => 1]);
        $order = $this->createOrder($user, [[$firstPart, 2], [$secondPart, 2]]);

        $this->actingAs($user)->post(route('orders.confirm', $order))->assertSessionHasErrors('order');

        $this->assertSame('pending', $order->refresh()->status);
        $this->assertSame(5, $firstPart->refresh()->stock_quantity);
        $this->assertSame(1, $secondPart->refresh()->stock_quantity);
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_cancelling_a_confirmed_order_restores_stock_and_creates_reversal_movements(): void
    {
        [$company, $user, $part] = $this->companyUserAndPart('attendant', 5, 1000);
        $order = $this->createOrder($user, [[$part, 2]]);
        $this->actingAs($user)->post(route('orders.confirm', $order));

        $this->actingAs($user)->post(route('orders.cancel', $order))->assertRedirect(route('orders.show', $order));

        $this->assertSame('cancelled', $order->refresh()->status);
        $this->assertSame(5, $part->refresh()->stock_quantity);
        $this->assertDatabaseHas('inventory_movements', ['company_id' => $company->id, 'order_id' => $order->id, 'part_id' => $part->id, 'type' => 'reversal', 'quantity_delta' => 2, 'balance_after' => 5]);
    }

    public function test_pending_cancellation_does_not_create_movements_and_state_transitions_cannot_repeat(): void
    {
        [, $user, $part] = $this->companyUserAndPart('admin', 5, 1000);
        $order = $this->createOrder($user, [[$part, 1]]);

        $this->actingAs($user)->post(route('orders.cancel', $order))->assertRedirect();
        $this->actingAs($user)->post(route('orders.cancel', $order))->assertSessionHasErrors('order');
        $this->actingAs($user)->post(route('orders.confirm', $order))->assertSessionHasErrors('order');

        $this->assertSame(5, $part->refresh()->stock_quantity);
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_users_cannot_access_or_submit_parts_from_another_company(): void
    {
        [, $user, $part] = $this->companyUserAndPart('admin', 5, 1000);
        [, , $otherPart] = $this->companyUserAndPart('admin', 5, 1000);
        $order = $this->createOrder($user, [[$part, 1]]);

        $this->actingAs($user)->get(route('orders.show', $order))->assertOk();
        $this->actingAs($user)->post(route('orders.store'), ['items' => [['part_id' => $otherPart->id, 'quantity' => 1]]])->assertSessionHasErrors('items');

        $otherOrder = $this->createOrder($otherPart->company->users()->first(), [[$otherPart, 1]]);
        $this->actingAs($user)->get(route('orders.show', $otherOrder))->assertForbidden();
        $this->actingAs($user)->post(route('orders.confirm', $otherOrder))->assertForbidden();
    }

    /** @return array{Company, User, Part} */
    private function companyUserAndPart(string $role, int $stock, int $price): array
    {
        $company = Company::factory()->create();
        $user = User::factory()->for($company)->{$role}()->create();
        $part = Part::factory()->for($company)->create(['stock_quantity' => $stock, 'price_cents' => $price]);

        return [$company, $user, $part];
    }

    /** @param array<int, array{Part, int}> $items */
    private function createOrder(User $user, array $items): Order
    {
        $this->actingAs($user)->post(route('orders.store'), ['items' => array_map(fn (array $item): array => ['part_id' => $item[0]->id, 'quantity' => $item[1]], $items)]);

        return Order::query()->latest('id')->firstOrFail();
    }
}
