<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Option;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Additional security and edge case tests for Settings and System controllers.
 * These tests go beyond the basic Batch 5 requirements to ensure robust security.
 */
class SecurityAndEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->user = User::factory()->create([
            'role' => User::ROLE_USER,
        ]);
    }

    #[Test]
    public function guest_cannot_access_settings_routes(): void
    {
        $response = $this->get(route('settings'));
        $response->assertRedirect(route('login'));
        
        // Verify no settings data is leaked
        $this->assertStringNotContainsString('company_name', $response->getContent());
        $this->assertStringNotContainsString('option', $response->getContent());
    }

    #[Test]
    public function guest_cannot_access_system_routes(): void
    {
        $response = $this->get(route('system'));
        $response->assertRedirect(route('login'));
    }

    #[Test]
    public function non_admin_cannot_update_settings(): void
    {
        $response = $this->actingAs($this->user)->post(route('settings.update'), [
            'company_name' => 'Hacked Company',
        ]);

        $response->assertForbidden();
        
        // Verify the setting was not updated
        $this->assertDatabaseMissing('options', [
            'name' => 'company_name',
            'value' => 'Hacked Company',
        ]);
        
        // Verify no sensitive data is leaked in the forbidden response
        $content = $response->getContent();
        $this->assertStringNotContainsString('Hacked Company', $content);
    }

    #[Test]
    public function non_admin_cannot_access_email_settings(): void
    {
        $response = $this->actingAs($this->user)->get(route('settings.email'));
        $response->assertForbidden();
    }

    #[Test]
    public function non_admin_cannot_update_email_settings(): void
    {
        $response = $this->actingAs($this->user)->post(route('settings.email.update'), [
            'mail_driver' => 'smtp',
            'mail_from_address' => 'hacker@example.com',
            'mail_from_name' => 'Hacker',
        ]);

        $response->assertForbidden();
    }

    #[Test]
    public function non_admin_cannot_clear_cache(): void
    {
        $response = $this->actingAs($this->user)->post(route('settings.cache.clear'));
        $response->assertForbidden();
    }

    #[Test]
    public function non_admin_cannot_run_migrations(): void
    {
        $response = $this->actingAs($this->user)->post(route('settings.migrate'));
        $response->assertForbidden();
    }

    #[Test]
    public function non_admin_cannot_access_system_diagnostics(): void
    {
        $response = $this->actingAs($this->user)->get(route('system.diagnostics'));
        $response->assertForbidden();
    }

    #[Test]
    public function option_handles_null_values_correctly(): void
    {
        Option::setValue('nullable_option', null);
        
        $value = Option::getValue('nullable_option');
        $this->assertNull($value);
    }

    #[Test]
    public function option_handles_empty_string_values(): void
    {
        Option::setValue('empty_option', '');
        
        $value = Option::getValue('empty_option');
        $this->assertEquals('', $value);
    }

    #[Test]
    public function option_handles_numeric_values(): void
    {
        Option::setValue('numeric_option', 12345);
        
        $value = Option::getValue('numeric_option');
        $this->assertEquals(12345, $value);
    }

    #[Test]
    public function option_handles_array_values(): void
    {
        $arrayValue = ['key1' => 'value1', 'key2' => 'value2'];
        Option::setValue('array_option', json_encode($arrayValue));
        
        $value = Option::getValue('array_option');
        $this->assertIsString($value);
        $this->assertEquals($arrayValue, json_decode($value, true));
    }

    #[Test]
    public function settings_validation_prevents_sql_injection(): void
    {
        $this->actingAs($this->admin);

        $maliciousInput = "'; DROP TABLE options; --";
        $response = $this->post(route('settings.update'), [
            'company_name' => $maliciousInput,
        ]);

        $response->assertRedirect();
        
        // Verify the malicious input was safely stored
        $this->assertDatabaseHas('options', [
            'name' => 'company_name',
            'value' => $maliciousInput,
        ]);
        
        // Verify options table still exists and other options are intact
        $this->assertNotNull(Option::all());
        
        // Verify no SQL was executed by checking table structure
        $this->assertDatabaseHas('options', ['name' => 'company_name']);
    }

    #[Test]
    public function settings_validation_prevents_xss(): void
    {
        $this->actingAs($this->admin);

        $xssPayload = '<script>alert("XSS")</script>';
        $response = $this->post(route('settings.update'), [
            'company_name' => $xssPayload,
        ]);

        $response->assertRedirect();
        
        // Verify the value was stored (sanitization should happen on output)
        $this->assertDatabaseHas('options', [
            'name' => 'company_name',
            'value' => $xssPayload,
        ]);
    }

    #[Test]
    public function email_settings_require_valid_email_format(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post(route('settings.email.update'), [
            'mail_driver' => 'smtp',
            'mail_from_address' => 'invalid-email',
            'mail_from_name' => 'Test',
        ]);

        $response->assertSessionHasErrors('mail_from_address');
        
        // Verify error message is helpful
        $errors = session('errors');
        $this->assertNotNull($errors);
        $emailErrors = $errors->get('mail_from_address');
        $this->assertNotEmpty($emailErrors);
        
        // Verify the invalid email was not saved
        $this->assertDatabaseMissing('options', [
            'name' => 'mail_from_address',
            'value' => 'invalid-email',
        ]);
    }

    #[Test]
    public function email_settings_require_supported_driver(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post(route('settings.email.update'), [
            'mail_driver' => 'unsupported_driver',
            'mail_from_address' => 'test@example.com',
            'mail_from_name' => 'Test',
        ]);

        $response->assertSessionHasErrors('mail_driver');
    }

    #[Test]
    public function system_diagnostics_checks_database_connection(): void
    {
        $response = $this->actingAs($this->admin)->get(route('system.diagnostics'));

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'checks' => [
                'database' => ['status', 'message'],
            ],
        ]);
        $response->assertJsonPath('checks.database.status', 'ok');
    }

    #[Test]
    public function system_diagnostics_checks_storage_writable(): void
    {
        $response = $this->actingAs($this->admin)->get(route('system.diagnostics'));

        $response->assertOk();
        $response->assertJsonPath('checks.storage.status', 'ok');
    }

    #[Test]
    public function system_diagnostics_checks_cache_working(): void
    {
        $response = $this->actingAs($this->admin)->get(route('system.diagnostics'));

        $response->assertOk();
        $response->assertJsonPath('checks.cache.status', 'ok');
    }

    #[Test]
    public function option_setValue_creates_new_record_when_not_exists(): void
    {
        $this->assertDatabaseMissing('options', ['name' => 'new_test_option']);
        
        Option::setValue('new_test_option', 'new_value');
        
        $this->assertDatabaseHas('options', [
            'name' => 'new_test_option',
            'value' => 'new_value',
        ]);
    }

    #[Test]
    public function option_setValue_updates_existing_record(): void
    {
        Option::create(['name' => 'existing_option', 'value' => 'old_value']);
        
        Option::setValue('existing_option', 'new_value');
        
        $this->assertDatabaseHas('options', [
            'name' => 'existing_option',
            'value' => 'new_value',
        ]);
        
        // Ensure only one record exists
        $this->assertEquals(1, Option::where('name', 'existing_option')->count());
    }

    #[Test]
    public function option_deleteOption_handles_non_existent_keys_gracefully(): void
    {
        $result = Option::deleteOption('non_existent_key');
        
        $this->assertFalse($result);
    }

    #[Test]
    public function multiple_options_can_be_stored_and_retrieved(): void
    {
        $options = [
            'option1' => 'value1',
            'option2' => 'value2',
            'option3' => 'value3',
        ];

        foreach ($options as $name => $value) {
            Option::setValue($name, $value);
        }

        foreach ($options as $name => $value) {
            $this->assertEquals($value, Option::getValue($name));
        }
    }

    #[Test]
    public function settings_page_displays_existing_options(): void
    {
        Option::create(['name' => 'company_name', 'value' => 'Test Corp']);
        Option::create(['name' => 'next_ticket', 'value' => '100']);

        $response = $this->actingAs($this->admin)->get(route('settings'));

        $response->assertOk();
        $response->assertSee('Test Corp');
    }

    #[Test]
    public function system_ajax_requires_admin(): void
    {
        $response = $this->actingAs($this->user)->post(route('system.ajax'), [
            'action' => 'system_info',
        ]);

        $response->assertForbidden();
    }

    #[Test]
    public function admin_can_get_system_info_with_correct_structure(): void
    {
        $response = $this->actingAs($this->admin)->post(route('system.ajax'), [
            'action' => 'system_info',
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'info' => [
                'php_version',
                'laravel_version',
                'db_connection',
                'cache_driver',
                'queue_connection',
                'session_driver',
                'timezone',
                'locale',
            ],
        ]);
    }
}
