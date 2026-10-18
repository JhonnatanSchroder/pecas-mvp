<?php

namespace App\Http\Requests\Integration;

use Illuminate\Foundation\Http\FormRequest;

class InboundWhatsappRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'provider_message_id' => ['required', 'string', 'max:255'],
            'whatsapp_number' => ['required', 'string', 'max:50'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:10000'],
            'occurred_at' => ['required', 'date'],
        ];
    }

    public function messages(): array
    {
        return ['required' => 'O campo :attribute é obrigatório.', 'date' => 'O campo :attribute deve ser uma data válida.'];
    }
}
