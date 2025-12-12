<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Controllers\CustomerController;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\CoversMethod;
use Tests\TestCase;

/**
 * Tests for customer social profiles and website management.
 */
#[CoversMethod(CustomerController::class, 'ajaxAddSocialProfile')]
#[CoversMethod(CustomerController::class, 'ajaxDeleteSocialProfile')]
#[CoversMethod(CustomerController::class, 'ajaxAddWebsite')]
#[CoversMethod(CustomerController::class, 'ajaxDeleteWebsite')]
class CustomerSocialWebsiteTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->adminUser = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->customer = Customer::factory()->create();
    }

    // ====================
    // SOCIAL PROFILE TESTS
    // ====================

    public function test_add_twitter_profile(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('customers.ajax'), [
                'action' => 'add_social_profile',
                'customer_id' => $this->customer->id,
                'type' => 'twitter',
                'value' => '@username',
            ]);

        $response->assertJson(['success' => true]);
        
        $this->customer->refresh();
        $this->assertEquals('@username', $this->customer->social_profiles['twitter']);
    }

    public function test_add_facebook_profile(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('customers.ajax'), [
                'action' => 'add_social_profile',
                'customer_id' => $this->customer->id,
                'type' => 'facebook',
                'value' => 'facebook.com/user123',
            ]);

        $response->assertJson(['success' => true]);
        
        $this->customer->refresh();
        $this->assertEquals('facebook.com/user123', $this->customer->social_profiles['facebook']);
    }

    public function test_add_linkedin_profile(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('customers.ajax'), [
                'action' => 'add_social_profile',
                'customer_id' => $this->customer->id,
                'type' => 'linkedin',
                'value' => 'linkedin.com/in/user123',
            ]);

        $response->assertJson(['success' => true]);
        
        $this->customer->refresh();
        $this->assertEquals('linkedin.com/in/user123', $this->customer->social_profiles['linkedin']);
    }

    public function test_update_existing_social_profile(): void
    {
        // First add a profile
        $this->customer->update(['social_profiles' => ['twitter' => '@oldhandle']]);
        
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('customers.ajax'), [
                'action' => 'add_social_profile',
                'customer_id' => $this->customer->id,
                'type' => 'twitter',
                'value' => '@newhandle',
            ]);

        $response->assertJson(['success' => true]);
        
        $this->customer->refresh();
        $this->assertEquals('@newhandle', $this->customer->social_profiles['twitter']);
    }

    public function test_delete_social_profile(): void
    {
        // First add profiles
        $this->customer->update(['social_profiles' => [
            'twitter' => '@user',
            'facebook' => 'fb/user',
        ]]);
        
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('customers.ajax'), [
                'action' => 'delete_social_profile',
                'customer_id' => $this->customer->id,
                'type' => 'twitter',
            ]);

        $response->assertJson(['success' => true]);
        
        $this->customer->refresh();
        $this->assertArrayNotHasKey('twitter', $this->customer->social_profiles);
        $this->assertEquals('fb/user', $this->customer->social_profiles['facebook']);
    }

    public function test_invalid_social_profile_type_rejected(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('customers.ajax'), [
                'action' => 'add_social_profile',
                'customer_id' => $this->customer->id,
                'type' => 'invalid_type',
                'value' => 'somevalue',
            ]);

        $response->assertStatus(422);
    }

    // ====================
    // WEBSITE TESTS
    // ====================

    public function test_add_website(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('customers.ajax'), [
                'action' => 'add_website',
                'customer_id' => $this->customer->id,
                'url' => 'https://example.com',
            ]);

        $response->assertJson(['success' => true]);
        
        $this->customer->refresh();
        $this->assertContains('https://example.com', $this->customer->websites);
    }

    public function test_add_multiple_websites(): void
    {
        // Add first website
        $this->actingAs($this->adminUser)
            ->postJson(route('customers.ajax'), [
                'action' => 'add_website',
                'customer_id' => $this->customer->id,
                'url' => 'https://example.com',
            ]);

        // Add second website
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('customers.ajax'), [
                'action' => 'add_website',
                'customer_id' => $this->customer->id,
                'url' => 'https://another.com',
            ]);

        $response->assertJson(['success' => true]);
        
        $this->customer->refresh();
        $this->assertCount(2, $this->customer->websites);
        $this->assertContains('https://example.com', $this->customer->websites);
        $this->assertContains('https://another.com', $this->customer->websites);
    }

    public function test_duplicate_website_not_added(): void
    {
        // Add website
        $this->customer->update(['websites' => ['https://example.com']]);
        
        // Try to add the same website again
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('customers.ajax'), [
                'action' => 'add_website',
                'customer_id' => $this->customer->id,
                'url' => 'https://example.com',
            ]);

        $response->assertJson(['success' => true]);
        
        $this->customer->refresh();
        $this->assertCount(1, $this->customer->websites);
    }

    public function test_delete_website(): void
    {
        // Add websites
        $this->customer->update(['websites' => [
            'https://first.com',
            'https://second.com',
            'https://third.com',
        ]]);
        
        // Delete the second website (index 1)
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('customers.ajax'), [
                'action' => 'delete_website',
                'customer_id' => $this->customer->id,
                'website_index' => 1,
            ]);

        $response->assertJson(['success' => true]);
        
        $this->customer->refresh();
        $this->assertCount(2, $this->customer->websites);
        $this->assertContains('https://first.com', $this->customer->websites);
        $this->assertContains('https://third.com', $this->customer->websites);
        $this->assertNotContains('https://second.com', $this->customer->websites);
    }

    public function test_invalid_url_rejected(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('customers.ajax'), [
                'action' => 'add_website',
                'customer_id' => $this->customer->id,
                'url' => 'not-a-valid-url',
            ]);

        $response->assertStatus(422);
    }

    // ====================
    // AUTHORIZATION TESTS
    // ====================

    public function test_unauthenticated_user_cannot_add_social_profile(): void
    {
        $response = $this->postJson(route('customers.ajax'), [
            'action' => 'add_social_profile',
            'customer_id' => $this->customer->id,
            'type' => 'twitter',
            'value' => '@user',
        ]);

        $response->assertStatus(401);
    }

    public function test_unauthenticated_user_cannot_add_website(): void
    {
        $response = $this->postJson(route('customers.ajax'), [
            'action' => 'add_website',
            'customer_id' => $this->customer->id,
            'url' => 'https://example.com',
        ]);

        $response->assertStatus(401);
    }
}
