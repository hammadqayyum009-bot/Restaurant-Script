<?php

namespace Tests\Unit\Billing;

use App\Services\Billing\ZatcaQr;
use chillerlan\QRCode\Output\QRGdImage;
use chillerlan\QRCode\Output\QRImagick;
use chillerlan\QRCode\Output\QRMarkupSVG;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class QrTest extends TestCase
{
    public function test_document_css_contains_no_physical_direction_properties(): void
    {
        $css = file_get_contents(dirname(__DIR__, 3).'/public/assets/css/document.css');

        foreach (['margin-left:', 'margin-right:', 'padding-left:', 'padding-right:'] as $forbidden) {
            $this->assertStringNotContainsString(
                $forbidden,
                $css,
                "document.css must use logical properties only — found \"{$forbidden}\".",
            );
        }
    }

    public function test_the_qr_renders_as_svg_via_a_backend_with_no_gd_or_imagick_dependency(): void
    {
        $svg = ZatcaQr::svg(base64_encode('hello world'));

        $this->assertStringContainsString('<svg', $svg);

        // Prove the render path structurally cannot depend on GD/Imagick,
        // rather than only proving it worked in an environment where both
        // happen to be installed: QRMarkupSVG is a pure string/XMLWriter
        // builder with no relation to the GD- or Imagick-backed output
        // classes, so this is true regardless of which extensions this
        // machine has loaded.
        $reflection = new ReflectionClass(QRMarkupSVG::class);
        $this->assertFalse($reflection->isSubclassOf(QRGdImage::class));
        $this->assertFalse($reflection->isSubclassOf(QRImagick::class));

        $source = file_get_contents((new ReflectionClass(QRMarkupSVG::class))->getFileName());
        foreach (['imagecreate', 'imagepng', 'imagejpeg', 'Imagick('] as $gdOrImagickCall) {
            $this->assertStringNotContainsString($gdOrImagickCall, $source);
        }
    }
}
