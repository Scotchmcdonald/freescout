<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ConversationStatus;
use Illuminate\Foundation\Http\FormRequest;

class UpdateConversationRequest extends FormRequest
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
            'status' => 'nullable|integer|in:'.ConversationStatus::Active->value.','.ConversationStatus::Pending->value.','.ConversationStatus::Closed->value,
            'user_id' => 'nullable|integer|exists:users,id',
            'folder_id' => 'nullable|integer|exists:folders,id',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Map text status values to integers
        $status = $this->input('status');
        if (is_string($status) && !is_numeric($status)) {
            $statusMap = [
                'active' => ConversationStatus::Active->value,
                'pending' => ConversationStatus::Pending->value,
                'closed' => ConversationStatus::Closed->value,
                'resolved' => ConversationStatus::Closed->value,
            ];
            if (isset($statusMap[strtolower($status)])) {
                $this->merge([
                    'status' => $statusMap[strtolower($status)],
                    'status_text' => strtolower($status),
                ]);
            }
        }
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.in' => 'Invalid status value. Must be Active (1), Closed (2), or Pending (3).',
            'user_id.exists' => 'The selected assignee does not exist.',
            'folder_id.exists' => 'The selected folder does not exist.',
        ];
    }
}
