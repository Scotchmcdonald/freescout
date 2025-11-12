<?php

declare(strict_types=1);

namespace Tests\Unit\Requests;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Tests\UnitTestCase;

class ProfileUpdateRequestTest extends UnitTestCase
{

    public function test_profile_update_request_validation_rules(): void
    {
        $user = User::factory()->create();

        $request = ProfileUpdateRequest::create('/profile', 'PUT');
        $request->setUserResolver(fn () => $user);

        $rules = $request->rules();

        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('first_name', $rules);
        $this->assertArrayHasKey('last_name', $rules);
        $this->assertArrayHasKey('email', $rules);
    }

    public function test_name_is_required(): void
    {
        $user = User::factory()->create();

        $request = ProfileUpdateRequest::create('/profile', 'PUT');
        $request->setUserResolver(fn () => $user);

        $validator = Validator::make(
            ['email' => 'test@example.com'],
            $request->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    public function test_email_is_required(): void
    {
        $user = User::factory()->create();

        $request = ProfileUpdateRequest::create('/profile', 'PUT');
        $request->setUserResolver(fn () => $user);

        $validator = Validator::make(
            ['name' => 'Test User'],
            $request->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }

    public function test_email_must_be_valid_format(): void
    {
        $user = User::factory()->create();

        $request = ProfileUpdateRequest::create('/profile', 'PUT');
        $request->setUserResolver(fn () => $user);

        $validator = Validator::make(
            ['name' => 'Test User', 'email' => 'invalid-email'],
            $request->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }

    public function test_email_must_be_unique_except_current_user(): void
    {
        $user = User::factory()->create(['email' => 'current@example.com']);
        $otherUser = User::factory()->create(['email' => 'other@example.com']);

        $request = ProfileUpdateRequest::create('/profile', 'PUT');
        $request->setUserResolver(fn () => $user);

        // Should allow keeping own email
        $validator = Validator::make(
            ['name' => 'Test User', 'email' => 'current@example.com'],
            $request->rules()
        );
        $this->assertFalse($validator->fails());

        // Should not allow using another user's email
        $validator2 = Validator::make(
            ['name' => 'Test User', 'email' => 'other@example.com'],
            $request->rules()
        );
        $this->assertTrue($validator2->fails());
    }

    public function test_name_is_split_into_first_and_last_name(): void
    {
        $user = User::factory()->create();

        $request = ProfileUpdateRequest::create('/profile', 'PUT', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
        $request->setUserResolver(fn () => $user);
        $request->setContainer($this->app);

        // Trigger prepareForValidation
        $request->validateResolved();

        $this->assertEquals('John', $request->input('first_name'));
        $this->assertEquals('Doe', $request->input('last_name'));
    }

    public function test_name_split_handles_single_name(): void
    {
        $user = User::factory()->create();

        $request = ProfileUpdateRequest::create('/profile', 'PUT', [
            'name' => 'John',
            'email' => 'john@example.com',
        ]);
        $request->setUserResolver(fn () => $user);
        $request->setContainer($this->app);

        // Trigger prepareForValidation
        $request->validateResolved();

        $this->assertEquals('John', $request->input('first_name'));
        $this->assertEquals('', $request->input('last_name'));
    }

    public function test_name_split_handles_multiple_spaces(): void
    {
        $user = User::factory()->create();

        $request = ProfileUpdateRequest::create('/profile', 'PUT', [
            'name' => 'John Peter Doe',
            'email' => 'john@example.com',
        ]);
        $request->setUserResolver(fn () => $user);
        $request->setContainer($this->app);

        // Trigger prepareForValidation
        $request->validateResolved();

        $this->assertEquals('John', $request->input('first_name'));
        $this->assertEquals('Peter Doe', $request->input('last_name'));
    }

    public function test_first_name_not_overridden_if_provided(): void
    {
        $user = User::factory()->create();

        $request = ProfileUpdateRequest::create('/profile', 'PUT', [
            'name' => 'John Doe',
            'first_name' => 'Jane',
            'email' => 'jane@example.com',
        ]);
        $request->setUserResolver(fn () => $user);
        $request->setContainer($this->app);

        // Trigger prepareForValidation
        $request->validateResolved();

        // first_name should not be overridden if it's already provided
        $this->assertEquals('Jane', $request->input('first_name'));
    }

    public function test_last_name_can_be_nullable(): void
    {
        $user = User::factory()->create();

        $request = ProfileUpdateRequest::create('/profile', 'PUT');
        $request->setUserResolver(fn () => $user);

        $validator = Validator::make(
            [
                'name' => 'Test User',
                'first_name' => 'Test',
                'last_name' => null,
                'email' => 'test@example.com',
            ],
            $request->rules()
        );

        $this->assertFalse($validator->fails());
    }
}
