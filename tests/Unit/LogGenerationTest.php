<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class LogGenerationTest extends TestCase
{
    public function test_risky()
    {
        $this->assertTrue(true);
    }

    public function test_skipped()
    {
        $this->assertTrue(true);
    }

    public function test_incomplete()
    {
        $this->assertTrue(true);
    }

    public function test_warning()
    {
        trigger_error('This is a warning.', E_USER_WARNING);
        $this->assertTrue(true);
    }
}
