<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route protected by ['can:manage_rbac'] middleware.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name'  => ['required', 'string', 'max:255', Rule::unique('roles', 'name')],
            'label' => ['nullable', 'string', 'max:255'],
            'scope' => ['nullable', 'string', 'in:internal,client'],
        ];
    }
}
