<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Exceptions\Billing\TlvValueTooLongException;

/**
 * Base64-encoded TLV (Tag-Length-Value) byte string, per the ZATCA Phase 1
 * QR spec: five tags, seller name / VAT number / timestamp / total incl. VAT /
 * VAT amount, in that fixed order. Each block is [tag byte][length byte]
 * [UTF-8 value bytes].
 *
 * The length byte is the UTF-8 BYTE length of the value, never the character
 * count — an Arabic seller name is the difference between a working QR and a
 * broken one, since multibyte characters are longer in bytes than in
 * characters. strlen() (byte length) is used deliberately instead of
 * mb_strlen() (character count) throughout this class.
 */
class Tlv
{
    public const TAG_SELLER_NAME = 1;

    public const TAG_VAT_NUMBER = 2;

    public const TAG_TIMESTAMP = 3;

    public const TAG_INVOICE_TOTAL = 4;

    public const TAG_VAT_TOTAL = 5;

    protected const LABELS = [
        self::TAG_SELLER_NAME => 'seller name',
        self::TAG_VAT_NUMBER => 'seller VAT registration number',
        self::TAG_TIMESTAMP => 'invoice timestamp',
        self::TAG_INVOICE_TOTAL => 'invoice total including VAT',
        self::TAG_VAT_TOTAL => 'total VAT amount',
    ];

    public static function build(
        string $sellerName,
        string $vatNumber,
        string $timestampIso8601,
        string $totalIncludingVat,
        string $totalVat,
    ): string {
        return self::encode([
            self::TAG_SELLER_NAME => $sellerName,
            self::TAG_VAT_NUMBER => $vatNumber,
            self::TAG_TIMESTAMP => $timestampIso8601,
            self::TAG_INVOICE_TOTAL => $totalIncludingVat,
            self::TAG_VAT_TOTAL => $totalVat,
        ]);
    }

    /**
     * @param  array<int, string>  $fields  Keyed by tag (1-5); always emitted in
     *                                       tag order regardless of array order.
     *
     * @throws TlvValueTooLongException if any value's UTF-8 byte length exceeds 255.
     */
    public static function encode(array $fields): string
    {
        $bytes = '';

        foreach (array_keys(self::LABELS) as $tag) {
            $value = (string) ($fields[$tag] ?? '');
            $length = strlen($value);

            if ($length > 255) {
                throw new TlvValueTooLongException(sprintf(
                    'TLV tag %d (%s) is %d bytes long; a single length byte can only express up to 255.',
                    $tag,
                    self::LABELS[$tag],
                    $length,
                ));
            }

            $bytes .= chr($tag).chr($length).$value;
        }

        return base64_encode($bytes);
    }
}
