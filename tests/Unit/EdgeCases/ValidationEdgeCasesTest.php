<?php

declare(strict_types=1);

namespace Tests\Unit\EdgeCases;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ValidationEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_validation_rejects_invalid_formats(): void
    {
        $invalidEmails = [
            'notanemail',
            '@example.com',
            'user@',
        ];

        foreach ($invalidEmails as $email) {
            $validator = Validator::make(
                ['email' => $email],
                ['email' => 'required|email']
            );

            $this->assertTrue($validator->fails(), "Failed to reject invalid email: {$email}");
        }
    }

    public function test_email_validation_accepts_valid_formats(): void
    {
        $validEmails = [
            'user@example.com',
            'user.name@example.com',
            'user+tag@example.co.uk',
            'user_name@example-domain.com',
        ];

        foreach ($validEmails as $email) {
            $validator = Validator::make(
                ['email' => $email],
                ['email' => 'required|email']
            );

            $this->assertFalse($validator->fails(), "Failed to accept valid email: {$email}");
        }
    }

    public function test_string_length_validation_boundary_conditions(): void
    {
        // Test max length boundary
        $exactLength = str_repeat('a', 255);
        $tooLong = str_repeat('a', 256);

        $validValidator = Validator::make(
            ['name' => $exactLength],
            ['name' => 'required|string|max:255']
        );
        $this->assertFalse($validValidator->fails());

        $invalidValidator = Validator::make(
            ['name' => $tooLong],
            ['name' => 'required|string|max:255']
        );
        $this->assertTrue($invalidValidator->fails());
    }

    public function test_numeric_validation_boundary_conditions(): void
    {
        $rules = ['value' => 'required|integer|min:1|max:100'];

        // Test boundaries
        $tests = [
            ['value' => 0, 'should_fail' => true],
            ['value' => 1, 'should_fail' => false],
            ['value' => 100, 'should_fail' => false],
            ['value' => 101, 'should_fail' => true],
        ];

        foreach ($tests as $test) {
            $validator = Validator::make(['value' => $test['value']], $rules);
            if ($test['should_fail']) {
                $this->assertTrue($validator->fails(), "Expected {$test['value']} to fail validation");
            } else {
                $this->assertFalse($validator->fails(), "Expected {$test['value']} to pass validation");
            }
        }
    }

    public function test_required_field_validation_with_empty_values(): void
    {
        $emptyValues = ['', null, []];
        $rules = ['field' => 'required'];

        foreach ($emptyValues as $value) {
            $validator = Validator::make(['field' => $value], $rules);
            $this->assertTrue($validator->fails(), 'Required field should reject empty value');
        }
    }

    public function test_nullable_field_accepts_null_value(): void
    {
        $validator = Validator::make(
            ['optional_field' => null],
            ['optional_field' => 'nullable|string']
        );

        $this->assertFalse($validator->fails());
    }

    public function test_unique_validation_with_existing_email(): void
    {
        User::factory()->create(['email' => 'test@example.com']);

        // Same email should fail unique validation
        $validator = Validator::make(
            ['email' => 'test@example.com'],
            ['email' => 'required|email|unique:users,email']
        );

        $this->assertTrue($validator->fails());
    }

    public function test_array_validation_with_empty_array(): void
    {
        $validator = Validator::make(
            ['items' => []],
            ['items' => 'array']
        );

        // Empty array should pass 'array' validation
        $this->assertFalse($validator->fails());
    }

    public function test_boolean_validation_accepts_various_formats(): void
    {
        // Laravel's boolean validation accepts: true, false, 1, 0, "1", "0"
        $validBooleans = [true, false, 1, 0, '1', '0'];

        foreach ($validBooleans as $value) {
            $validator = Validator::make(
                ['flag' => $value],
                ['flag' => 'boolean']
            );
            $this->assertFalse($validator->fails(), "Value " . var_export($value, true) . " should be accepted as boolean");
        }

        // These should fail boolean validation
        $invalidBooleans = ['yes', 'no', 'true', 'false'];
        foreach ($invalidBooleans as $value) {
            $validator = Validator::make(
                ['flag' => $value],
                ['flag' => 'boolean']
            );
            $this->assertTrue($validator->fails(), "Value {$value} should fail boolean validation");
        }
    }
}
