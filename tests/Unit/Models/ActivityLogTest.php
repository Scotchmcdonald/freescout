<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\ActivityLog;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Foundation\Application;
use Tests\PureUnitTestCase;

class ActivityLogTest extends PureUnitTestCase
{
    private ?Container $previousContainer = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->previousContainer = Container::getInstance();

        $app = new Application(getcwd());
        $app->instance('config', new Repository([
            'app' => [],
            'activitylog' => ['enabled' => true],
        ]));
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
    }

    protected function tearDown(): void
    {
        Container::setInstance($this->previousContainer);

        parent::tearDown();
    }

    public function test_get_event_description_returns_translated_labels_for_known_events(): void
    {
        $cases = [
            ActivityLog::DESCRIPTION_USER_LOGIN => 'Logged in',
            ActivityLog::DESCRIPTION_USER_LOGOUT => 'Logged out',
            ActivityLog::DESCRIPTION_USER_REGISTER => 'Registered',
            ActivityLog::DESCRIPTION_USER_LOCKED => 'Locked out',
            ActivityLog::DESCRIPTION_USER_LOGIN_FAILED => 'Failed login',
            ActivityLog::DESCRIPTION_USER_PASSWORD_RESET => 'Reset password',
            ActivityLog::DESCRIPTION_EMAILS_SENDING_ERROR_TO_CUSTOMER => 'Error sending email to customer',
            ActivityLog::DESCRIPTION_EMAILS_SENDING_ERROR_TO_USER => 'Error sending email to user',
            ActivityLog::DESCRIPTION_EMAILS_SENDING_ERROR_INVITE => 'Error sending invitation email to user',
            ActivityLog::DESCRIPTION_EMAILS_SENDING_ERROR_PASSWORD_CHANGED => 'Error sending password changed notification to user',
            ActivityLog::DESCRIPTION_EMAILS_SENDING_ERROR_ALERT => 'Error sending alert',
            ActivityLog::DESCRIPTION_EMAILS_SENDING_ERROR_WRONG_EMAIL => 'Error sending email to the user who replied to notification from wrong email',
            ActivityLog::DESCRIPTION_EMAILS_FETCHING_ERROR => 'Error fetching email',
            ActivityLog::DESCRIPTION_SYSTEM_ERROR => 'System error',
            ActivityLog::DESCRIPTION_USER_DELETED => 'Deleted user',
            ActivityLog::DESCRIPTION_USER_CREATED => 'User created',
            ActivityLog::DESCRIPTION_CONVERSATION_STATUS_CHANGED => 'Conversation status changed',
            ActivityLog::DESCRIPTION_EMAILS_SENDING_ERROR_AUTO_REPLY => 'Error sending auto reply',
            ActivityLog::DESCRIPTION_EMAILS_SENDING_ERROR_USER_NOTIFICATION => 'Error sending user notification',
            ActivityLog::DESCRIPTION_EMAILS_SENDING_ERROR_SYSTEM => 'Error sending system email',
            ActivityLog::DESCRIPTION_EMAILS_SENDING_ERROR_FORWARD => 'Error sending forward',
        ];

        foreach ($cases as $description => $expected) {
            $log = new ActivityLog(['description' => $description]);

            $this->assertSame($expected, $log->getEventDescription());
        }
    }

    public function test_get_event_description_falls_back_to_raw_description_for_unknown_events(): void
    {
        $log = new ActivityLog(['description' => 'custom_event']);

        $this->assertSame('custom_event', $log->getEventDescription());
    }

    public function test_get_log_title_returns_known_and_formatted_fallback_titles(): void
    {
        $this->assertSame('Users', ActivityLog::getLogTitle(ActivityLog::NAME_USER));
        $this->assertSame('Outgoing Emails', ActivityLog::getLogTitle(ActivityLog::NAME_OUT_EMAILS));
        $this->assertSame('Send Errors', ActivityLog::getLogTitle(ActivityLog::NAME_EMAILS_SENDING));
        $this->assertSame('Fetch Errors', ActivityLog::getLogTitle(ActivityLog::NAME_EMAILS_FETCHING));
        $this->assertSame('System', ActivityLog::getLogTitle(ActivityLog::NAME_SYSTEM));
        $this->assertSame('App Logs', ActivityLog::getLogTitle(ActivityLog::NAME_APP_LOGS));
        $this->assertSame('Conversations', ActivityLog::getLogTitle(ActivityLog::NAME_CONVERSATION));
        $this->assertSame('Custom Log Name', ActivityLog::getLogTitle('custom_log_name'));
    }

    public function test_format_col_title_and_get_available_logs_without_db_check_are_pure(): void
    {
        $this->assertSame('Batch Uuid', ActivityLog::formatColTitle('batch_uuid'));
        $this->assertSame(ActivityLog::$available_logs, ActivityLog::getAvailableLogs(false));
    }

    public function test_get_available_logs_merges_static_names_with_existing_logs_without_duplicates(): void
    {
        $merged = TestActivityLogForAvailableLogs::getAvailableLogs(true);

        $this->assertContains(ActivityLog::NAME_USER, $merged);
        $this->assertContains('custom_stream', $merged);
        $this->assertSame(count($merged), count(array_unique($merged)));
    }
}

final class TestActivityLogForAvailableLogs extends ActivityLog
{
    /**
     * @return array<string>
     */
    public static function getLogNames(): array
    {
        return [
            self::NAME_USER,
            'custom_stream',
            self::NAME_SYSTEM,
        ];
    }
}
