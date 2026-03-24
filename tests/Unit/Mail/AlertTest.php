<?php

declare(strict_types=1);

namespace Tests\Unit\Mail;

use App\Mail\Alert;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use Tests\PureUnitTestCase;

class AlertTest extends PureUnitTestCase
{
    private Container $originalContainer;
    private mixed $originalFacadeApp;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalContainer = Container::getInstance();
        $this->originalFacadeApp = Facade::getFacadeApplication();

        $container = new Container;
        $container->instance('config', new ConfigRepository([
            'app' => [
                'name' => 'FreeScout',
                'url' => 'https://example.com',
            ],
        ]));

        Container::setInstance($container);
        Facade::setFacadeApplication($container);
    }

    protected function tearDown(): void
    {
        Container::setInstance($this->originalContainer);
        Facade::setFacadeApplication($this->originalFacadeApp);

        parent::tearDown();
    }

    public function test_mailable_can_be_instantiated_with_text(): void
    {
        $mailable = new Alert('Test alert message');

        $this->assertInstanceOf(Alert::class, $mailable);
        $this->assertEquals('Test alert message', $mailable->text);
        $this->assertEquals('', $mailable->title);
    }

    public function test_mailable_can_be_instantiated_with_title(): void
    {
        $mailable = new Alert('Test message', 'Important Alert');

        $this->assertEquals('Test message', $mailable->text);
        $this->assertEquals('Important Alert', $mailable->title);
    }

    public function test_envelope_contains_correct_subject_with_title(): void
    {
        $mailable = new Alert('Test message', 'Security Alert');
        $envelope = $mailable->envelope();

        $this->assertStringContainsString('[FreeScout]', $envelope->subject);
        $this->assertStringContainsString('Security Alert', $envelope->subject);
        $this->assertStringContainsString('example.com', $envelope->subject);
    }

    public function test_envelope_uses_default_title_when_empty(): void
    {
        $mailable = new Alert('Test message');
        $envelope = $mailable->envelope();

        $this->assertStringContainsString('Alert', $envelope->subject);
        $this->assertStringContainsString('[FreeScout]', $envelope->subject);
    }

    public function test_content_uses_alert_view(): void
    {
        $mailable = new Alert('Test message');
        $content = $mailable->content();

        $this->assertEquals('emails.user.alert', $content->view);
    }

    public function test_mailable_has_correct_properties_for_delivery(): void
    {
        $mailable = new Alert('System alert', 'Warning');

        $this->assertEquals('System alert', $mailable->text);
        $this->assertEquals('Warning', $mailable->title);
        $this->assertEquals('System alert', $mailable->alert_message);
        $this->assertEquals('Warning', $mailable->alert_subject);
    }

    public function test_envelope_includes_domain_from_url(): void
    {
        Container::getInstance()->instance('config', new ConfigRepository([
            'app' => ['name' => 'App', 'url' => 'https://helpdesk.example.org'],
        ]));

        $mailable = new Alert('Test');
        $envelope = $mailable->envelope();

        $this->assertStringContainsString('helpdesk.example.org', $envelope->subject);
    }

    public function test_mailable_is_queueable(): void
    {
        $mailable = new Alert('Test');

        $this->assertTrue(method_exists($mailable, 'onQueue'));
        $this->assertTrue(method_exists($mailable, 'onConnection'));
    }
}
