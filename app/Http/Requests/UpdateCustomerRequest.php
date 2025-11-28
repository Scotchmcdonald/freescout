<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerRequest extends FormRequest
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
            'first_name' => 'required|string|max:50',
            'last_name' => 'nullable|string|max:50',
            'company' => 'nullable|string|max:255',
            'job_title' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:60',
            'timezone' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'zip' => 'nullable|string|max:12',
            'country' => 'nullable|string|max:2',
            'notes' => 'nullable|string',
            'emails' => 'nullable|array',
            'emails.*.email' => 'required_with:emails|email',
            'emails.*.type' => 'required_with:emails|string',
            'social_profiles' => 'nullable|array',
            'websites' => 'nullable|array',
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
            'first_name.max' => 'First name must not exceed 50 characters.',
            'last_name.max' => 'Last name must not exceed 50 characters.',
            'company.max' => 'Company name must not exceed 255 characters.',
            'job_title.max' => 'Job title must not exceed 100 characters.',
            'phone.max' => 'Phone number must not exceed 60 characters.',
            'zip.max' => 'ZIP code must not exceed 12 characters.',
            'country.max' => 'Country code must be 2 characters.',
            'emails.*.email.required_with' => 'Email address is required when adding email entries.',
            'emails.*.email.email' => 'Please provide a valid email address.',
            'emails.*.type.required_with' => 'Email type is required when adding email entries.',
        ];
    }
}
