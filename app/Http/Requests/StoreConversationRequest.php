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
        /** @var \App\Models\User|null $user */
        $user = $this->user();
        
        if (!$user) {
            return false;
        }

        // Get mailbox from route
        $mailbox = $this->route('mailbox');
        if (!($mailbox instanceof \App\Models\Mailbox)) {
            $mailbox = \App\Models\Mailbox::find($mailbox);
        }
        
        if (!$mailbox instanceof \App\Models\Mailbox) {
            return false;
        }

        // Check access: must be admin or have access to mailbox
        return $user->isAdmin() || $user->mailboxes->contains($mailbox->id);
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
     * Prepare the data for validation and sanitize HTML input.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('body')) {
            $this->merge([
                'body' => clean($this->input('body'), 'default'),
            ]);
        }
        
        if ($this->has('subject')) {
            $subject = $this->input('subject');
            $subjectStr = is_string($subject) || is_int($subject) || is_float($subject) ? (string) $subject : '';
            $this->merge([
                'subject' => strip_tags($subjectStr),
            ]);
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
            'subject.required' => 'A subject is required for the conversation.',
            'subject.max' => 'The subject cannot exceed 998 characters.',
            'body.required' => 'Please provide a message body.',
            'to.required' => 'At least one recipient email is required.',
            'to.*.email' => 'Each recipient must be a valid email address.',
            'customer_email.email' => 'Please provide a valid customer email address.',
        ];
    }
}
