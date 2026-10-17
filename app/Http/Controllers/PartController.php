<?php

namespace App\Http\Controllers;

use App\Http\Requests\PartRequest;
use App\Models\Part;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
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

        Part::create($request->validated());

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
        ];
    }
}
