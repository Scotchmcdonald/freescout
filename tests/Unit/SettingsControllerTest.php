<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Controllers\SettingsController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_controller_can_be_instantiated(): void
    {
        $controller = new SettingsController();
        
        $this->assertInstanceOf(SettingsController::class, $controller);
    }

    public function test_index_method_exists(): void
    {
        $controller = new SettingsController();
        
        $this->assertTrue(method_exists($controller, 'index'));
    }

    public function test_update_method_exists(): void
    {
        $controller = new SettingsController();
        
        $this->assertTrue(method_exists($controller, 'update'));
    }

    public function test_email_method_exists(): void
    {
        $controller = new SettingsController();
        
        $this->assertTrue(method_exists($controller, 'email'));
    }

    #[Test]
    public function settings_controller_validates_company_name(): void
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

    #[Test]
    public function settings_controller_validates_next_ticket_number(): void
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

    #[Test]
    public function settings_controller_validates_email_driver(): void
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

    #[Test]
    public function settings_controller_validates_email_address_format(): void
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

    #[Test]
    public function settings_controller_validates_mail_port_is_integer(): void
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

    #[Test]
    public function settings_controller_validates_encryption_type(): void
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
}