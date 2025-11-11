<?php

declare(strict_types=1);

namespace Tests\Unit\Requests;

use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class LoginRequestTest extends TestCase
{
    public function test_login_request_authorization_returns_true(): void
    {
        $request = new LoginRequest;

        $this->assertTrue($request->authorize());
    }

    public function test_login_request_validation_rules(): void
    {
        $request = new LoginRequest;
        $rules = $request->rules();

        $this->assertArrayHasKey('email', $rules);
        $this->assertArrayHasKey('password', $rules);
        $this->assertContains('required', $rules['email']);
        $this->assertContains('string', $rules['email']);
        $this->assertContains('email', $rules['email']);
        $this->assertContains('required', $rules['password']);
        $this->assertContains('string', $rules['password']);
    }

    public function test_login_request_validates_email_format(): void
    {
        $request = new LoginRequest;
        $validator = Validator::make(
            ['email' => 'invalid-email', 'password' => 'password123'],
            $request->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }

    public function test_login_request_requires_email(): void
    {
        $request = new LoginRequest;
        $validator = Validator::make(
            ['password' => 'password123'],
            $request->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }

    public function test_login_request_requires_password(): void
    {
        $request = new LoginRequest;
        $validator = Validator::make(
            ['email' => 'test@example.com'],
            $request->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('password', $validator->errors()->toArray());
    }

    public function test_login_request_accepts_valid_credentials(): void
    {
        $request = new LoginRequest;
        $validator = Validator::make(
            ['email' => 'test@example.com', 'password' => 'password123'],
            $request->rules()
        );

        $this->assertFalse($validator->fails());
    }

    public function test_throttle_key_is_generated_from_email_and_ip(): void
    {
        $request = LoginRequest::create('/login', 'POST', [
            'email' => 'TEST@Example.com',
        ], [], [], ['REMOTE_ADDR' => '127.0.0.1']);

        $throttleKey = $request->throttleKey();

        $this->assertIsString($throttleKey);
        $this->assertStringContainsString('test@example.com', $throttleKey);
        $this->assertStringContainsString('127.0.0.1', $throttleKey);
    }

    public function test_throttle_key_lowercases_email(): void
    {
        $request = LoginRequest::create('/login', 'POST', [
            'email' => 'UPPERCASE@EXAMPLE.COM',
        ], [], [], ['REMOTE_ADDR' => '192.168.1.1']);

        $throttleKey = $request->throttleKey();

        $this->assertStringContainsString('uppercase@example.com', $throttleKey);
        $this->assertStringNotContainsString('UPPERCASE@EXAMPLE.COM', $throttleKey);
    }
}
