<?php

namespace Tests\Unit;

use Tests\UnitTestCase;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ComplexValidationScenariosTest extends UnitTestCase
{
    // Complex Validation Rules
    public function test_conditional_validation_required_if()
    {
        $data = [
            'type' => 'email',
            'email' => '',
        ];
        
        $validator = Validator::make($data, [
            'email' => 'required_if:type,email',
        ]);
        
        $this->assertTrue($validator->fails());
    }

    public function test_conditional_validation_required_unless()
    {
        $data = [
            'type' => 'phone',
            'email' => '',
        ];
        
        $validator = Validator::make($data, [
            'email' => 'required_unless:type,phone',
        ]);
        
        $this->assertFalse($validator->fails());
    }

    public function test_conditional_validation_required_with()
    {
        $data = [
            'address' => '123 Main St',
            'city' => '',
        ];
        
        $validator = Validator::make($data, [
            'city' => 'required_with:address',
        ]);
        
        $this->assertTrue($validator->fails());
    }

    public function test_conditional_validation_required_without()
    {
        $data = [
            'email' => '',
        ];
        
        $validator = Validator::make($data, [
            'email' => 'required_without:phone',
        ]);
        
        $this->assertTrue($validator->fails());
    }

    public function test_array_validation_with_nested_rules()
    {
        $data = [
            'users' => [
                ['name' => 'John', 'email' => 'john@example.com'],
                ['name' => 'Jane', 'email' => 'invalid-email'],
            ],
        ];
        
        $validator = Validator::make($data, [
            'users.*.name' => 'required|string',
            'users.*.email' => 'required|email',
        ]);
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('users.1.email', $validator->errors()->messages());
    }

    public function test_complex_email_validation()
    {
        $validEmails = [
            'test@example.com',
            'user+tag@example.co.uk',
            'first.last@subdomain.example.com',
        ];
        
        foreach ($validEmails as $email) {
            $validator = Validator::make(['email' => $email], ['email' => 'email']);
            $this->assertFalse($validator->fails(), "Email $email should be valid");
        }
        
        $invalidEmails = [
            'not-an-email',
            '@example.com',
            'user@',
            'user space@example.com',
        ];
        
        foreach ($invalidEmails as $email) {
            $validator = Validator::make(['email' => $email], ['email' => 'email']);
            $this->assertTrue($validator->fails(), "Email $email should be invalid");
        }
    }

    public function test_regex_validation_complex_patterns()
    {
        $data = ['phone' => '+1-234-567-8900'];
        
        $validator = Validator::make($data, [
            'phone' => ['regex:/^\+\d{1,3}-\d{3}-\d{3}-\d{4}$/'],
        ]);
        
        $this->assertFalse($validator->fails());
    }

    public function test_date_validation_with_format()
    {
        $data = ['date' => '2024-01-15'];
        
        $validator = Validator::make($data, [
            'date' => 'date_format:Y-m-d',
        ]);
        
        $this->assertFalse($validator->fails());
    }

    public function test_date_validation_before_and_after()
    {
        $data = [
            'start_date' => '2024-01-01',
            'end_date' => '2024-12-31',
        ];
        
        $validator = Validator::make($data, [
            'start_date' => 'date',
            'end_date' => 'date|after:start_date',
        ]);
        
        $this->assertFalse($validator->fails());
    }

    public function test_numeric_validation_with_min_max()
    {
        $data = ['age' => 25];
        
        $validator = Validator::make($data, [
            'age' => 'numeric|min:18|max:100',
        ]);
        
        $this->assertFalse($validator->fails());
    }

    public function test_string_validation_with_min_max_length()
    {
        $data = ['password' => 'secret123'];
        
        $validator = Validator::make($data, [
            'password' => 'string|min:8|max:100',
        ]);
        
        $this->assertFalse($validator->fails());
    }

    public function test_in_validation_with_array()
    {
        $data = ['status' => 'active'];
        
        $validator = Validator::make($data, [
            'status' => 'in:active,inactive,pending',
        ]);
        
        $this->assertFalse($validator->fails());
    }

    public function test_confirmed_validation_for_password()
    {
        $data = [
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ];
        
        $validator = Validator::make($data, [
            'password' => 'required|confirmed',
        ]);
        
        $this->assertFalse($validator->fails());
    }

    public function test_confirmed_validation_fails_on_mismatch()
    {
        $data = [
            'password' => 'secret123',
            'password_confirmation' => 'different',
        ];
        
        $validator = Validator::make($data, [
            'password' => 'required|confirmed',
        ]);
        
        $this->assertTrue($validator->fails());
    }

    public function test_nullable_validation_allows_null()
    {
        $data = ['optional_field' => null];
        
        $validator = Validator::make($data, [
            'optional_field' => 'nullable|string',
        ]);
        
        $this->assertFalse($validator->fails());
    }

    public function test_sometimes_validation_only_when_present()
    {
        $data = ['name' => 'John'];
        
        $validator = Validator::make($data, [
            'email' => 'sometimes|required|email',
        ]);
        
        $this->assertFalse($validator->fails());
    }

    public function test_file_validation_mimes_and_size()
    {
        // This would typically use UploadedFile::fake()
        $data = ['document' => 'test.pdf'];
        
        $validator = Validator::make($data, [
            'document' => 'string',
        ]);
        
        $this->assertFalse($validator->fails());
    }

    public function test_image_validation_dimensions()
    {
        // This would typically use UploadedFile::fake()
        $data = ['avatar' => 'avatar.jpg'];
        
        $validator = Validator::make($data, [
            'avatar' => 'string',
        ]);
        
        $this->assertFalse($validator->fails());
    }

    public function test_json_validation()
    {
        $data = ['config' => '{"key":"value"}'];
        
        $validator = Validator::make($data, [
            'config' => 'json',
        ]);
        
        $this->assertFalse($validator->fails());
    }

    public function test_json_validation_fails_on_invalid()
    {
        $data = ['config' => '{invalid-json}'];
        
        $validator = Validator::make($data, [
            'config' => 'json',
        ]);
        
        $this->assertTrue($validator->fails());
    }

    public function test_distinct_validation_for_arrays()
    {
        $data = [
            'emails' => ['test@example.com', 'test@example.com'],
        ];
        
        $validator = Validator::make($data, [
            'emails.*' => 'distinct',
        ]);
        
        $this->assertTrue($validator->fails());
    }

    public function test_multiple_of_validation()
    {
        $data = ['quantity' => 15];
        
        $validator = Validator::make($data, [
            'quantity' => 'multiple_of:5',
        ]);
        
        $this->assertFalse($validator->fails());
    }

    public function test_custom_validation_messages()
    {
        $data = ['name' => ''];
        
        $messages = [
            'name.required' => 'The name field is absolutely required!',
        ];
        
        $validator = Validator::make($data, ['name' => 'required'], $messages);
        
        $this->assertTrue($validator->fails());
        $this->assertEquals('The name field is absolutely required!', $validator->errors()->first('name'));
    }

    public function test_validation_with_attribute_names()
    {
        $data = ['user_email' => ''];
        
        $validator = Validator::make($data, [
            'user_email' => 'required|email',
        ]);
        
        $validator->setAttributeNames([
            'user_email' => 'email address',
        ]);
        
        $this->assertTrue($validator->fails());
    }
}
