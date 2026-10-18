<?php

namespace App\Http\Requests;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;

class OrderStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Order::class) === true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.part_id' => ['required', 'integer', 'distinct'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }

    /** @return array<int, array{part_id: int, quantity: int}> */
    public function orderItems(): array
    {
        $items = $this->validated('items');

        if (! is_array($items)) {
            return [];
        }

        $validatedItems = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $validatedItems[] = [
                'part_id' => (int) $item['part_id'],
                'quantity' => (int) $item['quantity'],
            ];
        }

        return $validatedItems;
    }
}
