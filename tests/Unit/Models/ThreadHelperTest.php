<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Conversation;
use App\Models\Thread;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Facade;
use Tests\PureUnitTestCase;

final class TestThreadHelper extends Thread
{
    protected function casts(): array
    {
        return [];
    }
}

class ThreadHelperTest extends PureUnitTestCase
{
    private ?Container $previousContainer = null;

    private mixed $previousFacadeApplication = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->previousContainer = Container::getInstance();
        $this->previousFacadeApplication = Facade::getFacadeApplication();

        $app = new Application(getcwd());
        $app->instance('config', new Repository(['app' => []]));
        $app->instance('translator', new class
        {
            public function get(string $key, array $replace = [], ?string $locale = null, bool $fallback = true): string
            {
                foreach ($replace as $token => $value) {
                    $key = str_replace(':'.$token, (string) $value, $key);
                }

                return $key;
            }
        });

        Container::setInstance($app);
        Facade::setFacadeApplication($app);
    }

    protected function tearDown(): void
    {
        Facade::setFacadeApplication($this->previousFacadeApplication);
        Container::setInstance($this->previousContainer);

        parent::tearDown();
    }

    private function thread(array $attrs = []): TestThreadHelper
    {
        $t = new TestThreadHelper;
        foreach ($attrs as $key => $value) {
            $t->{$key} = $value;
        }

        return $t;
    }

    // -------------------------------------------------------------------------
    // isCustomerMessage / isUserMessage / isNote
    // -------------------------------------------------------------------------

    public function test_type_checks_return_correct_boolean_for_each_type(): void
    {
        $customer = $this->thread(['type' => Thread::TYPE_CUSTOMER]);
        $user = $this->thread(['type' => Thread::TYPE_MESSAGE]);
        $note = $this->thread(['type' => Thread::TYPE_NOTE]);

        $this->assertTrue($customer->isCustomerMessage());
        $this->assertFalse($customer->isUserMessage());
        $this->assertFalse($customer->isNote());

        $this->assertFalse($user->isCustomerMessage());
        $this->assertTrue($user->isUserMessage());
        $this->assertFalse($user->isNote());

        $this->assertFalse($note->isCustomerMessage());
        $this->assertFalse($note->isUserMessage());
        $this->assertTrue($note->isNote());
    }

    // -------------------------------------------------------------------------
    // isBounce — reads only $this->meta array
    // -------------------------------------------------------------------------

    public function test_is_bounce_returns_false_when_meta_is_null(): void
    {
        $this->assertFalse($this->thread(['meta' => null])->isBounce());
    }

    public function test_is_bounce_returns_false_when_send_status_missing(): void
    {
        $this->assertFalse($this->thread(['meta' => []])->isBounce());
    }

    public function test_is_bounce_returns_false_when_is_bounce_flag_is_false(): void
    {
        $this->assertFalse($this->thread(['meta' => ['send_status' => ['is_bounce' => false]]])->isBounce());
    }

    public function test_is_bounce_returns_true_when_is_bounce_flag_is_set(): void
    {
        $this->assertTrue($this->thread(['meta' => ['send_status' => ['is_bounce' => true]]])->isBounce());
    }

    public function test_is_bounce_returns_false_when_send_status_is_not_an_array(): void
    {
        // Non-array send_status is reset to [] and evaluated as empty
        $this->assertFalse($this->thread(['meta' => ['send_status' => 'string']])->isBounce());
    }

    // -------------------------------------------------------------------------
    // isAutoResponder — delegates to MailHelper::isAutoResponder(string|null)
    // -------------------------------------------------------------------------

    public function test_is_auto_responder_returns_false_for_null_headers(): void
    {
        $this->assertFalse($this->thread(['headers' => null])->isAutoResponder());
    }

    public function test_is_auto_responder_returns_false_for_empty_headers(): void
    {
        $this->assertFalse($this->thread(['headers' => ''])->isAutoResponder());
    }

    public function test_is_auto_responder_returns_true_for_x_autoreply_header(): void
    {
        $this->assertTrue($this->thread(['headers' => 'X-Autoreply: yes'])->isAutoResponder());
    }

    public function test_is_auto_responder_returns_true_for_auto_submitted_header(): void
    {
        $this->assertTrue($this->thread(['headers' => 'Auto-Submitted: auto-replied'])->isAutoResponder());
    }

    public function test_is_auto_responder_returns_true_for_precedence_bulk(): void
    {
        $this->assertTrue($this->thread(['headers' => 'Precedence: bulk'])->isAutoResponder());
    }

    public function test_is_auto_responder_returns_false_for_regular_headers(): void
    {
        $this->assertFalse($this->thread(['headers' => "From: user@example.com\nSubject: Hello"])->isAutoResponder());
    }

    // -------------------------------------------------------------------------
    // getStatusName
    // -------------------------------------------------------------------------

    public function test_get_status_name_returns_correct_label_for_each_status(): void
    {
        $cases = [
            Conversation::STATUS_ACTIVE => 'Active',
            Conversation::STATUS_PENDING => 'Pending',
            Conversation::STATUS_CLOSED => 'Closed',
            Conversation::STATUS_SPAM => 'Spam',
            999 => 'Unknown',
        ];

        foreach ($cases as $status => $expected) {
            $this->assertSame($expected, $this->thread(['status' => $status])->getStatusName());
        }
    }
}
