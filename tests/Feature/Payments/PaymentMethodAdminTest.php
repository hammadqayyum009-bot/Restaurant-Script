<?php

namespace Tests\Feature\Payments;

use App\Models\PaymentMethod;
use App\Models\PaymentTransaction;
use App\Models\User;
use App\Payments\Contracts\PaymentDriver;
use App\Payments\PaymentDriverRegistry;
use App\Payments\ValueObjects\DriverDisplayInfo;
use App\Payments\ValueObjects\PaymentCallbackResult;
use App\Payments\ValueObjects\PaymentInitiationResult;
use App\Payments\ValueObjects\PaymentVerificationResult;
use App\Payments\ValueObjects\PaymentWebhookResult;
use App\Payments\ValueObjects\RefundResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * This app has no multi-tenancy, so there is no "tenant A vs tenant B"
 * ownership boundary to test on payment_methods — it is a single, shared
 * admin resource. The equivalent, meaningful boundary here is: nobody who
 * isn't an active admin can reach any payment admin route.
 */
class PaymentMethodAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        return User::factory()->create(['is_admin' => true, 'is_active' => true]);
    }

    public function test_a_guest_cannot_view_the_payment_methods_screen(): void
    {
        $response = $this->get(route('admin.settings.payment-methods'));

        $response->assertRedirect(route('admin.login'));
    }

    public function test_a_non_admin_user_cannot_view_the_payment_methods_screen(): void
    {
        $customer = User::factory()->create(['is_admin' => false, 'is_active' => true]);

        $response = $this->actingAs($customer, 'web')->get(route('admin.settings.payment-methods'));

        $response->assertRedirect(route('admin.login'));
    }

    public function test_a_deactivated_admin_cannot_view_the_payment_methods_screen(): void
    {
        $deactivated = User::factory()->create(['is_admin' => true, 'is_active' => false]);

        $response = $this->actingAs($deactivated, 'web')->get(route('admin.settings.payment-methods'));

        $response->assertRedirect(route('admin.login'));
    }

    public function test_an_active_admin_can_view_the_payment_methods_screen(): void
    {
        PaymentMethod::factory()->create(['driver' => 'cod']);

        $response = $this->actingAs($this->admin(), 'web')->get(route('admin.settings.payment-methods'));

        $response->assertOk();
    }

    public function test_a_non_admin_cannot_toggle_a_payment_method(): void
    {
        $method = PaymentMethod::factory()->create(['driver' => 'cod', 'enabled' => false]);
        $customer = User::factory()->create(['is_admin' => false, 'is_active' => true]);

        $response = $this->actingAs($customer, 'web')->put(route('admin.settings.payment-methods.toggle', $method));

        $response->assertRedirect(route('admin.login'));
        $this->assertFalse($method->fresh()->enabled);
    }

    public function test_enabling_a_method_that_is_not_configured_is_rejected(): void
    {
        $registry = app(PaymentDriverRegistry::class);
        $registry->register(new class implements PaymentDriver
        {
            public function identifier(): string
            {
                return 'unconfigurable-admin-test-driver';
            }

            public function displayInfo(): DriverDisplayInfo
            {
                return new DriverDisplayInfo('Test', 'Test');
            }

            public function supportedCurrencies(): array
            {
                return ['SAR'];
            }

            public function isConfigured(PaymentMethod $method): bool
            {
                return false;
            }

            public function initiate(PaymentTransaction $t, PaymentMethod $m): PaymentInitiationResult
            {
                throw new \LogicException('not used');
            }

            public function handleCallback(PaymentMethod $m, Request $r): PaymentCallbackResult
            {
                throw new \LogicException('not used');
            }

            public function handleWebhook(PaymentMethod $m, Request $r): PaymentWebhookResult
            {
                throw new \LogicException('not used');
            }

            public function verify(PaymentTransaction $t, PaymentMethod $m): PaymentVerificationResult
            {
                throw new \LogicException('not used');
            }

            public function supportsRefund(): bool
            {
                return false;
            }

            public function refund(PaymentTransaction $t, PaymentMethod $m, int $a, string $r): RefundResult
            {
                throw new \LogicException('not used');
            }
        });

        $method = PaymentMethod::factory()->create(['driver' => 'unconfigurable-admin-test-driver', 'enabled' => false]);

        $response = $this->actingAs($this->admin(), 'web')->put(route('admin.settings.payment-methods.toggle', $method));

        $response->assertRedirect();
        $this->assertFalse($method->fresh()->enabled);
    }

    public function test_reordering_persists_the_new_sort_order(): void
    {
        $first = PaymentMethod::factory()->create(['driver' => 'cod', 'sort_order' => 0]);
        $second = PaymentMethod::factory()->create(['driver' => 'cod-test-2', 'sort_order' => 1]);

        $response = $this->actingAs($this->admin(), 'web')->putJson(route('admin.settings.payment-methods.reorder'), [
            'order' => [$second->id, $first->id],
        ]);

        $response->assertOk();
        $this->assertSame(0, $second->fresh()->sort_order);
        $this->assertSame(1, $first->fresh()->sort_order);
    }
}
