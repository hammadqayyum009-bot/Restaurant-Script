<?php

declare(strict_types=1);

namespace Tests\Unit\Billing;

use App\Services\Billing\Money;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;
use TypeError;

class MoneyTest extends TestCase
{
    /** @return array<string, array{string, string, int}> */
    public static function twoDpProvider(): array
    {
        return [
            '0.1 at 2dp' => ['0.1', 'AED', 10],
            '0.7 at 2dp' => ['0.7', 'AED', 70],
            '19.99 at 2dp' => ['19.99', 'AED', 1999],
            '1234567.89 at 2dp' => ['1234567.89', 'AED', 123456789],
        ];
    }

    #[DataProvider('twoDpProvider')]
    public function test_converts_two_decimal_currency_to_exact_minor_units(string $decimal, string $currency, int $expected): void
    {
        $this->assertSame($expected, Money::toMinor($decimal, $currency));
    }

    public function test_converts_at_exponent_three_for_kwd(): void
    {
        $this->assertSame(1005, Money::toMinor('1.005', 'KWD'));
        $this->assertSame(100, Money::toMinor('0.1', 'KWD'));
    }

    public function test_converts_at_exponent_zero_for_jpy(): void
    {
        $this->assertSame(1234, Money::toMinor('1234', 'JPY'));
    }

    public function test_rejects_a_value_with_more_precision_than_the_exponent_allows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Money::toMinor('19.999', 'AED');
    }

    public function test_round_trips_minor_units_back_to_a_decimal_string_with_no_drift(): void
    {
        foreach ([10, 70, 1999, 123456789, 0, 1, 999999999] as $minor) {
            $this->assertSame($minor, Money::toMinor(Money::toDecimal($minor, 'AED'), 'AED'));
        }

        foreach ([1005, 100, 3, 0] as $minor) {
            $this->assertSame($minor, Money::toMinor(Money::toDecimal($minor, 'KWD'), 'KWD'));
        }
    }

    public function test_never_accepts_a_float_argument(): void
    {
        $this->expectException(TypeError::class);
        Money::toMinor(19.99, 'AED'); // @phpstan-ignore-line intentional type violation under test
    }

    public function test_throws_on_an_unknown_currency_code(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Money::toMinor('10.00', 'XXX');
    }
}
