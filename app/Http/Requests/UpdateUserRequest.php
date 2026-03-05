<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization: must be able to update the target user
        $targetUser = $this->route('user');
        return $this->user()?->can('update', $targetUser) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $routeUser = $this->route('user');
        $userId = $routeUser instanceof \App\Models\User ? $routeUser->id : (int) $routeUser;

        $isExternal = (int) $this->input('type') === 2;

        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$userId,
            'password' => 'nullable|string|min:8',
            'type' => 'nullable|integer|in:1,2',
            'role' => $isExternal
                ? 'nullable|integer'
                : 'required|integer|in:'.UserRole::User->value.','.UserRole::Admin->value.','.UserRole::Reporter->value.','.UserRole::Finance->value,
            'client_role' => $isExternal
                ? 'required|string|in:Client Admin,Client User,Client Finance'
                : 'nullable|string',
            'status' => 'required|integer|in:'.UserStatus::Active->value.','.UserStatus::Inactive->value,
            'job_title' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:60',
            'timezone' => 'nullable|string|max:255',
            'locale' => 'nullable|string|max:2',
            'mailboxes' => 'nullable|array',
            'mailboxes.*' => 'integer|exists:mailboxes,id',
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
            'first_name.required' => 'First name is required.',
            'email.required' => 'Email address is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email address is already in use.',
            'password.min' => 'Password must be at least 8 characters.',
            'role.required' => 'User role is required.',
            'role.in' => 'Invalid user role selected.',
            'client_role.required' => 'A client role is required for external users.',
            'client_role.in' => 'Invalid client role selected.',
            'status.required' => 'User status is required.',
            'status.in' => 'Invalid user status selected.',
            'mailboxes.*.exists' => 'One or more selected mailboxes do not exist.',
        ];
    }
}
