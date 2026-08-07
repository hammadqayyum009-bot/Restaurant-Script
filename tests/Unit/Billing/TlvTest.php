<?php

namespace Tests\Unit\Billing;

use App\Exceptions\Billing\TlvValueTooLongException;
use App\Services\Billing\Tlv;
use PHPUnit\Framework\TestCase;

class TlvTest extends TestCase
{
    /**
     * Independently reproduces the [tag][length][value] byte string using raw
     * chr()/strlen(), so the test is not simply re-running Tlv's own logic.
     */
    protected function reference(string $sellerName, string $vat, string $timestamp, string $total, string $vatAmount): string
    {
        $block = fn (int $tag, string $value) => chr($tag).chr(strlen($value)).$value;

        return base64_encode(
            $block(1, $sellerName).$block(2, $vat).$block(3, $timestamp).$block(4, $total).$block(5, $vatAmount)
        );
    }

    public function test_encodes_exactly_five_tags_in_the_specified_order(): void
    {
        $base64 = Tlv::build('Al Waha Restaurant', '300000000000003', '2026-08-02T14:30:00', '115.00', '15.00');
        $bytes = base64_decode($base64);

        $offset = 0;
        $expectedTags = [1, 2, 3, 4, 5];
        $seenTags = [];

        while ($offset < strlen($bytes)) {
            $tag = ord($bytes[$offset]);
            $len = ord($bytes[$offset + 1]);
            $seenTags[] = $tag;
            $offset += 2 + $len;
        }

        $this->assertSame($expectedTags, $seenTags);
        $this->assertSame(strlen($bytes), $offset, 'Every byte belongs to exactly one block.');
    }

    public function test_length_byte_is_the_utf8_byte_length_not_the_character_count(): void
    {
        // 20 Arabic characters, but 39 UTF-8 bytes — the length byte MUST be 39.
        $sellerName = 'شركة مطعم الواحة العا';
        $this->assertNotSame(mb_strlen($sellerName), strlen($sellerName));

        $expected = $this->reference($sellerName, '300000000000003', '2026-08-02T14:30:00', '115.00', '15.00');
        $actual = Tlv::build($sellerName, '300000000000003', '2026-08-02T14:30:00', '115.00', '15.00');

        $this->assertSame($expected, $actual);

        $bytes = base64_decode($actual);
        $this->assertSame(strlen($sellerName), ord($bytes[1]), 'Tag 1\'s length byte must be the byte length of the value.');
    }

    public function test_a_real_arabic_company_name_produces_the_exact_expected_base64_string(): void
    {
        $sellerName = 'شركة مطعم الواحة الدولية للتجارة العامة والمقاولات والاستثمار العقاري المحدودة';
        $this->assertSame(147, strlen($sellerName));

        $expected = $this->reference($sellerName, '300000000000003', '2026-08-02T14:30:00', '115.00', '15.00');
        $actual = Tlv::build($sellerName, '300000000000003', '2026-08-02T14:30:00', '115.00', '15.00');

        $this->assertSame(
            'AZPYtNix2YPYqSDZhdi32LnZhSDYp9mE2YjYp9it2Kkg2KfZhNiv2YjZhNmK2Kkg2YTZhNiq2KzYp9ix2Kkg2KfZhNi52KfZhdipINmI2KfZhNmF2YLYp9mI2YTYp9iqINmI2KfZhNin2LPYqtir2YXYp9ixINin2YTYudmC2KfYsdmKINin2YTZhdit2K/ZiNiv2KkCDzMwMDAwMDAwMDAwMDAwMwMTMjAyNi0wOC0wMlQxNDozMDowMAQGMTE1LjAwBQUxNS4wMA==',
            $actual,
        );
        $this->assertSame($expected, $actual);
    }

    public function test_handles_a_value_whose_byte_length_exceeds_127_the_single_byte_boundary(): void
    {
        // 147 bytes — well past the 127 boundary where a signed-byte reading
        // of the length would misbehave. chr()/ord() in PHP are unsigned
        // (0-255), so this must round-trip cleanly.
        $sellerName = 'شركة مطعم الواحة الدولية للتجارة العامة والمقاولات والاستثمار العقاري المحدودة';
        $this->assertGreaterThan(127, strlen($sellerName));
        $this->assertLessThanOrEqual(255, strlen($sellerName));

        $actual = Tlv::build($sellerName, '300000000000003', '2026-08-02T14:30:00', '115.00', '15.00');
        $bytes = base64_decode($actual);

        $this->assertSame(strlen($sellerName), ord($bytes[1]));
    }

    public function test_throws_when_a_value_byte_length_exceeds_255(): void
    {
        $tooLong = str_repeat('شركة مطعم الواحة ', 10); // 310 bytes
        $this->assertGreaterThan(255, strlen($tooLong));

        $this->expectException(TlvValueTooLongException::class);
        Tlv::build($tooLong, '300000000000003', '2026-08-02T14:30:00', '115.00', '15.00');
    }

    public function test_an_empty_optional_value_still_emits_a_well_formed_block(): void
    {
        $actual = Tlv::build('Al Waha Restaurant', '', '2026-08-02T14:30:00', '115.00', '15.00');
        $bytes = base64_decode($actual);

        // Tag 1 block: [1][19]['Al Waha Restaurant'] -> next block starts at 2 + 19 = 21
        $vatTagOffset = 2 + strlen('Al Waha Restaurant');
        $this->assertSame(2, ord($bytes[$vatTagOffset]), 'Tag 2 immediately follows tag 1.');
        $this->assertSame(0, ord($bytes[$vatTagOffset + 1]), 'An empty value has length byte 0.');
    }
}
