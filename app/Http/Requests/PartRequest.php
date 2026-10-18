<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PartRequest extends FormRequest
{
    public function authorize(): bool
    {
        $part = $this->route('part');

        return $part === null || $this->user()?->can('update', $part) === true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'phone_brand' => ['required', 'string', 'max:255'],
            'phone_model' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:255'],
            'price_cents' => ['required', 'integer', 'min:0'],
            'aliases' => ['nullable', 'array'],
            'aliases.*' => ['string', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ];

        $rules['stock_quantity'] = $this->isMethod('post')
            ? ['required', 'integer', 'min:0']
            : ['prohibited'];

        return $rules;
    }
}
