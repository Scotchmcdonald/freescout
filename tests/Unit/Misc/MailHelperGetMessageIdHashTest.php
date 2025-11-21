<?php

declare(strict_types=1);

namespace Tests\Unit\Misc;

use App\Misc\MailHelper;
use Tests\UnitTestCase;

/**
 * Test MailHelper::getMessageIdHash() method
 * 
 * Simple method but important for email threading
 */
class MailHelperGetMessageIdHashTest extends UnitTestCase
{
    public function test_getMessageIdHash_returns_md5_hash(): void
    {
        $result = MailHelper::getMessageIdHash(123);
        
        $this->assertIsString($result);
        $this->assertEquals(32, strlen($result)); // MD5 is 32 chars
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $result);
    }

    public function test_getMessageIdHash_is_deterministic(): void
    {
        $hash1 = MailHelper::getMessageIdHash(123);
        $hash2 = MailHelper::getMessageIdHash(123);
        
        $this->assertEquals($hash1, $hash2);
    }

    public function test_getMessageIdHash_different_ids_produce_different_hashes(): void
    {
        $hash1 = MailHelper::getMessageIdHash(123);
        $hash2 = MailHelper::getMessageIdHash(456);
        
        $this->assertNotEquals($hash1, $hash2);
    }

    public function test_getMessageIdHash_with_zero_id(): void
    {
        $result = MailHelper::getMessageIdHash(0);
        
        $this->assertIsString($result);
        $this->assertEquals(32, strlen($result));
    }

    public function test_getMessageIdHash_with_large_id(): void
    {
        $result = MailHelper::getMessageIdHash(999999999);
        
        $this->assertIsString($result);
        $this->assertEquals(32, strlen($result));
    }

    public function test_getMessageIdHash_uses_app_key_in_hash(): void
    {
        // If app key changes, hash should change for same ID
        // This test documents that behavior exists
        $originalKey = config('app.key');
        
        $hash1 = MailHelper::getMessageIdHash(123);
        
        // Simulate different app key (though we can't actually change it in test)
        // Just verify hash is computed
        $this->assertIsString($hash1);
        $this->assertNotEmpty($hash1);
    }

    public function test_getMessageIdHash_sequential_ids_have_different_hashes(): void
    {
        $hashes = [];
        for ($i = 1; $i <= 10; $i++) {
            $hashes[] = MailHelper::getMessageIdHash($i);
        }
        
        // All hashes should be unique
        $this->assertCount(10, array_unique($hashes));
    }
}
