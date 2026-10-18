<?php

namespace App\Http\Controllers;

use App\Http\Requests\InventoryAdjustmentRequest;
use App\Http\Requests\PartRequest;
use App\Models\InventoryMovement;
use App\Models\Part;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PartController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Part::class);

        $search = trim((string) $request->string('search'));
        $parts = Part::query()
            ->forCurrentCompany()
            ->when($search !== '', function ($query) use ($search): void {
                $term = '%'.addcslashes($search, '%_\\').'%';

                $query->where(function ($query) use ($term): void {
                    $query->where('name', 'like', $term)
                        ->orWhere('phone_brand', 'like', $term)
                        ->orWhere('phone_model', 'like', $term)
                        ->orWhere('sku', 'like', $term)
                        ->orWhereRaw('CAST(aliases AS CHAR) LIKE ?', [$term]);
                });
            })
            ->orderBy('name')
            ->get()
            ->map(fn (Part $part): array => $this->partData($part));

        return Inertia::render('parts/Index', [
            'parts' => $parts,
            'filters' => ['search' => $search],
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Part::class);

        return Inertia::render('parts/Form', ['part' => null]);
    }

    public function store(PartRequest $request): RedirectResponse
    {
        Gate::authorize('create', Part::class);

        DB::transaction(function () use ($request): void {
            $part = Part::create($request->validated());

            if ($part->stock_quantity > 0) {
                InventoryMovement::create([
                    'part_id' => $part->id,
                    'user_id' => $request->user()->id,
                    'type' => 'initial',
                    'quantity_delta' => $part->stock_quantity,
                    'balance_after' => $part->stock_quantity,
                ]);
            }
        });

        return to_route('parts.index')->with('success', 'Peça cadastrada com sucesso.');
    }

    public function edit(Part $part): Response
    {
        Gate::authorize('update', $part);

        return Inertia::render('parts/Form', ['part' => $this->partData($part)]);
    }

    public function update(PartRequest $request, Part $part): RedirectResponse
    {
        Gate::authorize('update', $part);

        $part->update($request->validated());

        return to_route('parts.index')->with('success', 'Peça atualizada com sucesso.');
    }

    public function toggle(Part $part): RedirectResponse
    {
        Gate::authorize('update', $part);

        $part->update(['is_active' => ! $part->is_active]);

        return back()->with('success', $part->is_active ? 'Peça ativada.' : 'Peça desativada.');
    }

    public function destroy(Part $part): RedirectResponse
    {
        Gate::authorize('delete', $part);

        $part->delete();

        return to_route('parts.index')->with('success', 'Peça excluída com sucesso.');
    }

    public function inventoryHistory(Part $part): Response
    {
        Gate::authorize('view', $part);

        $movements = InventoryMovement::query()
            ->forCurrentCompany()
            ->where('part_id', $part->id)
            ->with('user:id,name')
            ->latest()
            ->get()
            ->map(fn (InventoryMovement $movement): array => [
                'id' => $movement->id,
                'type' => $movement->type,
                'quantity_delta' => $movement->quantity_delta,
                'balance_after' => $movement->balance_after,
                'reason' => $movement->reason,
                'user_name' => $movement->user?->name,
                'created_at' => $movement->created_at->toIso8601String(),
            ]);

        return Inertia::render('parts/InventoryHistory', [
            'part' => $this->partData($part),
            'movements' => $movements,
            'canAdjustInventory' => Gate::allows('adjustInventory', $part),
        ]);
    }

    public function createInventoryAdjustment(Part $part): Response
    {
        Gate::authorize('adjustInventory', $part);

        return Inertia::render('parts/InventoryAdjustment', ['part' => $this->partData($part)]);
    }

    public function storeInventoryAdjustment(InventoryAdjustmentRequest $request, Part $part): RedirectResponse
    {
        Gate::authorize('adjustInventory', $part);

        $validated = $request->validated();

        DB::transaction(function () use ($part, $request, $validated): void {
            $lockedPart = Part::query()
                ->forCurrentCompany()
                ->lockForUpdate()
                ->findOrFail($part->id);

            $balanceAfter = $lockedPart->stock_quantity + $validated['quantity_delta'];

            if ($balanceAfter < 0) {
                throw ValidationException::withMessages([
                    'quantity_delta' => 'O ajuste deixaria o estoque negativo.',
                ]);
            }

            $lockedPart->update(['stock_quantity' => $balanceAfter]);

            InventoryMovement::create([
                'part_id' => $lockedPart->id,
                'user_id' => $request->user()->id,
                'type' => 'adjustment',
                'quantity_delta' => $validated['quantity_delta'],
                'balance_after' => $balanceAfter,
                'reason' => $validated['reason'],
            ]);
        });

        return to_route('parts.inventory-history', $part)->with('success', 'Estoque ajustado com sucesso.');
    }

    /** @return array<string, mixed> */
    private function partData(Part $part): array
    {
        return [
            'id' => $part->id,
            'name' => $part->name,
            'phone_brand' => $part->phone_brand,
            'phone_model' => $part->phone_model,
            'sku' => $part->sku,
            'price_cents' => $part->price_cents,
            'stock_quantity' => $part->stock_quantity,
            'aliases' => $part->aliases,
            'is_active' => $part->is_active,
            'can_adjust_inventory' => Gate::allows('adjustInventory', $part),
        ];
    }
}
