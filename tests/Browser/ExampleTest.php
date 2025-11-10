<?php

namespace Tests\Browser;

use Tests\DuskTestCase;
use Laravel\Dusk\Browser;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class ExampleTest extends DuskTestCase
{
    /**
     * A basic browser test example.
     *
     * @return void
     */
    public function testBasicExample()
    {
        $this->browse(function (Browser $browser) {
            // Simple test to verify ChromeDriver is working
            // Just assert that the browser object was created successfully
            $this->assertInstanceOf(Browser::class, $browser);
        });
    }
}
