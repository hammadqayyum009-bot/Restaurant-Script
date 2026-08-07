<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Exceptions\Billing\ReconciliationFailedException;
use App\Models\Order;

/**
 * Rule D2: a document's grand total must always equal exactly what the
 * customer was charged. This class reports VAT; it never re-prices an order.
 *
 * Two-phase check:
 *  1. Verify the order's own stored components (items + delivery + tax) still
 *     add up to orders.total. A mismatch beyond a tiny per-line rounding
 *     tolerance means the order was edited after the fact — refuse to issue.
 *  2. Only once that passes, build VAT lines that are mathematically
 *     guaranteed to sum to orders.total exactly (via distribute(), the
 *     largest-remainder method), because orders.total is always the assigned
 *     grand total — never the independently computed line sum.
 */
class Reconciler
{
    public function __construct(protected BillingSettings $settings)
    {
    }

    /**
     * @return array{
     *     lines: array<int, array<string, mixed>>,
     *     subtotal_net_minor: int,
     *     vat_total_minor: int,
     *     grand_total_minor: int,
     *     rounding_adjustment_minor: int,
     * }
     */
    public function reconcileOrder(Order $order, string $currency, int $vatRateBp, bool $inclusive): array
    {
        $order->loadMissing('items');

        $orderTotalMinor = Money::toMinor((string) $order->getRawOriginal('total'), $currency);
        $deliveryFeeMinor = Money::toMinor((string) $order->getRawOriginal('delivery_fee'), $currency);
        $orderTaxMinor = Money::toMinor((string) $order->getRawOriginal('tax'), $currency);

        $rawLines = [];

        foreach ($order->items as $item) {
            $rawLines[] = [
                'name_en' => $item->name,
                'amount_minor' => Money::toMinor((string) $item->getRawOriginal('line_total'), $currency),
                'quantity_milli' => ((int) $item->quantity) * 1000,
                'unit_price_minor' => Money::toMinor((string) $item->getRawOriginal('price'), $currency),
                'is_delivery_fee' => false,
            ];
        }

        if ($deliveryFeeMinor > 0) {
            $rawLines[] = [
                'name_en' => 'Delivery fee',
                'amount_minor' => $deliveryFeeMinor,
                'quantity_milli' => 1000,
                'unit_price_minor' => $deliveryFeeMinor,
                'is_delivery_fee' => true,
            ];
        }

        $itemsAndDeliverySum = array_sum(array_column($rawLines, 'amount_minor'));
        $componentSum = $itemsAndDeliverySum + $orderTaxMinor;
        $lineCount = max(1, count($rawLines));
        $componentDelta = $orderTotalMinor - $componentSum;

        if (abs($componentDelta) > $lineCount) {
            throw new ReconciliationFailedException(sprintf(
                'Cannot issue: order #%s items, delivery fee and tax add up to %s %s but the order total is %s %s, '.
                'a difference of %s %s. This usually means the order was edited after it was placed. Issuing would '.
                'produce a tax invoice that disagrees with the amount charged.',
                $order->order_number,
                $currency,
                Money::toDecimal($componentSum, $currency),
                $currency,
                Money::toDecimal($orderTotalMinor, $currency),
                $currency,
                Money::toDecimal(abs($componentDelta), $currency),
            ));
        }

        $built = $inclusive
            ? $this->buildInclusive($rawLines, $vatRateBp, $orderTotalMinor)
            : $this->buildExclusive($rawLines, $vatRateBp, $orderTaxMinor, $orderTotalMinor);

        $computedTotal = array_sum(array_column($built['lines'], 'line_total_minor'));

        return [
            'lines' => $built['lines'],
            'subtotal_net_minor' => $built['subtotal_net_minor'],
            'vat_total_minor' => $built['vat_total_minor'],
            'grand_total_minor' => $orderTotalMinor,
            'rounding_adjustment_minor' => $orderTotalMinor - $computedTotal,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rawLines
     * @return array{lines: array<int, array<string, mixed>>, subtotal_net_minor: int, vat_total_minor: int}
     */
    protected function buildInclusive(array $rawLines, int $vatRateBp, int $targetTotal): array
    {
        $grossPerLine = $this->distribute(array_column($rawLines, 'amount_minor'), $targetTotal);

        $lines = [];

        foreach ($rawLines as $i => $raw) {
            $calc = VatCalculator::inclusive($grossPerLine[$i], $vatRateBp);
            $lines[] = $this->line($raw, $calc['net'], $calc['vat'], $calc['gross'], $vatRateBp, VatCalculator::CATEGORY_STANDARD);
        }

        return [
            'lines' => $lines,
            'subtotal_net_minor' => array_sum(array_column($lines, 'line_net_minor')),
            'vat_total_minor' => array_sum(array_column($lines, 'line_vat_minor')),
        ];
    }

    /**
     * Delivery is not taxed by the storefront today (see
     * documentation/billing-known-limitations.md) — only non-delivery lines
     * share the recorded order tax.
     *
     * @param  array<int, array<string, mixed>>  $rawLines
     * @return array{lines: array<int, array<string, mixed>>, subtotal_net_minor: int, vat_total_minor: int}
     */
    protected function buildExclusive(array $rawLines, int $vatRateBp, int $orderTaxMinor, int $targetTotal): array
    {
        $netTarget = $targetTotal - $orderTaxMinor;
        $netPerLine = $this->distribute(array_column($rawLines, 'amount_minor'), $netTarget);

        $taxableWeights = [];
        foreach ($rawLines as $i => $raw) {
            $taxableWeights[$i] = $raw['is_delivery_fee'] ? 0 : $netPerLine[$i];
        }
        $vatPerLine = $this->distribute($taxableWeights, $orderTaxMinor);

        $lines = [];

        foreach ($rawLines as $i => $raw) {
            $net = $netPerLine[$i];
            $vat = $raw['is_delivery_fee'] ? 0 : $vatPerLine[$i];
            $rate = $raw['is_delivery_fee'] ? 0 : $vatRateBp;
            $category = $raw['is_delivery_fee'] ? VatCalculator::CATEGORY_OUT_OF_SCOPE : VatCalculator::CATEGORY_STANDARD;

            $lines[] = $this->line($raw, $net, $vat, $net + $vat, $rate, $category);
        }

        return [
            'lines' => $lines,
            'subtotal_net_minor' => array_sum(array_column($lines, 'line_net_minor')),
            'vat_total_minor' => array_sum(array_column($lines, 'line_vat_minor')),
        ];
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return array<string, mixed>
     */
    protected function line(array $raw, int $net, int $vat, int $total, int $rateBp, string $category): array
    {
        return [
            'name_en' => $raw['name_en'],
            'quantity_milli' => $raw['quantity_milli'],
            'unit_price_minor' => $raw['unit_price_minor'],
            'line_net_minor' => $net,
            'line_vat_minor' => $vat,
            'line_total_minor' => $total,
            'vat_rate_bp' => $rateBp,
            'vat_category' => $category,
            'is_delivery_fee' => $raw['is_delivery_fee'],
        ];
    }

    /**
     * Splits $target across $weights proportionally, largest-remainder method,
     * so the parts always sum to exactly $target — never the caller's job to
     * fix up rounding by hand.
     *
     * @param  array<int, int>  $weights
     * @return array<int, int>
     */
    protected function distribute(array $weights, int $target): array
    {
        $weights = array_values($weights);
        $count = count($weights);

        if ($count === 0) {
            return [];
        }

        $weightSum = array_sum($weights);

        if ($weightSum <= 0) {
            $base = intdiv($target, $count);
            $remainder = $target - ($base * $count);
            $result = array_fill(0, $count, $base);

            for ($i = 0; $i < $remainder; $i++) {
                $result[$i]++;
            }

            return $result;
        }

        $result = [];
        $remainders = [];
        $allocated = 0;

        foreach ($weights as $i => $w) {
            $share = $target * $w;
            $portion = intdiv($share, $weightSum);
            $remainders[$i] = $share - ($portion * $weightSum);
            $result[$i] = $portion;
            $allocated += $portion;
        }

        $leftover = $target - $allocated;
        arsort($remainders);

        foreach (array_keys($remainders) as $i) {
            if ($leftover <= 0) {
                break;
            }
            $result[$i]++;
            $leftover--;
        }

        return $result;
    }
}
