<?php

namespace Tests\Feature\Billing;

use App\Exceptions\Billing\ReconciliationFailedException;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Billing\Reconciler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReconciliationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Integer-cents helpers so fixture totals are built without bcmath
     * (not installed in every environment, this one included) and without a
     * single float touching a money value — same discipline as the module's
     * own Money class, just inlined here since prices are always 2dp.
     */
    protected function cents(string $decimal): int
    {
        [$int, $frac] = array_pad(explode('.', $decimal, 2), 2, '');

        return ((int) $int) * 100 + (int) str_pad(substr($frac, 0, 2), 2, '0');
    }

    protected function decimal(int $cents): string
    {
        return sprintf('%d.%02d', intdiv($cents, 100), $cents % 100);
    }

    /**
     * Builds a self-consistent order: item lines sum to subtotal, and
     * subtotal + delivery + tax sum exactly to total — the state every order
     * is in immediately after checkout.
     */
    protected function consistentOrder(array $items, string $deliveryFee = '10.00', string $tax = '0.00'): Order
    {
        $subtotalCents = 0;

        $order = \Database\Factories\OrderFactory::new()->create([
            'subtotal' => 0,
            'delivery_fee' => $deliveryFee,
            'tax' => $tax,
            'total' => 0,
        ]);

        foreach ($items as [$name, $price, $qty]) {
            $lineTotalCents = $this->cents($price) * $qty;
            $subtotalCents += $lineTotalCents;

            \Database\Factories\OrderItemFactory::new()->create([
                'order_id' => $order->id,
                'name' => $name,
                'price' => $price,
                'quantity' => $qty,
                'line_total' => $this->decimal($lineTotalCents),
            ]);
        }

        $totalCents = $subtotalCents + $this->cents($deliveryFee) + $this->cents($tax);

        $order->update(['subtotal' => $this->decimal($subtotalCents), 'total' => $this->decimal($totalCents)]);

        return $order->fresh();
    }

    /** @return array<string, array{array<int, array{0: string, 1: string, 2: int}>, string, string}> */
    public static function orderScenarios(): array
    {
        return [
            'single item, no delivery' => [[['Mandi', '19.99', 1]], '0.00', '0.00'],
            'two items' => [[['Mandi', '19.99', 2], ['Karak', '3.50', 3]], '10.00', '0.00'],
            'many items' => [[['A', '9.99', 1], ['B', '15.50', 2], ['C', '7.25', 3], ['D', '22.00', 1]], '12.00', '0.00'],
            'odd pricing' => [[['X', '0.10', 7], ['Y', '0.70', 3]], '5.55', '0.00'],
            'with storefront tax' => [[['Mandi', '19.99', 2]], '10.00', '6.00'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('orderScenarios')]
    public function test_inclusive_mode_grand_total_always_equals_order_total(array $items, string $deliveryFee, string $tax): void
    {
        $order = $this->consistentOrder($items, $deliveryFee, $tax);
        $reconciler = app(Reconciler::class);

        $result = $reconciler->reconcileOrder($order, 'AED', 1500, true);

        $expected = \App\Services\Billing\Money::toMinor((string) $order->getRawOriginal('total'), 'AED');
        $this->assertSame($expected, $result['grand_total_minor']);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('orderScenarios')]
    public function test_exclusive_mode_grand_total_always_equals_order_total(array $items, string $deliveryFee, string $tax): void
    {
        $order = $this->consistentOrder($items, $deliveryFee, $tax);
        $reconciler = app(Reconciler::class);

        $result = $reconciler->reconcileOrder($order, 'AED', 1500, false);

        $expected = \App\Services\Billing\Money::toMinor((string) $order->getRawOriginal('total'), 'AED');
        $this->assertSame($expected, $result['grand_total_minor']);
    }

    public function test_inclusive_mode_vat_is_back_calculated_not_recomputed(): void
    {
        $order = $this->consistentOrder([['Mandi', '115.00', 1]], '0.00', '0.00');
        $reconciler = app(Reconciler::class);

        $result = $reconciler->reconcileOrder($order, 'AED', 1500, true);

        // 11500 minor gross at 15% => 1500 VAT, 10000 net, by the closed-form
        // back-calculation — not a re-derivation from list prices.
        $this->assertSame(1500, $result['vat_total_minor']);
        $this->assertSame(10000, $result['subtotal_net_minor']);
    }

    public function test_exclusive_mode_reports_exactly_the_orders_recorded_tax(): void
    {
        $order = $this->consistentOrder([['Mandi', '100.00', 1]], '10.00', '15.00');
        $reconciler = app(Reconciler::class);

        $result = $reconciler->reconcileOrder($order, 'AED', 1500, false);

        // orders.tax was recorded as 15.00 — the reconciler must report
        // exactly that, never an independently recomputed VAT figure.
        $this->assertSame(1500, $result['vat_total_minor']);
    }

    public function test_delivery_fee_is_its_own_taxable_line_in_inclusive_mode(): void
    {
        $order = $this->consistentOrder([['Mandi', '100.00', 1]], '10.00', '0.00');
        $reconciler = app(Reconciler::class);

        $result = $reconciler->reconcileOrder($order, 'AED', 1500, true);

        $deliveryLines = array_filter($result['lines'], fn ($l) => $l['is_delivery_fee']);
        $this->assertCount(1, $deliveryLines);

        $deliveryLine = array_values($deliveryLines)[0];
        $this->assertGreaterThan(0, $deliveryLine['line_vat_minor'], 'Delivery is taxed like everything else in inclusive mode.');
    }

    public function test_delivery_fee_is_out_of_scope_in_exclusive_mode(): void
    {
        $order = $this->consistentOrder([['Mandi', '100.00', 1]], '10.00', '15.00');
        $reconciler = app(Reconciler::class);

        $result = $reconciler->reconcileOrder($order, 'AED', 1500, false);

        $deliveryLines = array_filter($result['lines'], fn ($l) => $l['is_delivery_fee']);
        $deliveryLine = array_values($deliveryLines)[0];

        // The storefront never taxed delivery — the document must not invent
        // VAT that was never collected.
        $this->assertSame(0, $deliveryLine['line_vat_minor']);
        $this->assertSame('O', $deliveryLine['vat_category']);
    }

    public function test_a_corrupted_order_refuses_to_issue_and_names_the_discrepancy(): void
    {
        $order = $this->consistentOrder([['Mandi', '19.99', 2]], '10.00', '0.00');

        // Tamper with the order after the fact: total no longer matches
        // items + delivery + tax.
        $order->update(['total' => $this->decimal($this->cents((string) $order->total) + 5000)]);

        $reconciler = app(Reconciler::class);

        $this->expectException(ReconciliationFailedException::class);
        $this->expectExceptionMessageMatches('/difference of/');

        $reconciler->reconcileOrder($order->fresh(), 'AED', 1500, true);
    }

    public function test_a_failed_reconciliation_creates_no_document_and_consumes_no_number(): void
    {
        $order = $this->consistentOrder([['Mandi', '19.99', 2]], '10.00', '0.00');
        $order->update(['total' => $this->decimal($this->cents((string) $order->total) + 5000)]);

        $countBefore = \App\Models\BillingDocument::count();
        $counterBefore = \App\Models\BillingDocumentCounter::count();

        try {
            app(Reconciler::class)->reconcileOrder($order->fresh(), 'AED', 1500, true);
        } catch (ReconciliationFailedException) {
            // expected
        }

        $this->assertSame($countBefore, \App\Models\BillingDocument::count());
        $this->assertSame($counterBefore, \App\Models\BillingDocumentCounter::count());
    }
}
