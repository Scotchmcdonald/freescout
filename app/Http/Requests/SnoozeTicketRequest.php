<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SnoozeTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'add_hours' => 'nullable|integer|min:1|max:168',
            'add_days' => 'nullable|integer|min:1|max:30',
            'to_next_week' => 'nullable|boolean',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'add_hours' => $this->input('add_hours', $this->input('addHours')),
            'add_days' => $this->input('add_days', $this->input('addDays')),
            'to_next_week' => $this->boolean('to_next_week') || $this->boolean('nextWeek') || $this->boolean('toNextWeek'),
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $hasHours = is_numeric($this->input('add_hours'));
            $hasDays = is_numeric($this->input('add_days'));
            $hasNextWeek = $this->boolean('to_next_week');

            $selectedCount = ($hasHours ? 1 : 0) + ($hasDays ? 1 : 0) + ($hasNextWeek ? 1 : 0);

            if ($selectedCount !== 1) {
                $validator->errors()->add('snooze', 'Provide exactly one snooze option: addHours, addDays, or toNextWeek.');
            }
        });
    }
}
