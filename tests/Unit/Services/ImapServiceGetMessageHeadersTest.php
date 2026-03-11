<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\ImapService;
use Mockery;
use Tests\TestCase;
use Webklex\PHPIMAP\Message;
use Webklex\PHPIMAP\Header;

/**
 * Comprehensive tests for ImapService::getMessageHeaders() method.
 */
class ImapServiceGetMessageHeadersTest extends TestCase
{
    protected ImapService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ImapService();
    }

    protected function tearDown(): void
    {
        try {
            Mockery::close();
        } finally {
            parent::tearDown();
        }
    }

    /**
     * Helper method to invoke protected getMessageHeaders method
     */
    protected function invokeGetMessageHeaders($message): string
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getMessageHeaders');
        $method->setAccessible(true);

        return $method->invoke($this->service, $message);
    }

    public function test_returns_raw_header_when_available(): void
    {
        $rawHeader = "From: test@example.com\r\nTo: recipient@example.com\r\nSubject: Test\r\n";

        $message = new class($rawHeader) {
            private $header;
            public function __construct($header) { $this->header = $header; }
            public function getRawHeader() { return $this->header; }
        };

        $result = $this->invokeGetMessageHeaders($message);

        $this->assertEquals($rawHeader, $result);
    }

    public function test_returns_empty_string_when_raw_header_is_empty(): void
    {
        $message = new class {
            public function getRawHeader() { return ''; }
            public function getHeader() { return null; }
        };

        $result = $this->invokeGetMessageHeaders($message);

        $this->assertEquals('', $result);
    }

    public function test_returns_empty_string_when_raw_header_throws_exception(): void
    {
        $message = new class {
            public function getRawHeader() { throw new \BadMethodCallException('Method not available'); }
            public function getHeader() { return null; }
        };

        $result = $this->invokeGetMessageHeaders($message);

        $this->assertEquals('', $result);
    }

    public function test_falls_back_to_header_when_raw_header_not_available(): void
    {
        $headerString = "From: test@example.com\nSubject: Test";

        $header = new class($headerString) {
            private $str;
            public function __construct($s) { $this->str = $s; }
            public function __toString() { return $this->str; }
        };

        $message = new class($header) {
            private $h;
            public function __construct($h) { $this->h = $h; }
            // No getRawHeader method
            public function getHeader() { return $this->h; }
        };

        $result = $this->invokeGetMessageHeaders($message);

        $this->assertEquals($headerString, $result);
    }

    public function test_converts_header_object_to_string_using_tostring(): void
    {
        $headerString = "From: test@example.com\nTo: recipient@example.com";
        
        $header = new class($headerString) {
            private $str;
            public function __construct($s) { $this->str = $s; }
            public function __toString() { return $this->str; }
        };

        $message = new class($header) {
            private $h;
            public function __construct($h) { $this->h = $h; }
            // No getRawHeader
            public function getHeader() { return $this->h; }
        };

        $result = $this->invokeGetMessageHeaders($message);

        $this->assertEquals($headerString, $result);
    }

    public function test_returns_empty_when_header_tostring_returns_mockery_object(): void
    {
        // Simulate Mockery object string representation
        $header = new class {
            public function __toString() { return 'Mockery_123_Header'; }
        };

        $message = new class($header) {
            private $h;
            public function __construct($h) { $this->h = $h; }
            public function getHeader() { return $this->h; }
        };

        $result = $this->invokeGetMessageHeaders($message);

        $this->assertEquals('', $result);
    }

    public function test_returns_empty_when_header_is_null(): void
    {
        $message = new class {
            // No getRawHeader
            public function getHeader() { return null; }
        };

        $result = $this->invokeGetMessageHeaders($message);

        $this->assertEquals('', $result);
    }

    public function test_handles_exception_from_get_header(): void
    {
        $message = new class {
            // No getRawHeader
            public function getHeader() { throw new \Exception('Header not accessible'); }
        };

        $result = $this->invokeGetMessageHeaders($message);

        $this->assertEquals('', $result);
    }

    public function test_raw_header_takes_precedence_over_header_object(): void
    {
        $rawHeader = "Raw: Header Content";
        $headerString = "Fallback: Header Content";

        $header = new class($headerString) {
            private $str;
            public function __construct($s) { $this->str = $s; }
            public function __toString() { return $this->str; }
        };

        $message = new class($rawHeader, $header) {
            private $rh, $h;
            public function __construct($rh, $h) { $this->rh = $rh; $this->h = $h; }
            public function getRawHeader() { return $this->rh; }
            public function getHeader() { return $this->h; }
        };

        $result = $this->invokeGetMessageHeaders($message);

        $this->assertEquals($rawHeader, $result);
    }

    public function test_handles_multiline_raw_header(): void
    {
        $rawHeader = "From: test@example.com\r\n" .
                     "To: recipient@example.com\r\n" .
                     "Subject: Test Message\r\n" .
                     "Date: Mon, 20 Nov 2025 10:00:00 +0000\r\n" .
                     "Message-ID: <123@example.com>\r\n";

        $message = new class($rawHeader) {
            private $rh;
            public function __construct($rh) { $this->rh = $rh; }
            public function getRawHeader() { return $this->rh; }
        };

        $result = $this->invokeGetMessageHeaders($message);

        $this->assertEquals($rawHeader, $result);
        $this->assertStringContainsString('From: test@example.com', $result);
        $this->assertStringContainsString('Message-ID: <123@example.com>', $result);
    }

    public function test_handles_header_with_unicode_characters(): void
    {
        $rawHeader = "From: tëst@example.com\r\nSubject: Тест 测试\r\n";

        $message = new class($rawHeader) {
            private $rh;
            public function __construct($rh) { $this->rh = $rh; }
            public function getRawHeader() { return $this->rh; }
        };

        $result = $this->invokeGetMessageHeaders($message);

        $this->assertEquals($rawHeader, $result);
        $this->assertStringContainsString('tëst@example.com', $result);
    }

    public function test_returns_empty_when_raw_header_is_not_string(): void
    {
        $message = new class {
            public function getRawHeader() { return new \stdClass(); }
            public function getHeader() { return null; }
        };

        $result = $this->invokeGetMessageHeaders($message);

        $this->assertEquals('', $result);
    }

    public function test_header_object_without_tostring_returns_empty(): void
    {
        $headerObject = (object)['from' => 'test@example.com'];

        $message = new class($headerObject) {
            private $h;
            public function __construct($h) { $this->h = $h; }
            // No getRawHeader
            public function getHeader() { return $this->h; }
        };

        $result = $this->invokeGetMessageHeaders($message);

        $this->assertEquals('', $result);
    }

    public function test_handles_very_long_header(): void
    {
        $rawHeader = str_repeat("X-Custom-Header: " . str_repeat('a', 100) . "\r\n", 50);

        $message = new class($rawHeader) {
            private $rh;
            public function __construct($rh) { $this->rh = $rh; }
            public function getRawHeader() { return $this->rh; }
        };

        $result = $this->invokeGetMessageHeaders($message);

        $this->assertEquals($rawHeader, $result);
        $this->assertGreaterThan(5000, strlen($result));
    }

    public function test_handles_header_with_special_characters(): void
    {
        $rawHeader = "From: \"John Doe\" <john@example.com>\r\n" .
                     "Subject: =?UTF-8?B?VGVzdCBTdWJqZWN0?=\r\n";

        $message = new class($rawHeader) {
            private $rh;
            public function __construct($rh) { $this->rh = $rh; }
            public function getRawHeader() { return $this->rh; }
        };

        $result = $this->invokeGetMessageHeaders($message);

        $this->assertEquals($rawHeader, $result);
        $this->assertStringContainsString('"John Doe"', $result);
    }

    public function test_returns_empty_for_all_failures(): void
    {
        $message = new class {
            public function getRawHeader() { throw new \RuntimeException(); }
            public function getHeader() { throw new \RuntimeException(); }
        };

        $result = $this->invokeGetMessageHeaders($message);

        $this->assertEquals('', $result);
    }
}
