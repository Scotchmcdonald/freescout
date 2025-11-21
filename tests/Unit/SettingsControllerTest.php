<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\UnitTestCase;

class SettingsControllerTest extends UnitTestCase
{

    public function test_controller_can_be_instantiated(): void
    {
        $controller = new SettingsController;

        $this->assertInstanceOf(SettingsController::class, $controller);
    }

    public function test_index_method_exists(): void
    {
        $controller = new SettingsController;

        $this->assertTrue(method_exists($controller, 'index'));
    }

    public function test_update_method_exists(): void
    {
        $controller = new SettingsController;

        $this->assertTrue(method_exists($controller, 'update'));
    }

    public function test_email_method_exists(): void
    {
        $controller = new SettingsController;

        $this->assertTrue(method_exists($controller, 'email'));
    }

    public function test_settings_controller_validates_company_name(): void
    {
        $rules = [
            'company_name' => 'nullable|string|max:255',
        ];

        // Valid data
        $validData = ['company_name' => 'Valid Company Name'];
        $validator = Validator::make($validData, $rules);
        $this->assertFalse($validator->fails());

        // Invalid data - too long
        $invalidData = ['company_name' => str_repeat('a', 256)];
        $validator = Validator::make($invalidData, $rules);
        $this->assertTrue($validator->fails());
    }

    public function test_settings_controller_validates_next_ticket_number(): void
    {
        $rules = [
            'next_ticket' => 'nullable|integer|min:1',
        ];

        // Valid data
        $validData = ['next_ticket' => 100];
        $validator = Validator::make($validData, $rules);
        $this->assertFalse($validator->fails());

        // Invalid data - zero
        $invalidData = ['next_ticket' => 0];
        $validator = Validator::make($invalidData, $rules);
        $this->assertTrue($validator->fails());

        // Invalid data - negative
        $invalidData = ['next_ticket' => -5];
        $validator = Validator::make($invalidData, $rules);
        $this->assertTrue($validator->fails());
    }

    public function test_settings_controller_validates_email_driver(): void
    {
        $rules = [
            'mail_driver' => 'required|string|in:smtp,sendmail,mailgun,ses,postmark',
        ];

        // Valid data
        $validData = ['mail_driver' => 'smtp'];
        $validator = Validator::make($validData, $rules);
        $this->assertFalse($validator->fails());

        // Invalid data - unsupported driver
        $invalidData = ['mail_driver' => 'invalid_driver'];
        $validator = Validator::make($invalidData, $rules);
        $this->assertTrue($validator->fails());
    }

    public function test_settings_controller_validates_email_address_format(): void
    {
        $rules = [
            'mail_from_address' => 'required|email',
        ];

        // Valid data
        $validData = ['mail_from_address' => 'support@example.com'];
        $validator = Validator::make($validData, $rules);
        $this->assertFalse($validator->fails());

        // Invalid data - invalid email
        $invalidData = ['mail_from_address' => 'not-an-email'];
        $validator = Validator::make($invalidData, $rules);
        $this->assertTrue($validator->fails());
    }

    public function test_settings_controller_validates_mail_port_is_integer(): void
    {
        $rules = [
            'mail_port' => 'nullable|integer',
        ];

        // Valid data
        $validData = ['mail_port' => 587];
        $validator = Validator::make($validData, $rules);
        $this->assertFalse($validator->fails());

        // Invalid data - string
        $invalidData = ['mail_port' => 'not-a-number'];
        $validator = Validator::make($invalidData, $rules);
        $this->assertTrue($validator->fails());
    }

    public function test_settings_controller_validates_encryption_type(): void
    {
        $rules = [
            'mail_encryption' => 'nullable|string|in:tls,ssl',
        ];

        // Valid data - tls
        $validData = ['mail_encryption' => 'tls'];
        $validator = Validator::make($validData, $rules);
        $this->assertFalse($validator->fails());

        // Valid data - ssl
        $validData = ['mail_encryption' => 'ssl'];
        $validator = Validator::make($validData, $rules);
        $this->assertFalse($validator->fails());

        // Invalid data - unsupported encryption
        $invalidData = ['mail_encryption' => 'none'];
        $validator = Validator::make($invalidData, $rules);
        $this->assertTrue($validator->fails());
    }

    // ===== Tests for 0% Coverage Method: sendTestAlert =====

    public function test_send_test_alert_method_exists(): void
    {
        $controller = new SettingsController();
        
        $this->assertTrue(method_exists($controller, 'sendTestAlert'));
    }

    public function test_send_test_alert_returns_error_with_no_recipients(): void
    {
        \Mail::fake();
        
        $controller = new SettingsController();
        $request = \Illuminate\Http\Request::create('/settings/test-alert', 'POST');
        $request->merge(['alert_recipients' => '']);

        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('sendTestAlert');
        $method->setAccessible(true);

        $response = $method->invoke($controller, $request);

        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
        $this->assertEquals('No recipients configured for alerts.', session('error'));
    }

    public function test_send_test_alert_sends_email_to_valid_recipient(): void
    {
        \Mail::fake();
        
        $controller = new SettingsController();
        $request = \Illuminate\Http\Request::create('/settings/test-alert', 'POST');
        $request->merge(['alert_recipients' => 'test@example.com']);

        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('sendTestAlert');
        $method->setAccessible(true);

        $response = $method->invoke($controller, $request);

        \Mail::assertSent(\App\Mail\Alert::class, function ($mail) {
            return $mail->hasTo('test@example.com');
        });

        $this->assertStringContainsString('Test alert sent successfully', session('success'));
    }

    public function test_send_test_alert_sends_to_multiple_recipients(): void
    {
        \Mail::fake();
        
        $controller = new SettingsController();
        $request = \Illuminate\Http\Request::create('/settings/test-alert', 'POST');
        $request->merge(['alert_recipients' => "test1@example.com\ntest2@example.com"]);

        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('sendTestAlert');
        $method->setAccessible(true);

        $response = $method->invoke($controller, $request);

        \Mail::assertSent(\App\Mail\Alert::class, 2);
        $this->assertStringContainsString('2 recipient(s)', session('success'));
    }

    public function test_send_test_alert_skips_invalid_email_addresses(): void
    {
        \Mail::fake();
        
        $controller = new SettingsController();
        $request = \Illuminate\Http\Request::create('/settings/test-alert', 'POST');
        $request->merge(['alert_recipients' => "valid@example.com\ninvalid-email\ntest@example.com"]);

        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('sendTestAlert');
        $method->setAccessible(true);

        $response = $method->invoke($controller, $request);

        // Should only send to 2 valid emails
        \Mail::assertSent(\App\Mail\Alert::class, 2);
    }

    public function test_send_test_alert_handles_mail_exception(): void
    {
        \Mail::shouldReceive('to')->andThrow(new \Exception('Mail server error'));
        
        $controller = new SettingsController();
        $request = \Illuminate\Http\Request::create('/settings/test-alert', 'POST');
        $request->merge(['alert_recipients' => 'test@example.com']);

        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('sendTestAlert');
        $method->setAccessible(true);

        $response = $method->invoke($controller, $request);

        $this->assertStringContainsString('Failed to send test alert', session('error'));
    }

    public function test_send_test_alert_trims_whitespace_from_recipients(): void
    {
        \Mail::fake();
        
        $controller = new SettingsController();
        $request = \Illuminate\Http\Request::create('/settings/test-alert', 'POST');
        $request->merge(['alert_recipients' => "  test@example.com  \n  another@example.com  "]);

        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('sendTestAlert');
        $method->setAccessible(true);

        $response = $method->invoke($controller, $request);

        \Mail::assertSent(\App\Mail\Alert::class, 2);
        \Mail::assertSent(\App\Mail\Alert::class, function ($mail) {
            return $mail->hasTo('test@example.com') || $mail->hasTo('another@example.com');
        });
    }
}
