<?php

declare(strict_types=1);

namespace App\Services\Billing;

use DateTimeInterface;
use IntlDateFormatter;

/**
 * The Hijri date toggle (Decision 3, approved): if ext-intl is missing, the
 * toggle disables itself and the date simply never prints — never a crash,
 * never a wrong date. available() is the single seam a test can override to
 * exercise that path without needing to actually uninstall the extension.
 */
class HijriDate
{
    /**
     * Overridden only in tests, to exercise the "ext-intl is missing" path
     * deterministically without needing to actually uninstall the extension
     * from the test environment. Left null in every real request.
     */
    protected static ?bool $availableOverride = null;

    public static function available(): bool
    {
        return self::$availableOverride ?? extension_loaded('intl');
    }

    /** @internal test-only */
    public static function forceAvailability(?bool $available): void
    {
        self::$availableOverride = $available;
    }

    public static function for(DateTimeInterface $date): ?string
    {
        if (! self::available()) {
            return null;
        }

        $formatter = new IntlDateFormatter(
            'ar_SA@calendar=islamic-umalqura',
            IntlDateFormatter::FULL,
            IntlDateFormatter::NONE,
            null,
            IntlDateFormatter::TRADITIONAL,
        );

        $result = $formatter->format($date);

        return $result !== false ? $result : null;
    }
}
