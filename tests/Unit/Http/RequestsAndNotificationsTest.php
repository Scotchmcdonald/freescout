<?php


declare(strict_types=1);
namespace Tests\Unit\Http;

use Tests\UnitTestCase;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class RequestsAndNotificationsTest extends UnitTestCase
{
    // ========================================
    // LoginRequest Tests (30+ tests)
    // ========================================

    public function test_login_request_can_be_instantiated(): void
    {
        $request = new LoginRequest();
        $this->assertInstanceOf(LoginRequest::class, $request);
    }

    public function test_login_request_requires_email(): void
    {
        $request = new LoginRequest();
        $rules = $request->rules();
        
        $this->assertArrayHasKey('email', $rules);
        $this->assertContains('required', $rules['email']);
    }

    public function test_login_request_requires_string_email(): void
    {
        $request = new LoginRequest();
        $rules = $request->rules();
        
        $this->assertContains('string', $rules['email']);
    }

    public function test_login_request_validates_email_format(): void
    {
        $request = new LoginRequest();
        $rules = $request->rules();
        
        $this->assertContains('email', $rules['email']);
    }

    public function test_login_request_requires_password(): void
    {
        $request = new LoginRequest();
        $rules = $request->rules();
        
        $this->assertArrayHasKey('password', $rules);
        $this->assertContains('required', $rules['password']);
    }

    public function test_login_request_requires_string_password(): void
    {
        $request = new LoginRequest();
        $rules = $request->rules();
        
        $this->assertContains('string', $rules['password']);
    }

    public function test_login_request_validates_valid_email_format(): void
    {
        $data = [
            'email' => 'test@example.com',
            'password' => 'password123'
        ];
        
        $validator = Validator::make($data, (new LoginRequest())->rules());
        
        $this->assertFalse($validator->fails());
    }

    public function test_login_request_fails_with_invalid_email(): void
    {
        $data = [
            'email' => 'invalid-email',
            'password' => 'password123'
        ];
        
        $validator = Validator::make($data, (new LoginRequest())->rules());
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }

    public function test_login_request_fails_without_email(): void
    {
        $data = [
            'password' => 'password123'
        ];
        
        $validator = Validator::make($data, (new LoginRequest())->rules());
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }

    public function test_login_request_fails_without_password(): void
    {
        $data = [
            'email' => 'test@example.com'
        ];
        
        $validator = Validator::make($data, (new LoginRequest())->rules());
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('password', $validator->errors()->toArray());
    }

    public function test_login_request_fails_with_empty_email(): void
    {
        $data = [
            'email' => '',
            'password' => 'password123'
        ];
        
        $validator = Validator::make($data, (new LoginRequest())->rules());
        
        $this->assertTrue($validator->fails());
    }

    public function test_login_request_fails_with_empty_password(): void
    {
        $data = [
            'email' => 'test@example.com',
            'password' => ''
        ];
        
        $validator = Validator::make($data, (new LoginRequest())->rules());
        
        $this->assertTrue($validator->fails());
    }

    public function test_login_request_accepts_long_email(): void
    {
        $data = [
            'email' => str_repeat('a', 50) . '@example.com',
            'password' => 'password123'
        ];
        
        $validator = Validator::make($data, (new LoginRequest())->rules());
        
        // Should pass as long as it's a valid email format
        $this->assertFalse($validator->fails());
    }

    public function test_login_request_accepts_long_password(): void
    {
        $data = [
            'email' => 'test@example.com',
            'password' => str_repeat('a', 100)
        ];
        
        $validator = Validator::make($data, (new LoginRequest())->rules());
        
        $this->assertFalse($validator->fails());
    }

    public function test_login_request_accepts_special_characters_in_password(): void
    {
        $data = [
            'email' => 'test@example.com',
            'password' => 'P@ssw0rd!#$%'
        ];
        
        $validator = Validator::make($data, (new LoginRequest())->rules());
        
        $this->assertFalse($validator->fails());
    }

    public function test_login_request_accepts_numbers_in_password(): void
    {
        $data = [
            'email' => 'test@example.com',
            'password' => '1234567890'
        ];
        
        $validator = Validator::make($data, (new LoginRequest())->rules());
        
        $this->assertFalse($validator->fails());
    }

    public function test_login_request_handles_remember_me_field(): void
    {
        $data = [
            'email' => 'test@example.com',
            'password' => 'password123',
            'remember' => true
        ];
        
        $validator = Validator::make($data, (new LoginRequest())->rules());
        
        $this->assertFalse($validator->fails());
    }

    public function test_login_request_email_case_insensitive(): void
    {
        $data = [
            'email' => 'TEST@EXAMPLE.COM',
            'password' => 'password123'
        ];
        
        $validator = Validator::make($data, (new LoginRequest())->rules());
        
        $this->assertFalse($validator->fails());
    }

    public function test_login_request_fails_with_numeric_email(): void
    {
        $data = [
            'email' => 123456,
            'password' => 'password123'
        ];
        
        $validator = Validator::make($data, (new LoginRequest())->rules());
        
        $this->assertTrue($validator->fails());
    }

    public function test_login_request_fails_with_array_email(): void
    {
        $data = [
            'email' => ['test@example.com'],
            'password' => 'password123'
        ];
        
        $validator = Validator::make($data, (new LoginRequest())->rules());
        
        $this->assertTrue($validator->fails());
    }

    public function test_login_request_fails_with_array_password(): void
    {
        $data = [
            'email' => 'test@example.com',
            'password' => ['password123']
        ];
        
        $validator = Validator::make($data, (new LoginRequest())->rules());
        
        $this->assertTrue($validator->fails());
    }

    public function test_login_request_accepts_subdomain_emails(): void
    {
        $data = [
            'email' => 'test@subdomain.example.com',
            'password' => 'password123'
        ];
        
        $validator = Validator::make($data, (new LoginRequest())->rules());
        
        $this->assertFalse($validator->fails());
    }

    public function test_login_request_accepts_plus_in_email(): void
    {
        $data = [
            'email' => 'test+tag@example.com',
            'password' => 'password123'
        ];
        
        $validator = Validator::make($data, (new LoginRequest())->rules());
        
        $this->assertFalse($validator->fails());
    }

    public function test_login_request_accepts_dots_in_email(): void
    {
        $data = [
            'email' => 'first.last@example.com',
            'password' => 'password123'
        ];
        
        $validator = Validator::make($data, (new LoginRequest())->rules());
        
        $this->assertFalse($validator->fails());
    }

    public function test_login_request_fails_with_email_without_at_sign(): void
    {
        $data = [
            'email' => 'testexample.com',
            'password' => 'password123'
        ];
        
        $validator = Validator::make($data, (new LoginRequest())->rules());
        
        $this->assertTrue($validator->fails());
    }

    public function test_login_request_fails_with_email_without_domain(): void
    {
        $data = [
            'email' => 'test@',
            'password' => 'password123'
        ];
        
        $validator = Validator::make($data, (new LoginRequest())->rules());
        
        $this->assertTrue($validator->fails());
    }

    public function test_login_request_fails_with_email_without_username(): void
    {
        $data = [
            'email' => '@example.com',
            'password' => 'password123'
        ];
        
        $validator = Validator::make($data, (new LoginRequest())->rules());
        
        $this->assertTrue($validator->fails());
    }

    public function test_login_request_handles_whitespace_in_fields(): void
    {
        $data = [
            'email' => '  test@example.com  ',
            'password' => '  password123  '
        ];
        
        $validator = Validator::make($data, (new LoginRequest())->rules());
        
        // Validation should fail because unit tests don't run TrimStrings middleware
        $this->assertTrue($validator->fails());
    }

    public function test_login_request_custom_error_messages(): void
    {
        $request = new LoginRequest();
        $messages = $request->messages();
        
        // Check if custom messages are defined
        $this->assertIsArray($messages);
    }

    public function test_login_request_authorize_returns_true(): void
    {
        $request = new LoginRequest();
        
        // LoginRequest should allow all users to attempt login
        $this->assertTrue($request->authorize());
    }

    // ========================================
    // Additional Request Validation Tests (30+ tests)
    // ========================================

    public function test_validator_handles_required_fields(): void
    {
        $rules = ['name' => 'required|string'];
        $data = [];
        
        $validator = Validator::make($data, $rules);
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    public function test_validator_handles_string_validation(): void
    {
        $rules = ['name' => 'string'];
        $data = ['name' => 'John Doe'];
        
        $validator = Validator::make($data, $rules);
        
        $this->assertFalse($validator->fails());
    }

    public function test_validator_fails_string_with_number(): void
    {
        $rules = ['name' => 'string'];
        $data = ['name' => 12345];
        
        $validator = Validator::make($data, $rules);
        
        $this->assertTrue($validator->fails());
    }

    public function test_validator_handles_min_length(): void
    {
        $rules = ['password' => 'min:8'];
        $data = ['password' => '12345'];
        
        $validator = Validator::make($data, $rules);
        
        $this->assertTrue($validator->fails());
    }

    public function test_validator_passes_min_length(): void
    {
        $rules = ['password' => 'min:8'];
        $data = ['password' => '12345678'];
        
        $validator = Validator::make($data, $rules);
        
        $this->assertFalse($validator->fails());
    }

    public function test_validator_handles_max_length(): void
    {
        $rules = ['name' => 'max:10'];
        $data = ['name' => 'VeryLongNameThatExceedsLimit'];
        
        $validator = Validator::make($data, $rules);
        
        $this->assertTrue($validator->fails());
    }

    public function test_validator_passes_max_length(): void
    {
        $rules = ['name' => 'max:10'];
        $data = ['name' => 'Short'];
        
        $validator = Validator::make($data, $rules);
        
        $this->assertFalse($validator->fails());
    }

    public function test_validator_handles_unique_validation(): void
    {
        User::factory()->create(['email' => 'unique@example.com']);
        
        $rules = ['email' => 'unique:users,email'];
        $data = ['email' => 'unique@example.com'];
        
        $validator = Validator::make($data, $rules);
        
        $this->assertTrue($validator->fails());
    }

    public function test_validator_passes_unique_validation(): void
    {
        $rules = ['email' => 'unique:users,email'];
        $data = ['email' => 'new@example.com'];
        
        $validator = Validator::make($data, $rules);
        
        $this->assertFalse($validator->fails());
    }

    public function test_validator_handles_confirmed_validation(): void
    {
        $rules = ['password' => 'confirmed'];
        $data = [
            'password' => 'secret123',
            'password_confirmation' => 'different'
        ];
        
        $validator = Validator::make($data, $rules);
        
        $this->assertTrue($validator->fails());
    }

    public function test_validator_passes_confirmed_validation(): void
    {
        $rules = ['password' => 'confirmed'];
        $data = [
            'password' => 'secret123',
            'password_confirmation' => 'secret123'
        ];
        
        $validator = Validator::make($data, $rules);
        
        $this->assertFalse($validator->fails());
    }

    public function test_validator_handles_in_validation(): void
    {
        $rules = ['role' => 'in:admin,user'];
        $data = ['role' => 'invalid'];
        
        $validator = Validator::make($data, $rules);
        
        $this->assertTrue($validator->fails());
    }

    public function test_validator_passes_in_validation(): void
    {
        $rules = ['role' => 'in:admin,user'];
        $data = ['role' => 'admin'];
        
        $validator = Validator::make($data, $rules);
        
        $this->assertFalse($validator->fails());
    }

    public function test_validator_handles_numeric_validation(): void
    {
        $rules = ['age' => 'numeric'];
        $data = ['age' => 'not-a-number'];
        
        $validator = Validator::make($data, $rules);
        
        $this->assertTrue($validator->fails());
    }

    public function test_validator_passes_numeric_validation(): void
    {
        $rules = ['age' => 'numeric'];
        $data = ['age' => 25];
        
        $validator = Validator::make($data, $rules);
        
        $this->assertFalse($validator->fails());
    }

    public function test_validator_handles_integer_validation(): void
    {
        $rules = ['count' => 'integer'];
        $data = ['count' => 'not-integer'];
        
        $validator = Validator::make($data, $rules);
        
        $this->assertTrue($validator->fails());
    }

    public function test_validator_passes_integer_validation(): void
    {
        $rules = ['count' => 'integer'];
        $data = ['count' => 10];
        
        $validator = Validator::make($data, $rules);
        
        $this->assertFalse($validator->fails());
    }

    public function test_validator_handles_boolean_validation(): void
    {
        $rules = ['active' => 'boolean'];
        $data = ['active' => 'yes'];
        
        $validator = Validator::make($data, $rules);
        
        $this->assertTrue($validator->fails());
    }

    public function test_validator_passes_boolean_validation(): void
    {
        $rules = ['active' => 'boolean'];
        $data = ['active' => true];
        
        $validator = Validator::make($data, $rules);
        
        $this->assertFalse($validator->fails());
    }

    public function test_validator_handles_date_validation(): void
    {
        $rules = ['birthdate' => 'date'];
        $data = ['birthdate' => 'not-a-date'];
        
        $validator = Validator::make($data, $rules);
        
        $this->assertTrue($validator->fails());
    }

    public function test_validator_passes_date_validation(): void
    {
        $rules = ['birthdate' => 'date'];
        $data = ['birthdate' => '2023-01-01'];
        
        $validator = Validator::make($data, $rules);
        
        $this->assertFalse($validator->fails());
    }

    public function test_validator_handles_array_validation(): void
    {
        $rules = ['tags' => 'array'];
        $data = ['tags' => 'not-an-array'];
        
        $validator = Validator::make($data, $rules);
        
        $this->assertTrue($validator->fails());
    }

    public function test_validator_passes_array_validation(): void
    {
        $rules = ['tags' => 'array'];
        $data = ['tags' => ['tag1', 'tag2']];
        
        $validator = Validator::make($data, $rules);
        
        $this->assertFalse($validator->fails());
    }

    public function test_validator_handles_nullable_fields(): void
    {
        $rules = ['optional' => 'nullable|string'];
        $data = [];
        
        $validator = Validator::make($data, $rules);
        
        $this->assertFalse($validator->fails());
    }

    public function test_validator_handles_sometimes_validation(): void
    {
        $rules = ['optional' => 'sometimes|required'];
        $data = [];
        
        $validator = Validator::make($data, $rules);
        
        $this->assertFalse($validator->fails());
    }

    public function test_validator_fails_sometimes_when_present(): void
    {
        $rules = ['optional' => 'sometimes|required'];
        $data = ['optional' => ''];
        
        $validator = Validator::make($data, $rules);
        
        $this->assertTrue($validator->fails());
    }

    public function test_validator_handles_regex_validation(): void
    {
        $rules = ['code' => 'regex:/^[A-Z]{3}$/'];
        $data = ['code' => 'abc'];
        
        $validator = Validator::make($data, $rules);
        
        $this->assertTrue($validator->fails());
    }

    public function test_validator_passes_regex_validation(): void
    {
        $rules = ['code' => 'regex:/^[A-Z]{3}$/'];
        $data = ['code' => 'ABC'];
        
        $validator = Validator::make($data, $rules);
        
        $this->assertFalse($validator->fails());
    }

    public function test_validator_handles_multiple_rules(): void
    {
        $rules = ['name' => 'required|string|min:3|max:50'];
        $data = ['name' => 'AB'];
        
        $validator = Validator::make($data, $rules);
        
        $this->assertTrue($validator->fails());
    }

    public function test_validator_passes_multiple_rules(): void
    {
        $rules = ['name' => 'required|string|min:3|max:50'];
        $data = ['name' => 'John Doe'];
        
        $validator = Validator::make($data, $rules);
        
        $this->assertFalse($validator->fails());
    }
}
