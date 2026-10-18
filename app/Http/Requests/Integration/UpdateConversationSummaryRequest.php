<?php

namespace App\Http\Requests\Integration;

use Illuminate\Foundation\Http\FormRequest;

class UpdateConversationSummaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['ai_summary' => ['nullable', 'string', 'max:5000']];
    }

    public function messages(): array
    {
        return ['max' => 'O campo :attribute não pode ter mais de :max caracteres.'];
    }
}
