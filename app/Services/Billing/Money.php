<?php

declare(strict_types=1);

namespace App\Services\Billing;

use InvalidArgumentException;

/**
 * The single, tested conversion point between the decimal amounts used
 * elsewhere in the app (orders.total etc, decimal(10,2)) and the integer
 * minor units this module stores everything in.
 *
 * Strict types mean a float argument is a TypeError, not silent truncation —
 * decimal amounts must always cross this boundary as strings.
 */
class Money
{
    public static function exponent(string $currency): int
    {
        $exponent = config('billing.currencies')[$currency] ?? null;

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

    /**
     * Converts integer minor units back to a decimal display string. Used for
     * display and for round-trip tests — never for storage.
     */
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

    /**
     * Converts a decimal quantity (e.g. "2.5") to thousandths (2500), so
     * fractional quantities (2.5 kg) never require a float or a decimal column.
     */
    public static function toMilli(string $decimal): int
    {
        $decimal = trim($decimal);

        if (! preg_match('/^\d+(\.\d+)?$/', $decimal)) {
            throw new InvalidArgumentException("Invalid quantity: \"{$decimal}\".");
        }

        [$intPart, $fracPart] = array_pad(explode('.', $decimal, 2), 2, '');

        if (strlen($fracPart) > 3) {
            $excess = substr($fracPart, 3);

            if (rtrim($excess, '0') !== '') {
                throw new InvalidArgumentException("Quantity \"{$decimal}\" has more than 3 decimal places.");
            }

            $fracPart = substr($fracPart, 0, 3);
        }

        $fracPart = str_pad($fracPart, 3, '0');
        $intPart = ltrim($intPart, '0');
        $intPart = $intPart === '' ? '0' : $intPart;

        return (int) ($intPart.$fracPart);
    }

    public static function milliToDecimal(int $milli): string
    {
        $magnitude = str_pad((string) abs($milli), 4, '0', STR_PAD_LEFT);
        $result = substr($magnitude, 0, -3).'.'.substr($magnitude, -3);

        return ($milli < 0 ? '-' : '').$result;
    }
}
