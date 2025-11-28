<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGeneralSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled in controller (admin only)
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'company_name' => 'nullable|string|max:255',
            'custom_number' => 'nullable|boolean',
            'next_ticket' => 'nullable|integer|min:1',
            'locale' => 'nullable|string|max:10',
            'timezone' => 'nullable|timezone',
            'time_format' => 'nullable|in:12,24',
            'email_conv_history' => 'nullable|in:none,last,full',
            'max_message_size' => 'nullable|integer|min:0|max:102400',
            'email_branding' => 'nullable|boolean',
            'open_tracking' => 'nullable|boolean',
            'enrich_customer_data' => 'nullable|boolean',
            'user_permissions' => 'nullable|array',
            'user_permissions.*' => 'nullable|integer',
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
            'company_name.max' => 'Company name must not exceed 255 characters.',
            'next_ticket.min' => 'Next ticket number must be at least 1.',
            'timezone.timezone' => 'Please select a valid timezone.',
            'time_format.in' => 'Time format must be either 12 or 24 hour.',
            'email_conv_history.in' => 'Email conversation history must be none, last, or full.',
            'max_message_size.max' => 'Maximum message size cannot exceed 100MB.',
        ];
    }
}
