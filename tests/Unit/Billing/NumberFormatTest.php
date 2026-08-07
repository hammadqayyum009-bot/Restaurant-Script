<?php

namespace Tests\Unit\Billing;

use App\Services\Billing\DocumentNumberer;
use Tests\TestCase;

class NumberFormatTest extends TestCase
{
    public function test_default_format_with_padding_six_renders_correctly(): void
    {
        $numberer = $this->app->make(DocumentNumberer::class);

        $this->assertSame('INV-2026-000123', $numberer->format('simplified_tax_invoice', 2026, 123));
        $this->assertSame('TI-2026-000045', $numberer->format('standard_tax_invoice', 2026, 45));
        $this->assertSame('CN-2026-000009', $numberer->format('credit_note', 2026, 9));
    }

    public function test_padding_is_configurable_and_respected(): void
    {
        // config('billing.defaults') is a flat array whose keys are literal
        // dotted strings (e.g. "billing.number_padding") — not nested arrays —
        // so it must be replaced as a whole array, not via a dot-path config()
        // call (which would build nested arrays instead of matching the key).
        config(['billing.defaults' => array_merge(config('billing.defaults'), [
            'billing.number_padding' => '4',
        ])]);

        $numberer = $this->app->make(DocumentNumberer::class);

        $this->assertSame('INV-2026-0123', $numberer->format('simplified_tax_invoice', 2026, 123));
    }

    public function test_a_number_wider_than_the_padding_is_not_truncated(): void
    {
        $numberer = $this->app->make(DocumentNumberer::class);

        // Default padding is 6, but the seventh digit must still print in full.
        $this->assertSame('INV-2026-1234567', $numberer->format('simplified_tax_invoice', 2026, 1234567));
    }
}
