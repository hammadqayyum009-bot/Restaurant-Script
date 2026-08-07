<?php

namespace Tests\Feature\Payments\Tap;

use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\PaymentTransaction;
use App\Payments\Drivers\TapDriver;
use App\Payments\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Tap sends amounts as a decimal string matched to the currency's own
 * decimal places (e.g. "5.500" for KWD), not integer minor units the way
 * Moyasar does. Our internal storage stays integer minor units unchanged —
 * only this outbound conversion differs. Every test here asserts the exact
 * string in the raw HTTP request body, not a parsed/cast value, because a
 * PHP float silently drops the trailing zero Tap's docs say it rejects
 * ("5.500" -> 5.5 -> "5.5").
 */
class TapChargeCreationTest extends TestCase
{
    use RefreshDatabase;

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

    protected function tapMethod(): PaymentMethod
    {
        return PaymentMethod::factory()->create([
            'driver' => 'tap',
            'enabled' => true,
            'test_mode' => true,
            'credentials' => ['secret_key_test' => 'sk_test_fake'],
        ]);
    }

    protected function transactionFor(int $amountMinor, string $currency): PaymentTransaction
    {
        $order = $this->makeOrder();
        $method = $this->tapMethod();

        return PaymentTransaction::create([
            'order_id' => $order->id,
            'payment_method_id' => $method->id,
            'driver' => 'tap',
            'amount_minor' => $amountMinor,
            'currency' => $currency,
            'status' => 'pending',
        ]);
    }

    /**
     * @return array<string, array{0: string, 1: int, 2: string}>
     */
    public static function currencyProvider(): array
    {
        return [
            'SAR (2dp)' => ['SAR', 5000, '50.00'],
            'AED (2dp)' => ['AED', 12345, '123.45'],
            'QAR (2dp)' => ['QAR', 100, '1.00'],
            'USD (2dp)' => ['USD', 999, '9.99'],
            'KWD (3dp)' => ['KWD', 5500, '5.500'],
            'BHD (3dp)' => ['BHD', 3000, '3.000'],
            'OMR (3dp)' => ['OMR', 1250, '1.250'],
        ];
    }

    /**
     * @dataProvider currencyProvider
     */
    public function test_the_exact_decimal_string_is_sent_for_every_supported_currency(string $currency, int $amountMinor, string $expectedDecimal): void
    {
        Http::fake([
            'api.tap.company/v2/charges' => Http::response(['id' => 'chg_1', 'transaction' => ['url' => 'https://tap.company/pay/chg_1']], 200),
        ]);

        $transaction = $this->transactionFor($amountMinor, $currency);
        $method = $transaction->paymentMethod;

        (new TapDriver)->initiate($transaction, $method);

        Http::assertSent(function ($request) use ($expectedDecimal, $currency) {
            // Checked against the raw request body text, not the parsed
            // array — a parsed comparison ($request['amount'] === "5.500")
            // would still pass even if the value had been cast through a
            // float and lost its trailing zero, because PHP's loose
            // comparison and json_decode() can mask exactly that failure.
            // The raw body is the only place a float-cast is unambiguously
            // visible: it would serialize as 5.5, not "5.500".
            return str_contains($request->body(), '"amount":"'.$expectedDecimal.'"')
                && str_contains($request->body(), '"currency":"'.$currency.'"');
        });
    }

    /**
     * @dataProvider currencyProvider
     */
    public function test_the_amount_round_trips_back_to_the_original_minor_units(string $currency, int $amountMinor, string $expectedDecimal): void
    {
        $decimal = Money::toDecimal($amountMinor, $currency);
        $this->assertSame($expectedDecimal, $decimal);

        $roundTripped = Money::toMinor($decimal, $currency);
        $this->assertSame($amountMinor, $roundTripped);
    }

    public function test_the_amount_field_is_never_a_bare_json_number(): void
    {
        Http::fake([
            'api.tap.company/v2/charges' => Http::response(['id' => 'chg_2', 'transaction' => ['url' => 'https://tap.company/pay/chg_2']], 200),
        ]);

        $transaction = $this->transactionFor(5500, 'KWD');
        $method = $transaction->paymentMethod;

        (new TapDriver)->initiate($transaction, $method);

        Http::assertSent(function ($request) {
            // A float-cast amount would serialize as "amount":5.5 (a bare,
            // unquoted JSON number with the trailing zero already lost).
            // This must never appear.
            $this->assertStringNotContainsString('"amount":5.5', $request->body());
            $this->assertStringNotContainsString('"amount":5.500', $request->body());

            return true;
        });
    }

    public function test_the_amount_sent_to_tap_always_comes_from_the_stored_transaction_never_the_request(): void
    {
        Http::fake([
            'api.tap.company/v2/charges' => Http::response(['id' => 'chg_3', 'transaction' => ['url' => 'https://tap.company/pay/chg_3']], 200),
        ]);

        $transaction = $this->transactionFor(5000, 'SAR');
        $method = $transaction->paymentMethod;

        // Simulates a tampered request carrying a completely different
        // amount — the driver never reads request input at all.
        $this->get('/?amount=1');

        (new TapDriver)->initiate($transaction, $method);

        Http::assertSent(fn ($request) => str_contains($request->body(), '"amount":"50.00"'));
    }

    public function test_initiate_returns_a_redirect_result_when_a_transaction_url_is_present(): void
    {
        Http::fake([
            'api.tap.company/v2/charges' => Http::response(['id' => 'chg_4', 'transaction' => ['url' => 'https://tap.company/pay/chg_4']], 200),
        ]);

        $transaction = $this->transactionFor(5000, 'SAR');
        $method = $transaction->paymentMethod;

        $result = (new TapDriver)->initiate($transaction, $method);

        $this->assertTrue($result->requiresRedirect);
        $this->assertSame('https://tap.company/pay/chg_4', $result->redirectUrl);
    }

    /**
     * Tap can complete a charge synchronously (no 3-D Secure step), in
     * which case the response has no transaction.url. This is the same
     * PaymentInitiationResult factory Cash on Delivery uses for a
     * different reason — evidence the Phase 1 value object was not shaped
     * narrowly around Moyasar's always-a-redirect flow.
     */
    public function test_initiate_returns_no_redirect_required_when_the_charge_completes_synchronously(): void
    {
        Http::fake([
            'api.tap.company/v2/charges' => Http::response(['id' => 'chg_5', 'status' => 'CAPTURED'], 200),
        ]);

        $transaction = $this->transactionFor(5000, 'SAR');
        $method = $transaction->paymentMethod;

        $result = (new TapDriver)->initiate($transaction, $method);

        $this->assertFalse($result->requiresRedirect);
        $this->assertNull($result->redirectUrl);
    }
}
