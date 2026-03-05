<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ValidateSmtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route protected by 'can:manage_settings' middleware.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'out_server'     => ['required', 'string', 'max:255'],
            'out_port'       => ['required', 'integer', 'min:1', 'max:65535'],
            'email'          => ['required', 'email', 'max:255'],
            'out_encryption' => ['nullable', 'integer', 'in:0,1,2'],
            'out_username'   => ['nullable', 'string', 'max:255'],
            'out_password'   => ['nullable', 'string', 'max:1024'],
        ];
    }
}
