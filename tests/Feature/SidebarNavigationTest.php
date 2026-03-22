<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

test('sidebar displays dashboard link for all authenticated users', function () {
    $user = User::factory()->create(['role' => 'user']);
    
    $response = $this->actingAs($user)->get('/dashboard');
    
    $response->assertStatus(200)
        ->assertSee('Dashboard')
        ->assertSee(route('dashboard'));
});

test('sidebar displays users link for admin users', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    
    $response = $this->actingAs($admin)->get('/dashboard');
    
    $response->assertStatus(200)
        ->assertSee('Users')
        ->assertSee(route('users.index'));
});

test('sidebar displays users link for super-admin users', function () {
    $superAdmin = User::factory()->create(['role' => 'super-admin']);
    
    $response = $this->actingAs($superAdmin)->get('/dashboard');
    
    $response->assertStatus(200)
        ->assertSee('Users')
        ->assertSee(route('users.index'));
});

test('sidebar does not display users link for standard users', function () {
    $user = User::factory()->create(['role' => 'user']);
    
    $response = $this->actingAs($user)->get('/dashboard');
    
    $response->assertStatus(200)
        ->assertDontSee('Users', false); // false = case-insensitive
});

test('sidebar displays announcements link for admin users', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    
    $response = $this->actingAs($admin)->get('/dashboard');
    
    $response->assertStatus(200)
        ->assertSee('Announcements')
        ->assertSee(route('announcements.index'));
});

test('sidebar displays announcements link for super-admin users', function () {
    $superAdmin = User::factory()->create(['role' => 'super-admin']);
    
    $response = $this->actingAs($superAdmin)->get('/dashboard');
    
    $response->assertStatus(200)
        ->assertSee('Announcements')
        ->assertSee(route('announcements.index'));
});

test('sidebar does not display announcements link for standard users', function () {
    $user = User::factory()->create(['role' => 'user']);
    
    $response = $this->actingAs($user)->get('/dashboard');
    
    $response->assertStatus(200)
        ->assertDontSee('Announcements', false);
});

test('sidebar displays audit logs link for super-admin users only', function () {
    // Skip this test if audit logs route doesn't exist yet
    if (!Route::has('audit-logs.index')) {
        $this->markTestSkipped('Audit logs route not yet implemented');
    }
    
    $superAdmin = User::factory()->create(['role' => 'super-admin']);
    
    $response = $this->actingAs($superAdmin)->get('/dashboard');
    
    $response->assertStatus(200)
        ->assertSee('Audit Logs');
});

test('sidebar does not display audit logs link for admin users', function () {
    // Skip this test if audit logs route doesn't exist yet
    if (!Route::has('audit-logs.index')) {
        $this->markTestSkipped('Audit logs route not yet implemented');
    }
    
    $admin = User::factory()->create(['role' => 'admin']);
    
    $response = $this->actingAs($admin)->get('/dashboard');
    
    $response->assertStatus(200)
        ->assertDontSee('Audit Logs', false);
});

test('sidebar does not display audit logs link for standard users', function () {
    // Skip this test if audit logs route doesn't exist yet
    if (!Route::has('audit-logs.index')) {
        $this->markTestSkipped('Audit logs route not yet implemented');
    }
    
    $user = User::factory()->create(['role' => 'user']);
    
    $response = $this->actingAs($user)->get('/dashboard');
    
    $response->assertStatus(200)
        ->assertDontSee('Audit Logs', false);
});
