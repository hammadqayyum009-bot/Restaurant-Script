<?php

namespace Tests\Feature\Security;

use App\Models\User;
use App\Services\AdminUserDeleter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * UserController::destroy() previously did a plain check-then-act ("if
 * is_admin && count <= 1, refuse") with no lock — racy under two concurrent
 * requests (see LastAdminDeletionConcurrencySqliteTest /
 * LastAdminDeletionConcurrencyMysqlTest for the real-concurrency proof, via
 * two different admins racing each other). This covers the guard's ordinary,
 * single-request behavior.
 *
 * Exercised directly against AdminUserDeleter rather than through
 * UserController::destroy()'s HTTP route for the core invariant tests below:
 * the controller's own self-delete guard ("you cannot delete the account
 * you are signed in with") fires before the last-admin guard ever would,
 * and the last-admin guard's true target — "the acting admin's sole
 * *other* admin" — always keeps the acting admin's own row in the count too,
 * so a same-request HTTP call can never actually observe count <= 1 for a
 * different target. The service is what actually owns the invariant; the
 * HTTP tests below cover the controller wiring around it.
 */
class LastAdminDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_deleter_refuses_to_delete_the_sole_remaining_admin(): void
    {
        $soleAdmin = User::factory()->create(['is_admin' => true, 'is_active' => true]);

        $deleted = app(AdminUserDeleter::class)->delete($soleAdmin);

        $this->assertFalse($deleted);
        $this->assertDatabaseHas('users', ['id' => $soleAdmin->id]);
    }

    public function test_the_deleter_allows_deleting_an_admin_when_another_remains(): void
    {
        User::factory()->create(['is_admin' => true, 'is_active' => true]);
        $target = User::factory()->create(['is_admin' => true, 'is_active' => true]);

        $deleted = app(AdminUserDeleter::class)->delete($target);

        $this->assertTrue($deleted);
        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }

    public function test_the_deleter_never_blocks_a_non_admin_regardless_of_admin_count(): void
    {
        $soleAdmin = User::factory()->create(['is_admin' => true, 'is_active' => true]);
        $customer = User::factory()->create(['is_admin' => false, 'is_active' => true]);

        // Only one admin exists in the whole table — the guard must still
        // never apply, since the target itself is not an admin.
        $this->assertSame(1, User::where('is_admin', true)->count());

        $deleted = app(AdminUserDeleter::class)->delete($customer);

        $this->assertTrue($deleted);
        $this->assertDatabaseMissing('users', ['id' => $customer->id]);
        $this->assertDatabaseHas('users', ['id' => $soleAdmin->id]);
    }

    public function test_an_admin_can_delete_a_different_admin_via_the_http_route_when_another_remains(): void
    {
        $actor = User::factory()->create(['is_admin' => true, 'is_active' => true]);
        $otherAdmin = User::factory()->create(['is_admin' => true, 'is_active' => true]);

        $response = $this->actingAs($actor, 'web')->delete(route('admin.users.destroy', $otherAdmin));

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseMissing('users', ['id' => $otherAdmin->id]);
    }

    public function test_an_admin_cannot_delete_their_own_account_even_as_the_sole_admin(): void
    {
        $soleAdmin = User::factory()->create(['is_admin' => true, 'is_active' => true]);

        $response = $this->actingAs($soleAdmin, 'web')->delete(route('admin.users.destroy', $soleAdmin));

        $response->assertRedirect();
        $response->assertSessionHas('error', 'You cannot delete the account you are signed in with.');
        $this->assertDatabaseHas('users', ['id' => $soleAdmin->id]);
    }

    public function test_a_non_admin_user_can_always_be_deleted_via_the_http_route(): void
    {
        $actor = User::factory()->create(['is_admin' => true, 'is_active' => true]);
        $customer = User::factory()->create(['is_admin' => false, 'is_active' => true]);

        $response = $this->actingAs($actor, 'web')->delete(route('admin.users.destroy', $customer));

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseMissing('users', ['id' => $customer->id]);
    }
}
