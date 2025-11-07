<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Email;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_has_first_name_attribute(): void
    {
        $customer = new Customer(['first_name' => 'John']);
        
        $this->assertEquals('John', $customer->first_name);
    }

    public function test_customer_has_last_name_attribute(): void
    {
        $customer = new Customer(['last_name' => 'Doe']);
        
        $this->assertEquals('Doe', $customer->last_name);
    }

    public function test_customer_has_company_attribute(): void
    {
        $customer = new Customer(['company' => 'Acme Corp']);
        
        $this->assertEquals('Acme Corp', $customer->company);
    }

    public function test_customer_has_phones_attribute(): void
    {
        $customer = new Customer(['phones' => ['555-1234']]);
        
        $this->assertEquals(['555-1234'], $customer->phones);
    }

    public function test_customer_get_full_name_with_both_names(): void
    {
        $customer = new Customer([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
        
        $this->assertEquals('John Doe', $customer->getFullName());
    }

    public function test_customer_get_full_name_with_only_first_name(): void
    {
        $customer = new Customer([
            'first_name' => 'John',
            'last_name' => '',
        ]);
        
        $this->assertEquals('John', $customer->getFullName());
    }

    public function test_customer_get_full_name_with_only_last_name(): void
    {
        $customer = new Customer([
            'first_name' => '',
            'last_name' => 'Doe',
        ]);
        
        $this->assertEquals('Doe', $customer->getFullName());
    }

    public function test_customer_has_notes_attribute(): void
    {
        $customer = new Customer(['notes' => 'Important customer']);
        
        $this->assertEquals('Important customer', $customer->notes);
    }

    public function test_customer_has_address_attribute(): void
    {
        $customer = new Customer(['address' => '123 Main St']);
        
        $this->assertEquals('123 Main St', $customer->address);
    }

    public function test_customer_has_city_attribute(): void
    {
        $customer = new Customer(['city' => 'Springfield']);
        
        $this->assertEquals('Springfield', $customer->city);
    }

    /** @test */
    public function customer_has_conversations_relationship(): void
    {
        // Arrange
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create([
            'customer_id' => $customer->id,
        ]);

        // Act
        $result = $customer->conversations;

        // Assert
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
        $this->assertTrue($result->contains($conversation));
        $this->assertEquals(1, $result->count());
    }

    /** @test */
    public function customer_full_name_accessor_works(): void
    {
        // Arrange
        $customer = new Customer([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
        ]);

        // Act
        $fullName = $customer->full_name;

        // Assert
        $this->assertEquals('Jane Smith', $fullName);
    }

    /** @test */
    public function customer_full_name_accessor_trims_whitespace(): void
    {
        // Arrange
        $customer = new Customer([
            'first_name' => 'Jane',
            'last_name' => '',
        ]);

        // Act
        $fullName = $customer->full_name;

        // Assert
        $this->assertEquals('Jane', $fullName);
        $this->assertStringNotContainsString('  ', $fullName);
    }

    /** @test */
    public function customer_has_emails_relationship(): void
    {
        // Arrange
        $customer = Customer::factory()->create();
        $email = Email::factory()->create([
            'customer_id' => $customer->id,
            'email' => 'test@example.com',
        ]);

        // Act
        $result = $customer->emails;

        // Assert
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
        $this->assertTrue($result->contains($email));
        $this->assertEquals('test@example.com', $result->first()->email);
    }

    /** @test */
    public function customer_get_main_email_returns_primary_email(): void
    {
        // Arrange
        $customer = Customer::factory()->create();
        Email::factory()->create([
            'customer_id' => $customer->id,
            'email' => 'primary@example.com',
            'type' => 1, // Primary
        ]);
        Email::factory()->create([
            'customer_id' => $customer->id,
            'email' => 'secondary@example.com',
            'type' => 2, // Secondary
        ]);

        // Act
        $mainEmail = $customer->getMainEmail();

        // Assert
        $this->assertEquals('primary@example.com', $mainEmail);
    }

    /** @test */
    public function customer_get_main_email_returns_first_email_if_no_primary(): void
    {
        // Arrange
        $customer = Customer::factory()->create();
        Email::factory()->create([
            'customer_id' => $customer->id,
            'email' => 'only@example.com',
            'type' => 2, // Secondary
        ]);

        // Act
        $mainEmail = $customer->getMainEmail();

        // Assert
        $this->assertEquals('only@example.com', $mainEmail);
    }

    /** @test */
    public function customer_get_main_email_returns_null_when_no_emails(): void
    {
        // Arrange
        $customer = Customer::factory()->create();

        // Act
        $mainEmail = $customer->getMainEmail();

        // Assert
        $this->assertNull($mainEmail);
    }

    /** @test */
    public function customer_has_threads_relationship(): void
    {
        // Arrange
        $customer = Customer::factory()->create();

        // Act
        $result = $customer->threads();

        // Assert
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $result);
    }

    /** @test */
    public function customer_has_channels_relationship(): void
    {
        // Arrange
        $customer = Customer::factory()->create();

        // Act
        $result = $customer->channels();

        // Assert
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $result);
    }

    /** @test */
    public function customer_get_first_name_returns_empty_string_when_null(): void
    {
        // Arrange
        $customer = new Customer(['first_name' => null]);

        // Act
        $firstName = $customer->getFirstName();

        // Assert
        $this->assertEquals('', $firstName);
    }

    /** @test */
    public function customer_get_first_name_returns_actual_value(): void
    {
        // Arrange
        $customer = new Customer(['first_name' => 'John']);

        // Act
        $firstName = $customer->getFirstName();

        // Assert
        $this->assertEquals('John', $firstName);
    }

    /** @test */
    public function customer_primary_email_attribute_returns_primary_email(): void
    {
        // Arrange
        $customer = Customer::factory()->create();
        Email::factory()->create([
            'customer_id' => $customer->id,
            'email' => 'primary@example.com',
            'type' => 1, // Primary
        ]);
        Email::factory()->create([
            'customer_id' => $customer->id,
            'email' => 'secondary@example.com',
            'type' => 2, // Secondary
        ]);

        // Act
        $primaryEmail = $customer->primary_email;

        // Assert
        $this->assertEquals('primary@example.com', $primaryEmail);
    }

    /** @test */
    public function customer_primary_email_attribute_returns_null_when_no_primary(): void
    {
        // Arrange
        $customer = Customer::factory()->create();
        Email::factory()->create([
            'customer_id' => $customer->id,
            'email' => 'secondary@example.com',
            'type' => 2, // Secondary
        ]);

        // Act
        $primaryEmail = $customer->primary_email;

        // Assert
        $this->assertNull($primaryEmail);
    }

    /** @test */
    public function customer_casts_attributes_correctly(): void
    {
        // Arrange
        $customer = new Customer([
            'phones' => ['555-1234', '555-5678'],
            'websites' => ['https://example.com'],
            'social_profiles' => ['twitter' => '@user'],
        ]);

        // Assert
        $this->assertIsArray($customer->phones);
        $this->assertIsArray($customer->websites);
        $this->assertIsArray($customer->social_profiles);
    }

    /** @test */
    public function customer_fillable_includes_all_expected_fields(): void
    {
        // Arrange
        $expectedFields = [
            'first_name',
            'last_name',
            'company',
            'job_title',
            'photo_url',
            'photo_type',
            'channel',
            'channel_id',
            'phones',
            'websites',
            'social_profiles',
            'address',
            'city',
            'state',
            'zip',
            'country',
            'notes',
        ];

        // Act
        $customer = new Customer();
        $fillable = $customer->getFillable();

        // Assert
        foreach ($expectedFields as $field) {
            $this->assertContains($field, $fillable, "Field '{$field}' should be fillable");
        }
    }
}
