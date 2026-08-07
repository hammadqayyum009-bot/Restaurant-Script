<?php

namespace Tests\Unit\Billing;

use App\Services\Billing\VatCalculator;
use PHPUnit\Framework\TestCase;

class VatCalculatorTest extends TestCase
{
    public function test_inclusive_mode_back_calculates_vat_from_the_gross(): void
    {
        // 11500 gross at 15% (1500bp) means 10000 net + 1500 VAT.
        $result = VatCalculator::inclusive(11500, 1500);

        $this->assertSame(10000, $result['net']);
        $this->assertSame(1500, $result['vat']);
        $this->assertSame(11500, $result['gross']);
    }

    public function test_exclusive_mode_adds_vat_to_the_net(): void
    {
        $result = VatCalculator::exclusive(10000, 1500);

        $this->assertSame(10000, $result['net']);
        $this->assertSame(1500, $result['vat']);
        $this->assertSame(11500, $result['gross']);
    }

    public function test_zero_rated_line_contributes_no_vat_but_keeps_its_net_amount(): void
    {
        $inclusive = VatCalculator::forLine(5000, 1500, VatCalculator::CATEGORY_ZERO, inclusive: true);
        $exclusive = VatCalculator::forLine(5000, 1500, VatCalculator::CATEGORY_ZERO, inclusive: false);

        foreach ([$inclusive, $exclusive] as $result) {
            $this->assertSame(0, $result['vat']);
            $this->assertSame(5000, $result['net']);
            $this->assertSame(5000, $result['gross']);
        }
    }

    public function test_exempt_line_is_excluded_from_the_vat_base(): void
    {
        $result = VatCalculator::forLine(7500, 1500, VatCalculator::CATEGORY_EXEMPT, inclusive: true);

        $this->assertSame(0, $result['vat']);
        $this->assertSame(7500, $result['net']);
    }

    public function test_per_line_rounding_never_drifts_the_header_total(): void
    {
        $grossLines = [333, 667, 1001, 9999];
        $totalVat = 0;
        $totalNet = 0;
        $totalGross = 0;

        foreach ($grossLines as $gross) {
            $line = VatCalculator::inclusive($gross, 1500);
            $totalVat += $line['vat'];
            $totalNet += $line['net'];
            $totalGross += $line['gross'];
        }

        $this->assertSame(array_sum($grossLines), $totalGross);
        $this->assertSame($totalNet + $totalVat, $totalGross);
    }

    public function test_a_zero_percent_vat_rate_produces_a_valid_document_with_zero_vat(): void
    {
        $inclusive = VatCalculator::inclusive(10000, 0);
        $exclusive = VatCalculator::exclusive(10000, 0);

        $this->assertSame(0, $inclusive['vat']);
        $this->assertSame(10000, $inclusive['net']);
        $this->assertSame(10000, $inclusive['gross']);

        $this->assertSame(0, $exclusive['vat']);
        $this->assertSame(10000, $exclusive['gross']);
    }
}
