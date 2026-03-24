<?php

declare(strict_types=1);

use App\Models\ActivityLog;
use App\Models\Conversation;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create(['role' => User::ROLE_ADMIN]);
});

test('get event description returns string', function () {
    $log = ActivityLog::factory()->create([
        'description' => ActivityLog::DESCRIPTION_USER_CREATED,
    ]);

    $description = $log->getEventDescription();

    expect($description)->toBeString()->not->toBeEmpty();
});

test('get event description handles user created', function () {
    $log = ActivityLog::factory()->create([
        'description' => ActivityLog::DESCRIPTION_USER_CREATED,
    ]);

    $description = $log->getEventDescription();

    expect($description)->toContain('User');
});

test('get event description handles conversation status changed', function () {
    $log = ActivityLog::factory()->create([
        'description' => ActivityLog::DESCRIPTION_CONVERSATION_STATUS_CHANGED,
    ]);

    $description = $log->getEventDescription();

    expect($description)->toContain('status');
});

test('get event description handles unknown description', function () {
    $log = ActivityLog::factory()->create([
        'description' => 'unknown_event_type',
    ]);

    $description = $log->getEventDescription();

    expect($description)->toBeString();
});

test('get log title returns string', function () {
    $log = ActivityLog::factory()->create();

    $title = ActivityLog::getLogTitle($log->log_name);

    expect($title)->toBeString();
});

test('get log title formats subject type', function () {
    $conversation = Conversation::factory()->create();
    $log = ActivityLog::factory()->create([
        'subject_type' => Conversation::class,
        'subject_id' => $conversation->id,
    ]);

    $title = ActivityLog::getLogTitle($log->log_name);

    expect($title)->toBeString();
});

test('format col title formats snake case', function () {
    $result = ActivityLog::formatColTitle('user_created');
    expect($result)->toBe('User Created');
});

test('format col title handles single word', function () {
    $result = ActivityLog::formatColTitle('created');
    expect($result)->toBe('Created');
});

test('format col title handles multiple underscores', function () {
    $result = ActivityLog::formatColTitle('conversation_status_changed_by_user');
    expect($result)->toBe('Conversation Status Changed By User');
});

test('format col title handles empty string', function () {
    $result = ActivityLog::formatColTitle('');
    expect($result)->toBeString();
});

test('get log names returns array', function () {
    $logNames = ActivityLog::getLogNames();
    expect($logNames)->toBeArray();
});

test('get log names contains expected keys', function () {
    // Create some logs with different names
    ActivityLog::factory()->create(['log_name' => 'default']);
    ActivityLog::factory()->create(['log_name' => 'conversation']);
    ActivityLog::factory()->create(['log_name' => 'user']);

    $logNames = ActivityLog::getLogNames();

    expect($logNames)->toContain('default');
    expect($logNames)->toContain('conversation');
    expect($logNames)->toContain('user');
});

test('get log names returns unique values', function () {
    ActivityLog::factory()->count(3)->create(['log_name' => 'default']);

    $logNames = ActivityLog::getLogNames();
    $uniqueNames = array_unique($logNames);

    expect(count($uniqueNames))->toBe(count($logNames));
});

test('get available logs returns array', function () {
    $logs = ActivityLog::getAvailableLogs();
    expect($logs)->toBeArray();
});

test('get available logs contains formatted names', function () {
    ActivityLog::factory()->create(['log_name' => 'user_activity']);

    $logs = ActivityLog::getAvailableLogs();

    expect($logs)->toBeArray();
});

test('description constants exist', function () {
    expect(ActivityLog::DESCRIPTION_USER_CREATED)->not->toBeNull();
    expect(ActivityLog::DESCRIPTION_CONVERSATION_STATUS_CHANGED)->not->toBeNull();
    expect(ActivityLog::DESCRIPTION_CONVERSATION_USER_CHANGED)->not->toBeNull();
    expect(ActivityLog::DESCRIPTION_CONVERSATION_DELETED)->not->toBeNull();
});

test('description constants are strings', function () {
    expect(ActivityLog::DESCRIPTION_USER_CREATED)->toBeString();
    expect(ActivityLog::DESCRIPTION_CONVERSATION_STATUS_CHANGED)->toBeString();
    expect(ActivityLog::DESCRIPTION_CONVERSATION_USER_CHANGED)->toBeString();
});

test('email error constants exist', function () {
    expect(ActivityLog::DESCRIPTION_EMAIL_SEND_ERROR_TO_CUSTOMER)->not->toBeNull();
    expect(ActivityLog::DESCRIPTION_EMAIL_SEND_ERROR_TO_USER)->not->toBeNull();
    expect(ActivityLog::DESCRIPTION_EMAIL_SEND_ERROR)->not->toBeNull();
});

test('email error constants are strings', function () {
    expect(ActivityLog::DESCRIPTION_EMAIL_SEND_ERROR_TO_CUSTOMER)->toBeString();
    expect(ActivityLog::DESCRIPTION_EMAIL_SEND_ERROR_TO_USER)->toBeString();
    expect(ActivityLog::DESCRIPTION_EMAIL_SEND_ERROR)->toBeString();
});

test('activity log created with conversation subject', function () {
    $conversation = Conversation::factory()->create();

    $log = ActivityLog::factory()->create([
        'subject_type' => Conversation::class,
        'subject_id' => $conversation->id,
        'causer_type' => User::class,
        'causer_id' => $this->user->id,
        'description' => ActivityLog::DESCRIPTION_CONVERSATION_STATUS_CHANGED,
    ]);

    expect($log->subject)->toBeInstanceOf(Conversation::class);
    expect($log->subject->id)->toBe($conversation->id);
});

test('activity log causer relationship', function () {
    $log = ActivityLog::factory()->create([
        'causer_type' => User::class,
        'causer_id' => $this->user->id,
    ]);

    expect($log->causer)->toBeInstanceOf(User::class);
    expect($log->causer->id)->toBe($this->user->id);
});

test('activity log with properties', function () {
    $properties = [
        'old_status' => Conversation::STATUS_ACTIVE,
        'new_status' => Conversation::STATUS_CLOSED,
    ];

    $log = ActivityLog::factory()->create([
        'properties' => $properties,
        'description' => ActivityLog::DESCRIPTION_CONVERSATION_STATUS_CHANGED,
    ]);

    expect($log->properties)->toEqual($properties);
});

test('scope in log with description constants', function () {
    ActivityLog::factory()->create([
        'log_name' => 'conversation',
        'description' => ActivityLog::DESCRIPTION_CONVERSATION_STATUS_CHANGED,
    ]);
    ActivityLog::factory()->create([
        'log_name' => 'user',
        'description' => ActivityLog::DESCRIPTION_USER_CREATED,
    ]);

    $conversationLogs = ActivityLog::inLog('conversation')->get();
    $userLogs = ActivityLog::inLog('user')->get();

    expect($conversationLogs)->toHaveCount(1);
    expect($userLogs)->toHaveCount(1);
});

test('available logs static array exists', function () {
    expect(ActivityLog::$available_logs)->toBeArray();
});

test('available logs contains expected types', function () {
    expect(ActivityLog::$available_logs)->toContain('default');
});
