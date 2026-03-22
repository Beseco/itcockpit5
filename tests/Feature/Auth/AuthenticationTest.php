<?php

use App\Models\User;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('last_login_at is updated on successful login', function () {
    $user = User::factory()->create([
        'last_login_at' => null,
    ]);

    expect($user->last_login_at)->toBeNull();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $user->refresh();
    
    expect($user->last_login_at)->not->toBeNull();
    expect($user->last_login_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});
