<?php

namespace Tests\Feature\Billing;

use App\Models\BillingDocument;
use App\Models\Order;
use App\Models\User;
use App\Services\Billing\DocumentIssuer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * Batch 5 — tests 55-60. Everything else in the Billing suite logs in as an
 * admin and either succeeds or hits a document-state rule (ImmutabilityTest,
 * CreditNoteTest 53/54); only one route (StandaloneDocumentTest's guest
 * check) has ever had its *identity* gate exercised at all, and only for a
 * guest. This file is what's missing: every billing route swept for guests,
 * non-admins, and inactive admins, plus the manage-billing Gate and the
 * BillingDocumentPolicy abilities tested directly rather than only as a
 * side effect of some other test's happy path.
 */
class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        return User::factory()->create(['is_admin' => true, 'is_active' => true]);
    }

    protected function nonAdmin(): User
    {
        return User::factory()->create(['is_admin' => false, 'is_active' => true]);
    }

    protected function inactiveAdmin(): User
    {
        return User::factory()->create(['is_admin' => true, 'is_active' => false]);
    }

    /**
     * One order and one issued document, created directly so every route in
     * the sweep below has something real to resolve — the point of these
     * tests is who is let in, not what happens once they are.
     *
     * @return array{0: Order, 1: BillingDocument}
     */
    protected function fixtures(): array
    {
        $order = \Database\Factories\OrderFactory::new()->create(['subtotal' => 100, 'delivery_fee' => 0, 'tax' => 0, 'total' => 100]);
        \Database\Factories\OrderItemFactory::new()->create(['order_id' => $order->id, 'price' => 100, 'quantity' => 1, 'line_total' => 100]);

        $document = app(DocumentIssuer::class)->createDraftFromOrder($order, 'simplified_tax_invoice');
        $document = app(DocumentIssuer::class)->issue($document, $this->admin());

        return [$order, $document];
    }

    /**
     * Every route this module exposes, as [routeName, httpMethod] pairs.
     * {order}/{document} are substituted with real fixture ids at call time.
     *
     * @return array<int, array{0: string, 1: string}>
     */
    protected function allBillingRoutes(): array
    {
        return [
            ['admin.billing.from-order.create', 'GET'],
            ['admin.billing.from-order.store', 'POST'],
            ['admin.billing.index', 'GET'],
            ['admin.billing.create', 'GET'],
            ['admin.billing.store', 'POST'],
            ['admin.billing.edit', 'GET'],
            ['admin.billing.show', 'GET'],
            ['admin.billing.update', 'PUT'],
            ['admin.billing.destroy', 'DELETE'],
            ['admin.billing.issue', 'POST'],
            ['admin.billing.archive', 'POST'],
            ['admin.billing.print', 'GET'],
            ['admin.billing.credit.create', 'GET'],
            ['admin.billing.credit.store', 'POST'],
        ];
    }

    protected function urlFor(string $routeName, Order $order, BillingDocument $document): string
    {
        return match ($routeName) {
            'admin.billing.from-order.create', 'admin.billing.from-order.store' => route($routeName, $order),
            'admin.billing.index', 'admin.billing.create', 'admin.billing.store' => route($routeName),
            default => route($routeName, $document),
        };
    }

    public function test_a_guest_is_redirected_to_login_from_every_billing_route(): void
    {
        [$order, $document] = $this->fixtures();

        foreach ($this->allBillingRoutes() as [$routeName, $method]) {
            $response = $this->call($method, $this->urlFor($routeName, $order, $document));

            $response->assertRedirect(route('admin.login'));
        }
    }

    public function test_a_logged_in_non_admin_user_is_denied_every_billing_route(): void
    {
        [$order, $document] = $this->fixtures();
        $nonAdmin = $this->nonAdmin();

        foreach ($this->allBillingRoutes() as [$routeName, $method]) {
            $this->actingAs($nonAdmin);

            $response = $this->call($method, $this->urlFor($routeName, $order, $document));

            $response->assertRedirect(route('admin.login'));
            $this->assertFalse(Auth::check(), "{$routeName} should have logged the non-admin out (EnsureAdmin), not just blocked the response.");
        }
    }

    public function test_an_inactive_admin_is_denied_every_billing_route(): void
    {
        [$order, $document] = $this->fixtures();
        $inactiveAdmin = $this->inactiveAdmin();

        foreach ($this->allBillingRoutes() as [$routeName, $method]) {
            $this->actingAs($inactiveAdmin);

            $response = $this->call($method, $this->urlFor($routeName, $order, $document));

            $response->assertRedirect(route('admin.login'));
            $this->assertFalse(Auth::check(), "{$routeName} should have logged the inactive admin out (EnsureAdmin), not just blocked the response.");
        }
    }

    /**
     * The manage-billing Gate (App\Providers\BillingServiceProvider) is
     * defined as `$user->isAdmin()` alone — it does not check is_active.
     * That is not a hole: every billing route sits behind EnsureAdmin, which
     * checks both and logs an inactive admin out before the Gate is ever
     * consulted (see the two tests above). But the Gate itself, tested in
     * isolation, really does still say yes to an inactive admin — documented
     * here deliberately, not left to be discovered by whoever adds the next
     * billing entry point and assumes the Gate alone is the whole guard.
     */
    public function test_the_manage_billing_gate_checks_admin_status_only_not_active_status(): void
    {
        $admin = $this->admin();
        $nonAdmin = $this->nonAdmin();
        $inactiveAdmin = $this->inactiveAdmin();

        $this->assertTrue(Gate::forUser($admin)->allows('manage-billing'));
        $this->assertFalse(Gate::forUser($nonAdmin)->allows('manage-billing'));
        $this->assertTrue(
            Gate::forUser($inactiveAdmin)->allows('manage-billing'),
            'The Gate is is_admin-only by design — is_active enforcement lives in EnsureAdmin, not here. '.
            'If that ever changes, this assertion is meant to be the thing that catches it.',
        );
    }

    /**
     * viewAny/view/create/archive have no document-state rule (unlike
     * update/delete/issue, covered by ImmutabilityTest, and credit, covered
     * by CreditNoteTest) — every existing test only ever exercises them as
     * an admin taking the happy path. Checked directly against the policy
     * here so "requires manage-billing" is actually asserted somewhere for
     * all seven abilities, not just implied by four of them.
     */
    public function test_every_policy_ability_without_a_state_rule_still_requires_manage_billing(): void
    {
        [, $document] = $this->fixtures();
        $admin = $this->admin();
        $nonAdmin = $this->nonAdmin();

        foreach (['viewAny', 'view', 'create', 'archive'] as $ability) {
            $args = $ability === 'viewAny' || $ability === 'create' ? [BillingDocument::class] : [$document];

            $this->assertTrue(Gate::forUser($admin)->allows($ability, $args), "admin should be allowed: {$ability}");
            $this->assertFalse(Gate::forUser($nonAdmin)->allows($ability, $args), "non-admin should be denied: {$ability}");
        }
    }

    /**
     * update/delete/issue read as `manage-billing && isDraft()` — this
     * proves it really is an AND, not an OR a future edit could turn it
     * into by accident. ImmutabilityTest already proves the state half
     * (admin + issued -> denied); this proves the identity half on a
     * document where the state half would otherwise allow it (admin + draft
     * -> allowed, so a non-admin + draft must be denied by identity alone).
     */
    public function test_state_restricted_abilities_deny_a_non_admin_even_on_an_otherwise_eligible_draft(): void
    {
        $admin = $this->admin();
        $draft = app(DocumentIssuer::class)->createDraftStandalone([
            'document_type' => 'quotation',
            'currency' => 'AED',
            'buyer_name_en' => 'Authorization Test Buyer',
            'lines' => [['name_en' => 'Line', 'quantity' => '1', 'unit_price' => '10.00', 'vat_category' => 'S']],
        ]);
        $nonAdmin = $this->nonAdmin();

        foreach (['update', 'delete', 'issue'] as $ability) {
            $this->assertTrue(Gate::forUser($admin)->allows($ability, $draft), "admin should be allowed on a draft: {$ability}");
            $this->assertFalse(Gate::forUser($nonAdmin)->allows($ability, $draft), "non-admin should still be denied on a draft: {$ability}");
        }
    }
}
