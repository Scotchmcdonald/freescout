<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMailboxRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled in controller via policy
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $mailboxId = $this->route('mailbox')?->id ?? $this->route('mailbox');

        return [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:mailboxes,email,'.$mailboxId,
            'from_name' => 'nullable|string|max:255',
            'out_method' => 'nullable|in:mail,smtp',
            'out_server' => 'nullable|string|max:255',
            'out_port' => 'nullable|integer',
            'out_username' => 'nullable|string|max:255',
            'out_password' => 'nullable|string',
            'out_encryption' => 'nullable|in:none,ssl,tls',
            'in_server' => 'nullable|string|max:255',
            'in_port' => 'nullable|integer',
            'in_username' => 'nullable|string|max:255',
            'in_password' => 'nullable|string',
            'in_protocol' => 'nullable|in:imap,pop3',
            'in_encryption' => 'nullable|in:none,ssl,tls',
            'auto_reply_enabled' => 'nullable|boolean',
            'auto_reply_subject' => 'nullable|string|max:255',
            'auto_reply_message' => 'nullable|string',
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
            'name.required' => 'A mailbox name is required.',
            'email.required' => 'A mailbox email address is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email address is already used by another mailbox.',
            'out_method.in' => 'Invalid outgoing mail method. Must be mail or smtp.',
            'out_encryption.in' => 'Invalid outgoing encryption. Must be none, ssl, or tls.',
            'in_protocol.in' => 'Invalid incoming protocol. Must be imap or pop3.',
            'in_encryption.in' => 'Invalid incoming encryption. Must be none, ssl, or tls.',
        ];
    }
}
