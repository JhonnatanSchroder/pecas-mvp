<?php

namespace App\Http\Requests\Integration;

use Illuminate\Foundation\Http\FormRequest;

class StoreIntegrationMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:10000'],
            'sender' => ['required', 'in:bot,human'],
            'provider_message_id' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return ['required' => 'O campo :attribute é obrigatório.', 'in' => 'O campo :attribute é inválido.'];
    }
}
