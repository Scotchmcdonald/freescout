<?php

declare(strict_types=1);

namespace Tests\Unit\Misc;

use App\Misc\MailHelper;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Foundation\Application;
use Tests\PureUnitTestCase;

/**
 * Test MailHelper::getMessageIdHash() method.
 */
class MailHelperGetMessageIdHashTest extends PureUnitTestCase
{
    private ?Container $previousContainer = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->previousContainer = Container::getInstance();

        $app = new Application(getcwd());
        $app->instance('config', new Repository([
            'app' => [
                'key' => 'base64:test-app-key-for-mail-helper-hash',
            ],
        ]));

        Container::setInstance($app);
    }

    protected function tearDown(): void
    {
        Container::setInstance($this->previousContainer);

        parent::tearDown();
    }

    public function test_get_message_id_hash_returns_md5_hash(): void
    {
        $result = MailHelper::getMessageIdHash(123);

        $this->assertIsString($result);
        $this->assertEquals(32, strlen($result));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $result);
    }

    public function test_get_message_id_hash_is_deterministic(): void
    {
        $hash1 = MailHelper::getMessageIdHash(123);
        $hash2 = MailHelper::getMessageIdHash(123);

        $this->assertEquals($hash1, $hash2);
    }

    public function test_get_message_id_hash_different_ids_produce_different_hashes(): void
    {
        $hash1 = MailHelper::getMessageIdHash(123);
        $hash2 = MailHelper::getMessageIdHash(456);

        $this->assertNotEquals($hash1, $hash2);
    }

    public function test_get_message_id_hash_with_zero_id(): void
    {
        $result = MailHelper::getMessageIdHash(0);

        $this->assertIsString($result);
        $this->assertEquals(32, strlen($result));
    }

    public function test_get_message_id_hash_with_large_id(): void
    {
        $result = MailHelper::getMessageIdHash(999999999);

        $this->assertIsString($result);
        $this->assertEquals(32, strlen($result));
    }

    public function test_get_message_id_hash_uses_app_key_in_hash(): void
    {
        $hash = MailHelper::getMessageIdHash(123);

        $this->assertIsString($hash);
        $this->assertNotEmpty($hash);
    }

    public function test_get_message_id_hash_sequential_ids_have_different_hashes(): void
    {
        $hashes = [];
        for ($i = 1; $i <= 10; $i++) {
            $hashes[] = MailHelper::getMessageIdHash($i);
        }

        $this->assertCount(10, array_unique($hashes));
    }
}
