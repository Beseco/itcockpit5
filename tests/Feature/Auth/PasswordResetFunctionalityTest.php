<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;

/**
 * Password Reset Functionality Tests
 * 
 * Validates Requirements: 1.6, 13.1, 13.2, 13.3, 13.4, 13.5, 13.6
 */

test('password reset routes are registered and accessible', function () {
    // Requirement 1.6: System SHALL provide password reset functionality via email
    
    // Test forgot password page
    $response = $this->get('/forgot-password');
    $response->assertStatus(200);
    $response->assertViewIs('auth.forgot-password');
    
    // Test reset password page with token
    $response = $this->get('/reset-password/test-token');
    $response->assertStatus(200);
    $response->assertViewIs('auth.reset-password');
});

test('password reset email is sent using Laravel mail system', function () {
    // Requirement 13.1: System SHALL send password reset emails using Laravel's mail system
    Notification::fake();
    
    $user = User::factory()->create([
        'email' => 'test@example.com',
    ]);
    
    $response = $this->post('/forgot-password', [
        'email' => $user->email,
    ]);
    
    $response->assertSessionHasNoErrors();
    Notification::assertSentTo($user, ResetPassword::class);
});

test('password reset uses SMTP configuration from env', function () {
    // Requirement 13.2: System SHALL use SMTP configuration for email delivery
    
    // Verify mail configuration is loaded
    $mailer = config('mail.default');
    expect($mailer)->toBeString();
    
    // Verify SMTP mailer is configured
    expect(config('mail.mailers.smtp'))->toBeArray();
    expect(config('mail.mailers.smtp.transport'))->toBe('smtp');
    
    // Verify from address is configured
    expect(config('mail.from.address'))->toBeString();
    expect(config('mail.from.name'))->toBeString();
});

test('secure token is generated when password reset is requested', function () {
    // Requirement 13.3: System SHALL generate a secure token when password reset is requested
    Notification::fake();
    
    $user = User::factory()->create();
    
    $this->post('/forgot-password', ['email' => $user->email]);
    
    Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
        // Verify token exists and is a string
        expect($notification->token)->toBeString();
        expect(strlen($notification->token))->toBeGreaterThan(20);
        
        return true;
    });
    
    // Verify token is stored in database
    $tokenRecord = DB::table('password_reset_tokens')
        ->where('email', $user->email)
        ->first();
    
    expect($tokenRecord)->not->toBeNull();
    expect($tokenRecord->token)->toBeString();
});

test('reset token is included in password reset email', function () {
    // Requirement 13.4: System SHALL include the reset token in the password reset email
    Notification::fake();
    
    $user = User::factory()->create();
    
    $this->post('/forgot-password', ['email' => $user->email]);
    
    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        // Verify the notification contains the token
        expect($notification->token)->toBeString();
        
        // Verify the reset URL can be generated with the token
        $url = route('password.reset', ['token' => $notification->token]);
        expect($url)->toContain($notification->token);
        
        return true;
    });
});

test('password reset tokens expire after 60 minutes', function () {
    // Requirement 13.5: System SHALL expire password reset tokens after 60 minutes
    
    // Verify configuration
    $expireMinutes = config('auth.passwords.users.expire');
    expect($expireMinutes)->toBe(60);
    
    $user = User::factory()->create();
    
    // Create an expired token (61 minutes old)
    $token = Password::createToken($user);
    
    // Manually update the token timestamp to be 61 minutes old
    DB::table('password_reset_tokens')
        ->where('email', $user->email)
        ->update(['created_at' => now()->subMinutes(61)]);
    
    // Attempt to reset password with expired token
    $response = $this->post('/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);
    
    // Should fail with error
    $response->assertSessionHasErrors(['email']);
});

test('password reset validates token before allowing password change', function () {
    // Requirement 13.6: System SHALL validate the reset token before allowing password changes
    
    $user = User::factory()->create([
        'password' => Hash::make('oldpassword'),
    ]);
    
    // Test with invalid token
    $response = $this->post('/reset-password', [
        'token' => 'invalid-token-12345',
        'email' => $user->email,
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);
    
    $response->assertSessionHasErrors(['email']);
    
    // Verify password was NOT changed
    $user->refresh();
    expect(Hash::check('oldpassword', $user->password))->toBeTrue();
    expect(Hash::check('newpassword123', $user->password))->toBeFalse();
});

test('password can be successfully reset with valid token', function () {
    // Complete password reset flow validation
    Notification::fake();
    
    $user = User::factory()->create([
        'password' => Hash::make('oldpassword'),
    ]);
    
    // Request password reset
    $this->post('/forgot-password', ['email' => $user->email]);
    
    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        // Reset password with valid token
        $response = $this->post('/reset-password', [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);
        
        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('login'));
        
        // Verify password was changed
        $user->refresh();
        expect(Hash::check('newpassword123', $user->password))->toBeTrue();
        expect(Hash::check('oldpassword', $user->password))->toBeFalse();
        
        return true;
    });
});

test('password reset requires email validation', function () {
    // Test invalid email format
    $response = $this->post('/forgot-password', [
        'email' => 'not-an-email',
    ]);
    
    $response->assertSessionHasErrors(['email']);
});

test('password reset requires password confirmation', function () {
    Notification::fake();
    
    $user = User::factory()->create();
    
    $this->post('/forgot-password', ['email' => $user->email]);
    
    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        // Attempt reset without matching confirmation
        $response = $this->post('/reset-password', [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'differentpassword',
        ]);
        
        $response->assertSessionHasErrors(['password']);
        
        return true;
    });
});

test('password reset token is single use', function () {
    Notification::fake();
    
    $user = User::factory()->create([
        'password' => Hash::make('oldpassword'),
    ]);
    
    $this->post('/forgot-password', ['email' => $user->email]);
    
    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        // Use token once successfully
        $this->post('/reset-password', [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])->assertSessionHasNoErrors();
        
        // Try to use the same token again
        $response = $this->post('/reset-password', [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'anotherpassword456',
            'password_confirmation' => 'anotherpassword456',
        ]);
        
        // Should fail because token was already used
        $response->assertSessionHasErrors(['email']);
        
        return true;
    });
});
