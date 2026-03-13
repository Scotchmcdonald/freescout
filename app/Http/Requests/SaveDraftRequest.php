<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        // The controller checks Auth::user() directly; auth handled by middleware.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'conversation_id' => ['required', 'integer', 'exists:conversations,id'],
            'body' => ['nullable', 'string'],
            'to' => ['nullable'],   // JSON string or array of email addresses
            'cc' => ['nullable'],
            'bcc' => ['nullable'],
            'attachment_ids' => ['nullable', 'array'],
            'attachment_ids.*' => ['integer'],
        ];
    }
}
