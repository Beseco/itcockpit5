<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('password is automatically hashed when creating a user', function () {
    $plainPassword = 'password123';
    
    $user = User::factory()->create([
        'password' => $plainPassword,
    ]);
    
    // Verify the stored password is not the plain text
    expect($user->password)->not->toBe($plainPassword);
    
    // Verify the password can be checked with Hash::check
    expect(Hash::check($plainPassword, $user->password))->toBeTrue();
});

test('password is automatically hashed when updating a user', function () {
    $user = User::factory()->create();
    
    $newPassword = 'newpassword456';
    $user->password = $newPassword;
    $user->save();
    
    // Refresh from database
    $user->refresh();
    
    // Verify the stored password is not the plain text
    expect($user->password)->not->toBe($newPassword);
    
    // Verify the password can be checked with Hash::check
    expect(Hash::check($newPassword, $user->password))->toBeTrue();
});
