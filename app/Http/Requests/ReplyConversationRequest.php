<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Conversation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        return [
            'body' => 'required|string',
            'type' => ['nullable', 'integer', Rule::in([1, 2])], // 1=reply, 2=note
            'status' => [
                'nullable',
                'integer',
                Rule::in([
                    Conversation::STATUS_ACTIVE,
                    Conversation::STATUS_PENDING,
                    Conversation::STATUS_CLOSED,
                ]),
            ],
            'follow_up_date' => 'nullable|date|after_or_equal:today',
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
            'follow_up_date.after_or_equal' => 'Follow-up date must be today or in the future.',
        ];
    }
}
