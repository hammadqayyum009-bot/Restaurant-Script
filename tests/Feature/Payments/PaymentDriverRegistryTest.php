<?php

namespace Tests\Feature\Payments;

use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\PaymentTransaction;
use App\Payments\Contracts\PaymentDriver;
use App\Payments\Exceptions\UnknownPaymentDriverException;
use App\Payments\PaymentDriverRegistry;
use App\Payments\ValueObjects\DriverDisplayInfo;
use App\Payments\ValueObjects\PaymentCallbackResult;
use App\Payments\ValueObjects\PaymentInitiationResult;
use App\Payments\ValueObjects\PaymentVerificationResult;
use App\Payments\ValueObjects\PaymentWebhookResult;
use App\Payments\ValueObjects\RefundResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Tests\TestCase;

class PaymentDriverRegistryTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_registered_driver_resolves_by_identifier(): void
    {
        $registry = app(PaymentDriverRegistry::class);

        $this->assertTrue($registry->has('cod'));
        $this->assertSame('cod', $registry->get('cod')->identifier());
    }

    public function test_an_unknown_driver_identifier_throws(): void
    {
        $registry = app(PaymentDriverRegistry::class);

        $this->assertFalse($registry->has('does-not-exist'));

        $this->expectException(UnknownPaymentDriverException::class);
        $registry->get('does-not-exist');
    }

    public function test_a_disabled_method_is_never_returned_as_available(): void
    {
        PaymentMethod::factory()->create(['driver' => 'cod', 'enabled' => false]);
        $order = $this->makeOrder(['total' => 50, 'order_type' => 'delivery']);

        $available = app(PaymentDriverRegistry::class)->availableFor($order);

        $this->assertTrue($available->isEmpty());
    }

    public function test_an_unconfigured_method_is_never_returned_as_available(): void
    {
        $registry = app(PaymentDriverRegistry::class);
        $registry->register($this->fakeUnconfigurableDriver());

        PaymentMethod::factory()->create(['driver' => 'unconfigurable-test-driver', 'enabled' => true]);
        $order = $this->makeOrder(['total' => 50, 'order_type' => 'delivery']);

        $available = $registry->availableFor($order);

        $this->assertTrue($available->isEmpty());
    }

    /**
     * App\Models\Order has no HasFactory trait — every test in this app
     * builds one with a direct Order::create() call instead.
     */
    protected function makeOrder(array $overrides = []): Order
    {
        return Order::create(array_merge([
            'order_number' => 'ORD-'.strtoupper(Str::random(8)),
            'customer_name' => 'Test Customer',
            'phone' => '0500000000',
            'address' => 'Test address',
            'order_type' => 'delivery',
            'subtotal' => 50, 'delivery_fee' => 0, 'tax' => 0, 'total' => 50,
            'payment_method' => 'cash', 'status' => 'pending',
        ], $overrides));
    }

    protected function fakeUnconfigurableDriver(): PaymentDriver
    {
        return new class implements PaymentDriver
        {
            public function identifier(): string
            {
                return 'unconfigurable-test-driver';
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

            public function initiate(PaymentTransaction $transaction, PaymentMethod $method): PaymentInitiationResult
            {
                return PaymentInitiationResult::noRedirectRequired();
            }

            public function handleCallback(PaymentMethod $method, Request $request): PaymentCallbackResult
            {
                throw new \LogicException('not used in this test');
            }

            public function handleWebhook(PaymentMethod $method, Request $request): PaymentWebhookResult
            {
                throw new \LogicException('not used in this test');
            }

            public function verify(PaymentTransaction $transaction, PaymentMethod $method): PaymentVerificationResult
            {
                throw new \LogicException('not used in this test');
            }

            public function supportsRefund(): bool
            {
                return false;
            }

            public function refund(PaymentTransaction $transaction, PaymentMethod $method, int $amountMinor, string $reason): RefundResult
            {
                throw new \LogicException('not used in this test');
            }
        };
    }
}
