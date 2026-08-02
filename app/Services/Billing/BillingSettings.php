<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Services\Settings;

/**
 * Typed reader over the `settings` table for every `billing.*` key. Falls
 * back to config('billing.defaults') so the module works, unbranded, before a
 * single field has been saved — same pattern as App\Services\Ordering reading
 * config/shop.php.
 *
 * Every accessor below calls get()/bool() with NO explicit default, so
 * config('billing.defaults') is always the single source of truth for
 * fallback values — never duplicated as a literal here, which is what let
 * BillingSettings::prefix() silently disagree with config/billing.php until
 * a test caught it.
 *
 * Deliberately NOT wired into AppServiceProvider's $configMap: these keys
 * never override config(), so no existing config('shop.*') / config('site.*')
 * call is affected by anything saved here.
 */
class BillingSettings
{
    public function __construct(protected Settings $settings)
    {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        // config('billing.defaults') is a flat array whose keys are literal
        // dotted strings (e.g. "billing.vat_rate"), not nested arrays — so
        // this indexes it directly rather than via
        // config("billing.defaults.{$key}"), which would wrongly treat the
        // dots in $key as further nesting and never find anything.
        if ($default === null) {
            $default = config('billing.defaults', [])[$key] ?? null;
        }

        return $this->settings->get($key, $default);
    }

    public function bool(string $key, bool $default = false): bool
    {
        $stored = $this->settings->all()[$key] ?? null;

        if ($stored !== null && $stored !== '') {
            return in_array(strtolower((string) $stored), ['1', 'true', 'yes', 'on'], true);
        }

        $fallback = config('billing.defaults', [])[$key] ?? null;

        return $fallback === null ? $default : in_array(strtolower((string) $fallback), ['1', 'true', 'yes', 'on'], true);
    }

    public function set(string $key, mixed $value): void
    {
        $this->settings->set($key, $value, 'billing');
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function setMany(array $values): void
    {
        $this->settings->setMany($values, 'billing');
    }

    public function vatRate(): string
    {
        return (string) $this->get('billing.vat_rate');
    }

    /** Basis points — 1500 = 15.00%. */
    public function vatRateBp(): int
    {
        return (int) round(((float) $this->vatRate()) * 100);
    }

    public function pricesIncludeVat(): bool
    {
        return $this->bool('billing.prices_include_vat');
    }

    public function defaultCurrency(): string
    {
        return (string) $this->get('billing.default_currency');
    }

    public function vatNumber(): string
    {
        return (string) $this->get('billing.vat_number');
    }

    public function crNumber(): string
    {
        return (string) $this->get('billing.cr_number');
    }

    public function sellerName(string $lang): string
    {
        $value = (string) $this->get("billing.seller_name_{$lang}");

        return $value !== '' ? $value : (string) config('site.name');
    }

    public function phone(): string
    {
        $value = (string) $this->get('billing.phone');

        return $value !== '' ? $value : (string) config('site.phone');
    }

    public function email(): string
    {
        $value = (string) $this->get('billing.email');

        return $value !== '' ? $value : (string) config('site.email');
    }

    public function logo(): ?string
    {
        $value = (string) $this->get('billing.logo');

        return $value !== '' ? $value : null;
    }

    public function footer(string $lang): string
    {
        return (string) $this->get("billing.footer_{$lang}");
    }

    public function address(string $lang): string
    {
        $parts = array_filter([
            $this->get('billing.building_number'),
            $this->get("billing.street_{$lang}"),
            $this->get("billing.district_{$lang}"),
            $this->get("billing.city_{$lang}"),
            $this->get('billing.postal_code'),
        ], fn ($v) => $v !== null && $v !== '');

        return implode(', ', $parts);
    }

    public function showHijri(): bool
    {
        return $this->bool('billing.show_hijri');
    }

    public function numberPadding(): int
    {
        return max(1, (int) $this->get('billing.number_padding'));
    }

    public function numberFormat(): string
    {
        return (string) $this->get('billing.number_format');
    }

    public function prefix(string $documentType): string
    {
        $value = $this->get('billing.prefix_'.$documentType);

        return $value !== null && $value !== '' ? (string) $value : strtoupper(substr($documentType, 0, 2));
    }
}
