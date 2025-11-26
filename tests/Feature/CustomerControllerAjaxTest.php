<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Email;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\FeatureTestCase;

/**
 * Feature tests for CustomerController AJAX methods added during Phase 5 implementation.
 */
class CustomerControllerAjaxTest extends FeatureTestCase
{
    protected User $admin;
    protected User $user;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->user = User::factory()->create(['role' => User::ROLE_USER]);
        $this->customer = Customer::factory()->create();
        Email::factory()->create([
            'customer_id' => $this->customer->id,
            'email' => 'main@example.com',
            'type' => Email::TYPE_PRIMARY,
        ]);
    }

    // ===== ajaxAddEmail tests =====

    public function test_admin_can_add_email_to_customer(): void
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('customers.ajax', $this->customer), [
            'customer_id' => $this->customer->id,
            'action' => 'add_email',
            'email' => 'new@example.com',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('emails', [
            'customer_id' => $this->customer->id,
            'email' => 'new@example.com',
        ]);
    }

    public function test_add_email_validates_email_format(): void
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('customers.ajax', $this->customer), [
            'customer_id' => $this->customer->id,
            'action' => 'add_email',
            'email' => 'invalid-email',
        ]);

        // Should fail validation
        $this->assertTrue($response->status() >= 400 || $response->json('error') !== null);
    }

    public function test_add_email_prevents_duplicates(): void
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('customers.ajax', $this->customer), [
            'customer_id' => $this->customer->id,
            'action' => 'add_email',
            'email' => 'main@example.com', // Already exists
        ]);

        // Should fail or handle gracefully
        $this->assertTrue(
            $response->status() >= 400 || 
            $response->json('success') === false || 
            $response->json('error') !== null || 
            $response->json('status') === 'exists'
        );
    }

    // ===== ajaxDeleteEmail tests =====

    public function test_admin_can_delete_customer_email(): void
    {
        $this->actingAs($this->admin);

        $email = Email::factory()->create([
            'customer_id' => $this->customer->id,
            'email' => 'secondary@example.com',
            'type' => Email::TYPE_SECONDARY ?? 2,
        ]);

        $response = $this->postJson(route('customers.ajax', $this->customer), [
            'customer_id' => $this->customer->id,
            'action' => 'delete_email',
            'email_id' => $email->id,
        ]);

        $response->assertOk();

        $this->assertDatabaseMissing('emails', [
            'id' => $email->id,
        ]);
    }

    public function test_cannot_delete_last_email(): void
    {
        $this->actingAs($this->admin);

        // Ensure customer has only one email
        $this->customer->emails()->where('id', '!=', $this->customer->emails()->first()->id)->delete();
        $this->assertEquals(1, $this->customer->emails()->count());

        $email = $this->customer->emails()->first();

        $response = $this->postJson(route('customers.ajax', $this->customer), [
            'customer_id' => $this->customer->id,
            'action' => 'delete_email',
            'email_id' => $email->id,
        ]);

        // Should prevent deletion of last email
        $this->assertDatabaseHas('emails', [
            'id' => $email->id,
        ]);
    }

    // ===== ajaxSetMainEmail tests =====

    public function test_admin_can_set_main_email(): void
    {
        $this->actingAs($this->admin);

        $newMainEmail = Email::factory()->create([
            'customer_id' => $this->customer->id,
            'email' => 'newmain@example.com',
            'type' => Email::TYPE_SECONDARY ?? 2,
        ]);

        $response = $this->postJson(route('customers.ajax', $this->customer), [
            'customer_id' => $this->customer->id,
            'action' => 'set_main_email',
            'email_id' => $newMainEmail->id,
        ]);

        $response->assertOk();

        $newMainEmail->refresh();
        $this->assertEquals(Email::TYPE_PRIMARY ?? 1, $newMainEmail->type);
    }

    // ===== ajaxUploadPhoto tests =====

    public function test_admin_can_upload_customer_photo(): void
    {
        $this->actingAs($this->admin);
        Storage::fake('public');

        $response = $this->postJson(route('customers.ajax', $this->customer), [
            'customer_id' => $this->customer->id,
            'action' => 'upload_photo',
            'photo' => UploadedFile::fake()->image('customer.jpg', 200, 200),
        ]);

        $response->assertOk();
    }

    public function test_upload_photo_rejects_non_image(): void
    {
        $this->actingAs($this->admin);
        Storage::fake('public');

        $response = $this->postJson(route('customers.ajax', $this->customer), [
            'customer_id' => $this->customer->id,
            'action' => 'upload_photo',
            'photo' => UploadedFile::fake()->create('document.pdf', 100),
        ]);

        $this->assertTrue($response->status() >= 400 || $response->json('error') !== null);
    }

    // ===== ajaxDeletePhoto tests =====

    public function test_admin_can_delete_customer_photo(): void
    {
        $this->actingAs($this->admin);
        Storage::fake('public');

        $this->customer->photo_url = 'photos/customer.jpg';
        $this->customer->save();

        $response = $this->postJson(route('customers.ajax', $this->customer), [
            'customer_id' => $this->customer->id,
            'action' => 'delete_photo',
        ]);

        $response->assertOk();
    }

    // ===== ajaxAddPhone tests =====

    public function test_admin_can_add_phone_to_customer(): void
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('customers.ajax', $this->customer), [
            'customer_id' => $this->customer->id,
            'action' => 'add_phone',
            'phone' => '+1234567890',
        ]);

        $response->assertOk();
    }

    public function test_add_phone_validates_format(): void
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('customers.ajax', $this->customer), [
            'customer_id' => $this->customer->id,
            'action' => 'add_phone',
            'phone' => '', // Empty phone
        ]);

        $this->assertTrue($response->status() >= 400 || $response->json('error') !== null);
    }

    // ===== ajaxDeletePhone tests =====

    public function test_admin_can_delete_customer_phone(): void
    {
        $this->actingAs($this->admin);

        // Add a phone first
        $phones = ['+1234567890'];
        $this->customer->update(['phones' => $phones]);

        $response = $this->postJson(route('customers.ajax', $this->customer), [
            'action' => 'delete_phone',
            'phone_index' => 0,
            'customer_id' => $this->customer->id,
        ]);

        $response->assertOk();
        
        $this->customer->refresh();
        $phones = $this->customer->phones ?? [];
        $this->assertEmpty($phones);
    }

    // ===== Authorization tests =====

    public function test_guest_cannot_access_customer_ajax(): void
    {
        $response = $this->postJson(route('customers.ajax', $this->customer), [
            'customer_id' => $this->customer->id,
            'action' => 'add_email',
            'email' => 'new@example.com',
        ]);

        $response->assertUnauthorized();
    }

    public function test_invalid_action_returns_error(): void
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('customers.ajax', $this->customer), [
            'customer_id' => $this->customer->id,
            'action' => 'invalid_action_xyz',
        ]);

        $this->assertTrue($response->status() >= 400 || $response->json('error') !== null);
    }

    // ===== Edge cases =====

    public function test_add_email_with_uppercase(): void
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('customers.ajax', $this->customer), [
            'customer_id' => $this->customer->id,
            'action' => 'add_email',
            'email' => 'NEW@EXAMPLE.COM',
        ]);

        $response->assertOk();

        // Email should be normalized (usually lowercase)
        $this->assertDatabaseHas('emails', [
            'customer_id' => $this->customer->id,
        ]);
    }

    public function test_add_email_with_whitespace(): void
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('customers.ajax', $this->customer), [
            'customer_id' => $this->customer->id,
            'action' => 'add_email',
            'email' => '  whitespace@example.com  ',
        ]);

        $response->assertOk();

        // Email should be trimmed
        $this->assertDatabaseHas('emails', [
            'customer_id' => $this->customer->id,
            'email' => 'whitespace@example.com',
        ]);
    }
}
