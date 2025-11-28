<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ConversationStatus;
use Illuminate\Foundation\Http\FormRequest;

class StoreConversationRequest extends FormRequest
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
            'subject' => 'required|string|max:998',
            'body' => 'required|string',
            'to' => 'required|array|min:1',
            'to.*' => 'email',
            'customer_id' => 'nullable|exists:customers,id',
            'customer_email' => 'nullable|email',
            'customer_first_name' => 'nullable|string|max:50',
            'customer_last_name' => 'nullable|string|max:50',
            'status' => 'nullable|integer|in:'.ConversationStatus::Active->value.','.ConversationStatus::Pending->value.','.ConversationStatus::Closed->value,
            'assign_to' => 'nullable|exists:users,id',
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
            'subject.required' => 'A subject is required for the conversation.',
            'subject.max' => 'The subject cannot exceed 998 characters.',
            'body.required' => 'Please provide a message body.',
            'to.required' => 'At least one recipient email is required.',
            'to.*.email' => 'Each recipient must be a valid email address.',
            'customer_email.email' => 'Please provide a valid customer email address.',
        ];
    }
}
