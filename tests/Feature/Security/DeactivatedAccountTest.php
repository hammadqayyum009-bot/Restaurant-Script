<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Regression test: AuthController::login() never checked is_active, so
 * deactivating a customer (Admin\UserController) did nothing on the
 * storefront — the account could still sign in with its existing password.
 * And because the plain `auth` middleware never re-checks is_active either,
 * an already-open session for a customer deactivated mid-session kept
 * working indefinitely, not just until their next login attempt.
 */
class DeactivatedAccountTest extends TestCase
{
    use RefreshDatabase;

    protected function deactivatedCustomer(string $password = 'password123'): User
    {
        return User::factory()->create([
            'is_admin' => false,
            'is_active' => false,
            'password' => Hash::make($password),
        ]);
    }

    public function test_a_deactivated_customer_cannot_log_in(): void
    {
        $user = $this->deactivatedCustomer();

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertFalse(Auth::check(), 'A deactivated account must never end up authenticated, even momentarily.');
    }

    public function test_an_already_logged_in_customer_is_signed_out_on_their_next_request_once_deactivated(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'is_active' => true]);

        $this->actingAs($user)->get(route('profile.show'))->assertOk();

        $user->update(['is_active' => false]);

        $response = $this->get(route('profile.show'));

        $response->assertRedirect(route('login'));
        $this->assertFalse(Auth::check(), 'Deactivation must end the session on the very next request, not just block future logins.');
    }

    public function test_an_active_customer_can_still_log_in_normally(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'is_active' => true,
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('profile.show'));
        $this->assertTrue(Auth::check());
    }
}
