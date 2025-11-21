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
 * This method currently has ~23% coverage and needs additional testing.
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
        Mockery::close();
        parent::tearDown();
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

        $message = Mockery::mock(Message::class);
        $message->shouldReceive('getRawHeader')->once()->andReturn($rawHeader);

        $result = $this->invokeGetMessageHeaders($message);

        $this->assertEquals($rawHeader, $result);
    }

    public function test_returns_empty_string_when_raw_header_is_empty(): void
    {
        $message = Mockery::mock(Message::class);
        $message->shouldReceive('getRawHeader')->once()->andReturn('');
        $message->shouldReceive('getHeader')->once()->andReturn(null);

        $result = $this->invokeGetMessageHeaders($message);

        $this->assertEquals('', $result);
    }

    public function test_returns_empty_string_when_raw_header_throws_exception(): void
    {
        $message = Mockery::mock(Message::class);
        $message->shouldReceive('getRawHeader')->once()->andThrow(new \BadMethodCallException('Method not available'));
        $message->shouldReceive('getHeader')->once()->andReturn(null);

        $result = $this->invokeGetMessageHeaders($message);

        $this->assertEquals('', $result);
    }

    public function test_falls_back_to_header_when_raw_header_not_available(): void
    {
        $headerString = "From: test@example.com\nSubject: Test";

        // getHeader() must return ?Header per type signature, not string
        $header = Mockery::mock(Header::class);
        $header->shouldReceive('__toString')->once()->andReturn($headerString);

        $message = Mockery::mock(Message::class);
        $message->shouldReceive('getRawHeader')->once()->andThrow(new \BadMethodCallException());
        $message->shouldReceive('getHeader')->once()->andReturn($header);

        $result = $this->invokeGetMessageHeaders($message);

        $this->assertEquals($headerString, $result);
    }

    public function test_converts_header_object_to_string_using_tostring(): void
    {
        $headerString = "From: test@example.com\nTo: recipient@example.com";

        $header = Mockery::mock(Header::class);
        $header->shouldReceive('__toString')->once()->andReturn($headerString);

        $message = Mockery::mock(Message::class);
        $message->shouldReceive('getRawHeader')->once()->andThrow(new \BadMethodCallException());
        $message->shouldReceive('getHeader')->once()->andReturn($header);

        $result = $this->invokeGetMessageHeaders($message);

        $this->assertEquals($headerString, $result);
    }

    public function test_returns_empty_when_header_tostring_returns_mockery_object(): void
    {
        $header = Mockery::mock(Header::class);
        $header->shouldReceive('__toString')->once()->andReturn('Mockery_123_Header');

        $message = Mockery::mock(Message::class);
        $message->shouldReceive('getRawHeader')->once()->andThrow(new \BadMethodCallException());
        $message->shouldReceive('getHeader')->once()->andReturn($header);

        $result = $this->invokeGetMessageHeaders($message);

        $this->assertEquals('', $result);
    }

    public function test_returns_empty_when_header_is_null(): void
    {
        $message = Mockery::mock(Message::class);
        $message->shouldReceive('getRawHeader')->once()->andThrow(new \BadMethodCallException());
        $message->shouldReceive('getHeader')->once()->andReturn(null);

        $result = $this->invokeGetMessageHeaders($message);

        $this->assertEquals('', $result);
    }

    public function test_returns_empty_when_get_header_throws_exception(): void
    {
        $message = Mockery::mock(Message::class);
        $message->shouldReceive('getRawHeader')->once()->andThrow(new \BadMethodCallException());
        $message->shouldReceive('getHeader')->once()->andThrow(new \Exception('Header not accessible'));

        $result = $this->invokeGetMessageHeaders($message);

        $this->assertEquals('', $result);
    }

    public function test_raw_header_takes_precedence_over_header_object(): void
    {
        $rawHeader = "Raw: Header Content";
        $headerString = "Fallback: Header Content";

        $header = Mockery::mock(Header::class);
        $header->shouldReceive('__toString')->andReturn($headerString);

        $message = Mockery::mock(Message::class);
        $message->shouldReceive('getRawHeader')->once()->andReturn($rawHeader);
        // getHeader should NOT be called when getRawHeader succeeds
        $message->shouldNotReceive('getHeader');

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

        $message = Mockery::mock(Message::class);
        $message->shouldReceive('getRawHeader')->once()->andReturn($rawHeader);

        $result = $this->invokeGetMessageHeaders($message);

        $this->assertEquals($rawHeader, $result);
        $this->assertStringContainsString('From: test@example.com', $result);
        $this->assertStringContainsString('Message-ID: <123@example.com>', $result);
    }

    public function test_handles_header_with_unicode_characters(): void
    {
        $rawHeader = "From: tëst@example.com\r\nSubject: Тест 测试\r\n";

        $message = Mockery::mock(Message::class);
        $message->shouldReceive('getRawHeader')->once()->andReturn($rawHeader);

        $result = $this->invokeGetMessageHeaders($message);

        $this->assertEquals($rawHeader, $result);
        $this->assertStringContainsString('tëst@example.com', $result);
    }

    public function test_returns_empty_when_raw_header_is_not_string(): void
    {
        $message = Mockery::mock(Message::class);
        // getRawHeader returns a non-string (mock object that isn't properly set up)
        $message->shouldReceive('getRawHeader')->once()->andReturn(new \stdClass());
        $message->shouldReceive('getHeader')->once()->andReturn(null);

        $result = $this->invokeGetMessageHeaders($message);

        $this->assertEquals('', $result);
    }

    public function test_header_object_without_tostring_returns_empty(): void
    {
        $headerObject = (object)['from' => 'test@example.com'];

        $message = Mockery::mock(Message::class);
        $message->shouldReceive('getRawHeader')->once()->andThrow(new \BadMethodCallException());
        $message->shouldReceive('getHeader')->once()->andReturn($headerObject);

        $result = $this->invokeGetMessageHeaders($message);

        $this->assertEquals('', $result);
    }

    public function test_handles_very_long_header(): void
    {
        $rawHeader = str_repeat("X-Custom-Header: " . str_repeat('a', 100) . "\r\n", 50);

        $message = Mockery::mock(Message::class);
        $message->shouldReceive('getRawHeader')->once()->andReturn($rawHeader);

        $result = $this->invokeGetMessageHeaders($message);

        $this->assertEquals($rawHeader, $result);
        $this->assertGreaterThan(5000, strlen($result));
    }

    public function test_handles_header_with_special_characters(): void
    {
        $rawHeader = "From: \"John Doe\" <john@example.com>\r\n" .
                     "Subject: =?UTF-8?B?VGVzdCBTdWJqZWN0?=\r\n";

        $message = Mockery::mock(Message::class);
        $message->shouldReceive('getRawHeader')->once()->andReturn($rawHeader);

        $result = $this->invokeGetMessageHeaders($message);

        $this->assertEquals($rawHeader, $result);
        // The string contains literal quote characters (not escaped in the result)
        $this->assertStringContainsString('"John Doe"', $result);
    }

    public function test_returns_empty_for_all_failures(): void
    {
        $message = Mockery::mock(Message::class);
        $message->shouldReceive('getRawHeader')->once()->andThrow(new \RuntimeException());
        $message->shouldReceive('getHeader')->once()->andThrow(new \RuntimeException());

        $result = $this->invokeGetMessageHeaders($message);

        $this->assertEquals('', $result);
    }
}
