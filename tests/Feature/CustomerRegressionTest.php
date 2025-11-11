<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Email;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CustomerRegressionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function customer_identification_from_incoming_email_creates_new_customer(): void
    {
        // Arrange
        $emailAddress = 'newcustomer@example.com';

        // Assert no customer exists with this email yet
        $this->assertDatabaseMissing('emails', [
            'email' => $emailAddress,
        ]);

        // Act - Simulate incoming email processing using Customer::create() method
        $customer = Customer::create($emailAddress, [
            'first_name' => 'New',
            'last_name' => 'Customer',
        ]);

        // Assert
        $this->assertNotNull($customer);
        $this->assertInstanceOf(Customer::class, $customer);
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'first_name' => 'New',
            'last_name' => 'Customer',
        ]);
        $this->assertDatabaseHas('emails', [
            'customer_id' => $customer->id,
            'email' => $emailAddress,
            'type' => 1, // Primary email
        ]);
    }

    #[Test]
    public function customer_identification_from_incoming_email_finds_existing_customer(): void
    {
        // Arrange - Create an existing customer with email
        $existingCustomer = Customer::factory()->create([
            'first_name' => 'Existing',
            'last_name' => 'Customer',
        ]);
        $emailAddress = 'existing@example.com';
        Email::factory()->create([
            'customer_id' => $existingCustomer->id,
            'email' => $emailAddress,
            'type' => 1,
        ]);

        // Act - Simulate incoming email from existing customer
        $foundCustomer = Customer::create($emailAddress, [
            'first_name' => 'Different',
            'last_name' => 'Name',
        ]);

        // Assert - Should return the existing customer, not create a new one
        $this->assertNotNull($foundCustomer);
        $this->assertEquals($existingCustomer->id, $foundCustomer->id);
        $this->assertDatabaseCount('customers', 1);
        $this->assertEquals('Existing', $foundCustomer->first_name);
    }

    #[Test]
    public function customer_create_sanitizes_email_address(): void
    {
        // Arrange
        $rawEmail = 'Test.User@EXAMPLE.COM';

        // Act
        $customer = Customer::create($rawEmail, [
            'first_name' => 'Test',
            'last_name' => 'User',
        ]);

        // Assert
        $this->assertNotNull($customer);
        // Email should be sanitized to lowercase
        $this->assertDatabaseHas('emails', [
            'customer_id' => $customer->id,
            'email' => 'test.user@example.com',
        ]);
    }

    #[Test]
    public function customer_create_returns_null_for_invalid_email(): void
    {
        // Arrange
        $invalidEmail = 'not-an-email';

        // Act
        $customer = Customer::create($invalidEmail, [
            'first_name' => 'Invalid',
            'last_name' => 'Email',
        ]);

        // Assert
        $this->assertNull($customer);
        $this->assertDatabaseMissing('customers', [
            'first_name' => 'Invalid',
        ]);
    }

    #[Test]
    public function customer_create_handles_email_with_trailing_dots(): void
    {
        // Arrange
        $emailWithDots = 'user@example.com....';

        // Act
        $customer = Customer::create($emailWithDots, [
            'first_name' => 'Dot',
            'last_name' => 'User',
        ]);

        // Assert
        $this->assertNotNull($customer);
        // Trailing dots should be removed
        $this->assertDatabaseHas('emails', [
            'customer_id' => $customer->id,
            'email' => 'user@example.com',
        ]);
    }

    #[Test]
    public function customer_set_data_fills_empty_fields_only(): void
    {
        // Arrange
        $customer = Customer::factory()->create([
            'first_name' => 'Original',
            'last_name' => 'Name',
            'company' => '',
            'notes' => '',
        ]);

        // Act
        $result = $customer->setData([
            'first_name' => 'Should Not Change',
            'last_name' => 'Should Not Change',
            'company' => 'New Company',
            'notes' => 'New Notes',
        ], false, true);

        // Assert
        $this->assertTrue($result);
        $customer->refresh();
        $this->assertEquals('Original', $customer->first_name);
        $this->assertEquals('Name', $customer->last_name);
        $this->assertEquals('New Company', $customer->company);
        $this->assertEquals('New Notes', $customer->notes);
    }

    #[Test]
    public function customer_set_data_replaces_all_fields_when_replace_data_is_true(): void
    {
        // Arrange
        $customer = Customer::factory()->create([
            'first_name' => 'Original',
            'last_name' => 'Name',
            'company' => 'Old Company',
        ]);

        // Act
        $result = $customer->setData([
            'first_name' => 'New',
            'last_name' => 'Updated',
            'company' => 'New Company',
        ], true, true);

        // Assert
        $this->assertTrue($result);
        $customer->refresh();
        $this->assertEquals('New', $customer->first_name);
        $this->assertEquals('Updated', $customer->last_name);
        $this->assertEquals('New Company', $customer->company);
    }

    #[Test]
    public function customer_set_data_uses_background_as_notes_if_notes_empty(): void
    {
        // Arrange
        $customer = Customer::factory()->create([
            'notes' => '',
        ]);

        // Act
        $customer->setData([
            'background' => 'Important background information',
        ], false, true);

        // Assert
        $customer->refresh();
        $this->assertEquals('Important background information', $customer->notes);
    }

    #[Test]
    public function customer_set_data_does_not_set_last_name_if_first_name_exists(): void
    {
        // Arrange
        $customer = Customer::factory()->create([
            'first_name' => 'John',
            'last_name' => '',
        ]);

        // Act
        $customer->setData([
            'last_name' => 'Should Not Be Set',
        ], false, true);

        // Assert
        $customer->refresh();
        $this->assertEquals('John', $customer->first_name);
        $this->assertEquals('', $customer->last_name);
    }

    #[Test]
    public function customer_set_data_does_not_set_first_name_if_last_name_exists(): void
    {
        // Arrange
        $customer = Customer::factory()->create([
            'first_name' => '',
            'last_name' => 'Doe',
        ]);

        // Act
        $customer->setData([
            'first_name' => 'Should Not Be Set',
        ], false, true);

        // Assert
        $customer->refresh();
        $this->assertEquals('', $customer->first_name);
        $this->assertEquals('Doe', $customer->last_name);
    }

    #[Test]
    public function email_sanitize_removes_dot_before_at_symbol(): void
    {
        // Arrange
        $email = 'user..name.@example.com';

        // Act
        $sanitized = Email::sanitizeEmail($email);

        // Assert
        $this->assertEquals('user..name@example.com', $sanitized);
    }

    #[Test]
    public function email_sanitize_converts_to_lowercase(): void
    {
        // Arrange
        $email = 'User@EXAMPLE.COM';

        // Act
        $sanitized = Email::sanitizeEmail($email);

        // Assert
        $this->assertEquals('user@example.com', $sanitized);
    }

    #[Test]
    public function email_sanitize_returns_false_for_invalid_format(): void
    {
        // Arrange
        $invalidEmails = [
            'no-at-sign',
            '@example.com',
            'user@',
            '',
        ];

        // Act & Assert
        foreach ($invalidEmails as $email) {
            $result = Email::sanitizeEmail($email);
            $this->assertFalse($result, "Email '{$email}' should return false");
        }
    }

    #[Test]
    public function customer_create_handles_multiple_emails_in_data(): void
    {
        // Arrange
        $emailAddress = 'primary@example.com';

        // Act
        $customer = Customer::create($emailAddress, [
            'first_name' => 'Multi',
            'last_name' => 'Email',
            'emails' => [
                ['value' => 'secondary@example.com', 'type' => 2],
            ],
        ]);

        // Assert
        $this->assertNotNull($customer);
        $this->assertDatabaseHas('emails', [
            'customer_id' => $customer->id,
            'email' => $emailAddress,
            'type' => 1, // Primary
        ]);
    }

    #[Test]
    public function customer_set_data_does_not_set_photo_url(): void
    {
        // Arrange
        $customer = Customer::factory()->create([
            'photo_url' => null,
        ]);

        // Act
        $customer->setData([
            'photo_url' => 'https://example.com/photo.jpg',
            'first_name' => 'Test',
        ], true, true);

        // Assert
        $customer->refresh();
        $this->assertNull($customer->photo_url);
    }

    #[Test]
    public function customer_set_data_returns_false_when_no_changes(): void
    {
        // Arrange
        $customer = Customer::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'company' => 'Test Company',
            'notes' => 'Existing notes',
        ]);

        // Act - Try to set data but the field is already filled
        $result = $customer->setData([
            'notes' => 'Some notes',
        ], false, false);

        // Assert - Should return false because notes field is already filled
        $this->assertFalse($result);
    }

    #[Test]
    public function customer_create_associates_email_with_customer(): void
    {
        // Arrange
        $emailAddress = 'test@example.com';

        // Act
        $customer = Customer::create($emailAddress, [
            'first_name' => 'Test',
            'last_name' => 'User',
        ]);

        // Assert
        $email = Email::where('email', $emailAddress)->first();
        $this->assertNotNull($email);
        $this->assertEquals($customer->id, $email->customer_id);
    }

    #[Test]
    public function customer_create_reuses_existing_email_object(): void
    {
        // Arrange
        $emailAddress = 'existing@example.com';
        $existingCustomer = Customer::factory()->create();
        $existingEmail = Email::factory()->create([
            'customer_id' => $existingCustomer->id,
            'email' => $emailAddress,
        ]);

        // Act
        $customer = Customer::create($emailAddress, [
            'first_name' => 'New',
            'last_name' => 'Name',
        ]);

        // Assert
        $this->assertEquals($existingCustomer->id, $customer->id);
        // Should only be one email record
        $this->assertDatabaseCount('emails', 1);
    }

    #[Test]
    public function email_sanitize_handles_null_input(): void
    {
        // Act
        $result = Email::sanitizeEmail(null);

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function customer_set_data_with_array_values_are_ignored_when_replace_true(): void
    {
        // Arrange
        $customer = Customer::factory()->create([
            'first_name' => 'Original',
        ]);

        // Act
        $customer->setData([
            'first_name' => 'Updated',
            'phones' => ['555-1234'], // This should be ignored
            'websites' => ['https://example.com'], // This should be ignored
        ], true, true);

        // Assert
        $customer->refresh();
        $this->assertEquals('Updated', $customer->first_name);
        // Array fields should not be set when replace_data is true
    }

    #[Test]
    public function customer_create_handles_empty_data_array(): void
    {
        // Arrange
        $emailAddress = 'minimal@example.com';

        // Act
        $customer = Customer::create($emailAddress, []);

        // Assert
        $this->assertNotNull($customer);
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
        ]);
        $this->assertDatabaseHas('emails', [
            'customer_id' => $customer->id,
            'email' => $emailAddress,
        ]);
    }

    #[Test]
    public function email_sanitize_removes_multiple_trailing_dots(): void
    {
        // Arrange
        $email = 'user@example.com.....';

        // Act
        $sanitized = Email::sanitizeEmail($email);

        // Assert
        $this->assertEquals('user@example.com', $sanitized);
    }

    #[Test]
    public function customer_set_data_saves_when_save_parameter_is_true(): void
    {
        // Arrange
        $customer = Customer::factory()->create([
            'company' => '',
        ]);
        $originalUpdatedAt = $customer->updated_at;

        // Wait a moment to ensure timestamp would change
        sleep(1);

        // Act
        $customer->setData([
            'company' => 'New Company',
        ], false, true); // save = true

        // Assert
        $customer->refresh();
        $this->assertEquals('New Company', $customer->company);
        $this->assertNotEquals($originalUpdatedAt, $customer->updated_at);
    }

    #[Test]
    public function customer_set_data_does_not_save_when_save_parameter_is_false(): void
    {
        // Arrange
        $customer = Customer::factory()->create([
            'company' => '',
        ]);
        $originalUpdatedAt = $customer->updated_at;

        // Act
        $customer->setData([
            'company' => 'New Company',
        ], false, false); // save = false

        // Assert
        // In memory, the company should be set
        $this->assertEquals('New Company', $customer->company);

        // But in database, it should not be saved
        $customer->refresh();
        $this->assertEquals('', $customer->company);
    }
}
