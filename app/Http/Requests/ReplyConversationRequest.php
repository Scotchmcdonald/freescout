<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Conversation;
use Illuminate\Foundation\Http\FormRequest;

class ReplyConversationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled in controller
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $validStatuses = implode(',', [
            Conversation::STATUS_ACTIVE,
            Conversation::STATUS_PENDING,
            Conversation::STATUS_CLOSED,
        ]);

        return [
            'body' => 'required|string',
            'type' => 'nullable|integer|in:1,2', // 1=reply, 2=note
            'status' => 'nullable|integer|in:'.$validStatuses,
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'body.required' => 'Reply content is required.',
            'type.in' => 'Invalid reply type. Must be reply or note.',
            'status.in' => 'Invalid status selected.',
        ];
    }
}
