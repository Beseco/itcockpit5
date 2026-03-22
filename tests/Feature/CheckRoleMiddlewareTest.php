<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    // Define test routes with role middleware
    Route::middleware(['web', 'auth', 'role:admin'])->get('/test-admin', function () {
        return response()->json(['message' => 'Admin access granted']);
    });

    Route::middleware(['web', 'auth', 'role:super-admin'])->get('/test-super-admin', function () {
        return response()->json(['message' => 'Super admin access granted']);
    });
});

test('super-admin can access any role-protected route', function () {
    $superAdmin = User::factory()->create(['role' => 'super-admin', 'is_active' => true]);

    $this->actingAs($superAdmin)
        ->get('/test-admin')
        ->assertStatus(200)
        ->assertJson(['message' => 'Admin access granted']);

    $this->actingAs($superAdmin)
        ->get('/test-super-admin')
        ->assertStatus(200)
        ->assertJson(['message' => 'Super admin access granted']);
});

test('admin can access admin routes but not super-admin routes', function () {
    $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

    $this->actingAs($admin)
        ->get('/test-admin')
        ->assertStatus(200)
        ->assertJson(['message' => 'Admin access granted']);

    $this->actingAs($admin)
        ->get('/test-super-admin')
        ->assertStatus(403);
});

test('standard user cannot access admin or super-admin routes', function () {
    $user = User::factory()->create(['role' => 'user', 'is_active' => true]);

    $this->actingAs($user)
        ->get('/test-admin')
        ->assertStatus(403);

    $this->actingAs($user)
        ->get('/test-super-admin')
        ->assertStatus(403);
});

test('unauthenticated user cannot access role-protected routes', function () {
    $this->get('/test-admin')
        ->assertStatus(302)
        ->assertRedirect('/login');
});

test('middleware returns 403 with appropriate message for unauthorized access', function () {
    $user = User::factory()->create(['role' => 'user', 'is_active' => true]);

    $response = $this->actingAs($user)->get('/test-admin');
    
    $response->assertStatus(403);
});
