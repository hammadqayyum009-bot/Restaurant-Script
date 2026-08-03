<?php

declare(strict_types=1);

namespace App\Payments;

use InvalidArgumentException;

/**
 * The Payments module's own decimal-to-minor-units conversion. Reads
 * config('currencies') — the exponent table shared with
 * App\Services\Billing\Money, so an order's invoice and its payment
 * transaction can never round differently. Deliberately not a reuse of
 * Billing's Money class itself: the conversion algorithm is small, stable,
 * and fine to duplicate; only the currency-exponent facts need one owner.
 */
class Money
{
    public static function exponent(string $currency): int
    {
        $exponent = config('currencies')[$currency] ?? null;

        if ($exponent === null) {
            throw new InvalidArgumentException("Unknown currency code: {$currency}");
        }

        return $exponent;
    }

    /**
     * Converts a decimal amount (as a string, e.g. "19.99") to integer minor
     * units for the given currency, without ever passing through a float.
     */
    public static function toMinor(string $decimal, string $currency): int
    {
        $exponent = self::exponent($currency);
        $decimal = trim($decimal);

        if ($decimal === '') {
            throw new InvalidArgumentException('Decimal value cannot be empty.');
        }

        $negative = str_starts_with($decimal, '-');

        if ($negative) {
            $decimal = substr($decimal, 1);
        }

        if (! preg_match('/^\d+(\.\d+)?$/', $decimal)) {
            throw new InvalidArgumentException("Invalid decimal value: \"{$decimal}\".");
        }

        [$intPart, $fracPart] = array_pad(explode('.', $decimal, 2), 2, '');

        if (strlen($fracPart) > $exponent) {
            $excess = substr($fracPart, $exponent);

            if (rtrim($excess, '0') !== '') {
                throw new InvalidArgumentException(
                    "Value \"{$decimal}\" has more precision than {$currency} allows ({$exponent} decimal places)."
                );
            }

            $fracPart = substr($fracPart, 0, $exponent);
        }

        $fracPart = str_pad($fracPart, $exponent, '0');
        $intPart = ltrim($intPart, '0');
        $intPart = $intPart === '' ? '0' : $intPart;

        $minor = (int) ($intPart.$fracPart);

        return $negative ? -$minor : $minor;
    }

    public static function toDecimal(int $minor, string $currency): string
    {
        $exponent = self::exponent($currency);
        $negative = $minor < 0;
        $magnitude = (string) abs($minor);

        if ($exponent === 0) {
            $result = $magnitude;
        } else {
            $magnitude = str_pad($magnitude, $exponent + 1, '0', STR_PAD_LEFT);
            $result = substr($magnitude, 0, -$exponent).'.'.substr($magnitude, -$exponent);
        }

        return ($negative ? '-' : '').$result;
    }
}
