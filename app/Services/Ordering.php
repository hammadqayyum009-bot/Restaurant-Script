<?php

namespace App\Services;

/**
 * The rules the admin sets under Settings → Ordering, in one place so the
 * checkout form, its validation and the totals cannot drift apart.
 */
class Ordering
{
    /** @return array<int, string> */
    public function orderTypes(): array
    {
        return array_values(array_filter([
            config('shop.enable_delivery') ? 'delivery' : null,
            config('shop.enable_pickup') ? 'pickup' : null,
        ]));
    }

    public function orderTypeLabel(string $type): string
    {
        return $type === 'pickup' ? 'Pickup' : 'Delivery';
    }

    public function minimumOrder(): float
    {
        return (float) config('shop.min_order');
    }

    public function meetsMinimum(float $subtotal): bool
    {
        return $subtotal >= $this->minimumOrder();
    }

    public function deliveryFee(string $orderType, float $subtotal): float
    {
        return $orderType === 'delivery' && $subtotal > 0
            ? (float) config('shop.delivery_fee')
            : 0.0;
    }

    public function taxPercent(): float
    {
        return (float) config('shop.tax_percent');
    }

    /** Tax applies to the food, not to the delivery charge. */
    public function tax(float $subtotal): float
    {
        return round($subtotal * $this->taxPercent() / 100, 2);
    }

    /** @return array{subtotal: float, delivery_fee: float, tax: float, total: float} */
    public function totals(float $subtotal, string $orderType): array
    {
        $deliveryFee = $this->deliveryFee($orderType, $subtotal);
        $tax = $this->tax($subtotal);

        return [
            'subtotal' => round($subtotal, 2),
            'delivery_fee' => $deliveryFee,
            'tax' => $tax,
            'total' => round($subtotal + $deliveryFee + $tax, 2),
        ];
    }

    /* ---------------- reservations ---------------- */

    public function reservationsEnabled(): bool
    {
        return (bool) config('shop.reservations_enabled');
    }

    public function reservationOpensAt(): string
    {
        return (string) (config('shop.reservation_open') ?: '00:00');
    }

    public function reservationClosesAt(): string
    {
        return (string) (config('shop.reservation_close') ?: '23:59');
    }

    public function maxGuests(): int
    {
        return max(1, (int) config('shop.reservation_max_guests'));
    }

    /**
     * Bookings that close after midnight (11:00 to 01:00) are treated as a
     * single evening rather than an impossible range.
     */
    public function reservationTimeAllowed(string $time): bool
    {
        $minutes = $this->minutes($time);
        $open = $this->minutes($this->reservationOpensAt());
        $close = $this->minutes($this->reservationClosesAt());

        if ($minutes === null || $open === null || $close === null) {
            return true;
        }

        return $close >= $open
            ? $minutes >= $open && $minutes <= $close
            : $minutes >= $open || $minutes <= $close;
    }

    public function reviewsEnabled(): bool
    {
        return (bool) config('shop.reviews_enabled');
    }

    protected function minutes(string $time): ?int
    {
        if (! preg_match('/^(\d{1,2}):(\d{2})/', trim($time), $m)) {
            return null;
        }

        return ((int) $m[1]) * 60 + (int) $m[2];
    }
}
