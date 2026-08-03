<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Regression test: /admin/users/{id}/edit, reachable for an admin's own
 * account, accepted a bare password/password_confirmation with no
 * current_password check — a session-riding or CSRF path straight to a full
 * account takeover, unlike /admin/profile, which was already correctly
 * gated. Fixed by applying the same check UserController::update() already
 * uses elsewhere, but only when the target is the acting admin themselves.
 */
class AdminSelfPasswordChangeTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(string $password = 'OldPassword123!'): User
    {
        return User::factory()->create(['is_admin' => true, 'is_active' => true, 'password' => Hash::make($password)]);
    }

    public function test_an_admin_cannot_change_their_own_password_via_the_users_route_without_the_current_password(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->put(route('admin.users.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'password' => 'NewPassword456!',
            'password_confirmation' => 'NewPassword456!',
        ]);

        $response->assertSessionHasErrors('current_password');
        $this->assertTrue(Hash::check('OldPassword123!', $admin->fresh()->password), 'The password must not have changed.');
    }

    public function test_an_admin_cannot_change_their_own_password_with_the_wrong_current_password(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->put(route('admin.users.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'current_password' => 'DefinitelyWrong!',
            'password' => 'NewPassword456!',
            'password_confirmation' => 'NewPassword456!',
        ]);

        $response->assertSessionHasErrors('current_password');
        $this->assertTrue(Hash::check('OldPassword123!', $admin->fresh()->password));
    }

    public function test_an_admin_can_change_their_own_password_with_the_correct_current_password(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->put(route('admin.users.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'current_password' => 'OldPassword123!',
            'password' => 'NewPassword456!',
            'password_confirmation' => 'NewPassword456!',
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertTrue(Hash::check('NewPassword456!', $admin->fresh()->password));
    }

    public function test_editing_a_different_admins_account_is_unaffected_by_this_check(): void
    {
        $actor = $this->admin();
        $other = User::factory()->create(['is_admin' => true, 'is_active' => true, 'password' => Hash::make('WhateverTheyHad!')]);

        $response = $this->actingAs($actor)->put(route('admin.users.update', $other), [
            'name' => $other->name,
            'email' => $other->email,
            'password' => 'ResetByAdmin789!',
            'password_confirmation' => 'ResetByAdmin789!',
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertTrue(
            Hash::check('ResetByAdmin789!', $other->fresh()->password),
            "Resetting a different admin's forgotten password must still work with no current_password needed.",
        );
    }

    public function test_updating_other_fields_without_touching_the_password_does_not_require_current_password(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->put(route('admin.users.update', $admin), [
            'name' => 'New Name Only',
            'email' => $admin->email,
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertSame('New Name Only', $admin->fresh()->name);
    }
}
