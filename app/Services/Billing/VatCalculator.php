<?php

declare(strict_types=1);

namespace App\Services\Billing;

/**
 * Per-line VAT arithmetic in integer minor units, basis points for the rate
 * (1500 = 15.00%). Zero-rated and exempt lines short-circuit to zero VAT;
 * "out of scope" lines are excluded from the VAT base entirely, same as exempt,
 * but kept as a distinct category for reporting.
 */
class VatCalculator
{
    public const CATEGORY_STANDARD = 'S';

    public const CATEGORY_ZERO = 'Z';

    public const CATEGORY_EXEMPT = 'E';

    public const CATEGORY_OUT_OF_SCOPE = 'O';

    /**
     * @return array{net: int, vat: int, gross: int}
     */
    public static function forLine(int $amountMinor, int $vatRateBp, string $category, bool $inclusive): array
    {
        if (in_array($category, [self::CATEGORY_ZERO, self::CATEGORY_EXEMPT, self::CATEGORY_OUT_OF_SCOPE], true)) {
            return ['net' => $amountMinor, 'vat' => 0, 'gross' => $amountMinor];
        }

        return $inclusive
            ? self::inclusive($amountMinor, $vatRateBp)
            : self::exclusive($amountMinor, $vatRateBp);
    }

    /**
     * $grossMinor already includes VAT; back-calculate the VAT component.
     *
     * @return array{net: int, vat: int, gross: int}
     */
    public static function inclusive(int $grossMinor, int $vatRateBp): array
    {
        if ($vatRateBp === 0) {
            return ['net' => $grossMinor, 'vat' => 0, 'gross' => $grossMinor];
        }

        $vat = (int) round(($grossMinor * $vatRateBp) / (10000 + $vatRateBp));
        $net = $grossMinor - $vat;

        return ['net' => $net, 'vat' => $vat, 'gross' => $grossMinor];
    }

    /**
     * $netMinor excludes VAT; add it.
     *
     * @return array{net: int, vat: int, gross: int}
     */
    public static function exclusive(int $netMinor, int $vatRateBp): array
    {
        $vat = (int) round(($netMinor * $vatRateBp) / 10000);
        $gross = $netMinor + $vat;

        return ['net' => $netMinor, 'vat' => $vat, 'gross' => $gross];
    }
}
