<?php

declare(strict_types=1);

namespace App\Services\Billing;

use chillerlan\QRCode\Output\QRMarkupSVG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

/**
 * Renders the base64 TLV payload (App\Services\Billing\Tlv) as an inline SVG
 * QR code — no GD/Imagick dependency, nothing fetched from a third party.
 *
 * outputBase64 must be false: chillerlan/php-qrcode defaults to returning a
 * data: URI, but the print view inlines raw <svg> markup directly, since a
 * data: URI would need to sit inside an <img>, and this way the QR inherits
 * no extra network/caching layer at all — it's just markup in the page.
 */
class ZatcaQr
{
    public static function svg(string $base64Tlv): string
    {
        $options = new QROptions([
            'outputInterface' => QRMarkupSVG::class,
            'outputBase64' => false,
            'addQuietzone' => true,
        ]);

        return (new QRCode($options))->render($base64Tlv);
    }
}
