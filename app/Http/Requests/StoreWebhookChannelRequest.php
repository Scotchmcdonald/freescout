<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWebhookChannelRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route group handled by 'auth'+'verified' middleware — no additional gate.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'resource_type' => ['required', 'string', 'max:50'],
            'resource_id' => ['required', 'string', 'max:255'],
            'webhook_url' => ['required', 'url', 'max:512'],
            'duration_hours' => ['required', 'integer', 'min:1', 'max:43200'],
        ];
    }
}
