<?php

use App\Models\AuditLog;
use App\Models\User;
use App\Models\Announcement;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Auth;

beforeEach(function () {
    $this->auditLogger = app(AuditLogger::class);
});

test('log method creates audit log entry with authenticated user', function () {
    $user = User::factory()->create();
    Auth::login($user);

    $log = $this->auditLogger->log('TestModule', 'Test action', ['key' => 'value']);

    expect($log)->toBeInstanceOf(AuditLog::class)
        ->and($log->user_id)->toBe($user->id)
        ->and($log->module)->toBe('TestModule')
        ->and($log->action)->toBe('Test action')
        ->and($log->payload)->toBe(['key' => 'value'])
        ->and($log->created_at)->not->toBeNull();
});

test('log method accepts custom user_id parameter', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    Auth::login($user1);

    $log = $this->auditLogger->log('TestModule', 'Test action', [], $user2->id);

    expect($log->user_id)->toBe($user2->id);
});

test('log method works without authenticated user when user_id provided', function () {
    $user = User::factory()->create();

    $log = $this->auditLogger->log('TestModule', 'Test action', [], $user->id);

    expect($log->user_id)->toBe($user->id);
});

test('logUserAction creates audit log with user details', function () {
    $actor = User::factory()->create();
    $targetUser = User::factory()->create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);
    Auth::login($actor);

    $log = $this->auditLogger->logUserAction('created', $targetUser);

    expect($log->user_id)->toBe($actor->id)
        ->and($log->module)->toBe('User')
        ->and($log->action)->toBe('User created')
        ->and($log->payload['user_id'])->toBe($targetUser->id)
        ->and($log->payload['user_email'])->toBe('john@example.com')
        ->and($log->payload['user_name'])->toBe('John Doe');
});

test('logUserAction includes changes when provided', function () {
    $actor = User::factory()->create();
    $targetUser = User::factory()->create();
    Auth::login($actor);

    $changes = [
        'name' => ['old' => 'Old Name', 'new' => 'New Name'],
        'role' => ['old' => 'user', 'new' => 'admin'],
    ];

    $log = $this->auditLogger->logUserAction('updated', $targetUser, $changes);

    expect($log->payload['changes'])->toBe($changes);
});

test('logAnnouncementAction creates audit log with announcement details', function () {
    $user = User::factory()->create();
    Auth::login($user);

    $announcement = Announcement::factory()->create([
        'type' => 'critical',
        'message' => 'System maintenance',
    ]);

    $log = $this->auditLogger->logAnnouncementAction('created', $announcement);

    expect($log->user_id)->toBe($user->id)
        ->and($log->module)->toBe('Announcement')
        ->and($log->action)->toBe('Announcement created')
        ->and($log->payload['announcement_id'])->toBe($announcement->id)
        ->and($log->payload['type'])->toBe('critical')
        ->and($log->payload['message'])->toBe('System maintenance');
});

test('logModuleAction creates audit log with module context', function () {
    $user = User::factory()->create();
    Auth::login($user);

    $data = ['setting' => 'enabled', 'version' => '1.0'];

    $log = $this->auditLogger->logModuleAction('Inventory', 'Module enabled', $data);

    expect($log->user_id)->toBe($user->id)
        ->and($log->module)->toBe('Inventory')
        ->and($log->action)->toBe('Module enabled')
        ->and($log->payload)->toBe($data);
});

test('audit logger is bound as singleton in container', function () {
    $instance1 = app(AuditLogger::class);
    $instance2 = app(AuditLogger::class);

    expect($instance1)->toBeInstanceOf(AuditLogger::class)
        ->and($instance2)->toBeInstanceOf(AuditLogger::class)
        ->and(spl_object_id($instance1))->toBe(spl_object_id($instance2));
});

test('log method stores payload as json in database', function () {
    $user = User::factory()->create();
    Auth::login($user);

    $payload = [
        'nested' => ['key' => 'value'],
        'array' => [1, 2, 3],
        'string' => 'test',
    ];

    $log = $this->auditLogger->log('TestModule', 'Test action', $payload);

    // Refresh from database to ensure it was stored and retrieved correctly
    $log->refresh();

    expect($log->payload)->toBe($payload);
});
