<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrderStoreRequest;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Part;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', Order::class);

        $orders = Order::query()->forCurrentCompany()->with('items')->latest()->get()
            ->map(fn (Order $order): array => $this->orderData($order));

        return Inertia::render('orders/Index', ['orders' => $orders]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Order::class);

        $parts = Part::query()->forCurrentCompany()->where('is_active', true)->orderBy('name')->get()
            ->map(fn (Part $part): array => [
                'id' => $part->id, 'name' => $part->name, 'sku' => $part->sku,
                'price_cents' => $part->price_cents, 'stock_quantity' => $part->stock_quantity,
            ]);

        return Inertia::render('orders/Form', ['parts' => $parts]);
    }

    public function store(OrderStoreRequest $request): RedirectResponse
    {
        $items = $request->orderItems();
        $partIds = collect($items)->pluck('part_id')->all();
        $parts = Part::query()->forCurrentCompany()->whereIn('id', $partIds)->get()->keyBy('id');

        if ($parts->count() !== count($partIds)) {
            throw ValidationException::withMessages(['items' => 'Uma ou mais peças não estão disponíveis para a empresa.']);
        }

        $order = DB::transaction(function () use ($items, $parts): Order {
            $order = Order::create(['status' => 'pending']);

            foreach ($items as $item) {
                $part = $parts->get($item['part_id']);
                OrderItem::create([
                    'order_id' => $order->id,
                    'part_id' => $part->id,
                    'quantity' => $item['quantity'],
                    'unit_price_cents' => $part->price_cents,
                ]);
            }

            return $order;
        });

        return to_route('orders.show', $order)->with('success', 'Pedido criado com sucesso.');
    }

    public function show(Order $order): Response
    {
        Gate::authorize('view', $order);
        $order->load(['items.part']);

        return Inertia::render('orders/Show', ['order' => $this->orderData($order, true)]);
    }

    public function confirm(Request $request, Order $order): RedirectResponse
    {
        Gate::authorize('confirm', $order);

        DB::transaction(function () use ($order, $request): void {
            $lockedOrder = Order::query()->forCurrentCompany()->lockForUpdate()->findOrFail($order->id);
            if ($lockedOrder->status !== 'pending') {
                throw ValidationException::withMessages(['order' => 'Apenas pedidos pendentes podem ser confirmados.']);
            }

            $items = OrderItem::query()->forCurrentCompany()->where('order_id', $lockedOrder->id)->get();
            $parts = Part::query()->forCurrentCompany()->whereIn('id', $items->pluck('part_id'))
                ->orderBy('id')->lockForUpdate()->get()->keyBy('id');

            foreach ($items as $item) {
                $part = $parts->get($item->part_id);
                if ($part === null || $part->stock_quantity < $item->quantity) {
                    throw ValidationException::withMessages(['order' => 'Estoque insuficiente para confirmar o pedido.']);
                }
            }

            foreach ($items as $item) {
                $part = $parts->get($item->part_id);
                $balanceAfter = $part->stock_quantity - $item->quantity;
                $part->update(['stock_quantity' => $balanceAfter]);
                InventoryMovement::create([
                    'part_id' => $part->id, 'order_id' => $lockedOrder->id, 'user_id' => $request->user()->id,
                    'type' => 'sale', 'quantity_delta' => -$item->quantity, 'balance_after' => $balanceAfter,
                ]);
            }

            $lockedOrder->update(['status' => 'confirmed']);
        });

        return to_route('orders.show', $order)->with('success', 'Pedido confirmado e estoque baixado.');
    }

    public function cancel(Request $request, Order $order): RedirectResponse
    {
        Gate::authorize('cancel', $order);

        DB::transaction(function () use ($order, $request): void {
            $lockedOrder = Order::query()->forCurrentCompany()->lockForUpdate()->findOrFail($order->id);
            if ($lockedOrder->status === 'cancelled') {
                throw ValidationException::withMessages(['order' => 'Este pedido já foi cancelado.']);
            }

            if ($lockedOrder->status === 'confirmed') {
                $items = OrderItem::query()->forCurrentCompany()->where('order_id', $lockedOrder->id)->get();
                $parts = Part::query()->forCurrentCompany()->whereIn('id', $items->pluck('part_id'))
                    ->orderBy('id')->lockForUpdate()->get()->keyBy('id');

                foreach ($items as $item) {
                    $part = $parts->get($item->part_id);
                    if ($part === null) {
                        throw ValidationException::withMessages(['order' => 'Peça do pedido não encontrada.']);
                    }
                    $balanceAfter = $part->stock_quantity + $item->quantity;
                    $part->update(['stock_quantity' => $balanceAfter]);
                    InventoryMovement::create([
                        'part_id' => $part->id, 'order_id' => $lockedOrder->id, 'user_id' => $request->user()->id,
                        'type' => 'reversal', 'quantity_delta' => $item->quantity, 'balance_after' => $balanceAfter,
                    ]);
                }
            }

            $lockedOrder->update(['status' => 'cancelled']);
        });

        return to_route('orders.show', $order)->with('success', 'Pedido cancelado.');
    }

    /** @return array<string, mixed> */
    private function orderData(Order $order, bool $includeParts = false): array
    {
        return [
            'id' => $order->id, 'status' => $order->status,
            'created_at' => $order->created_at->toIso8601String(),
            'items' => $order->items->map(fn (OrderItem $item): array => [
                'id' => $item->id, 'part_id' => $item->part_id, 'part_name' => $includeParts ? $item->part?->name : null,
                'quantity' => $item->quantity, 'unit_price_cents' => $item->unit_price_cents,
            ])->all(),
        ];
    }
}
