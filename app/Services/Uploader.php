<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Throwable;

/**
 * Writes uploads straight into public/uploads.
 *
 * Shared cPanel accounts frequently cannot create the storage symlink, so the
 * public disk is avoided entirely and files land somewhere the web server can
 * already serve.
 */
class Uploader
{
    /**
     * Longest edge kept per folder. A phone photo is routinely 4000px wide and
     * several megabytes; serving that on every menu view is the single easiest
     * way to make the site feel slow.
     */
    protected const MAX_EDGE = [
        'branding' => 600,
        'menu' => 1600,
        'content' => 1920,
    ];

    protected const DEFAULT_MAX_EDGE = 1600;

    protected const JPEG_QUALITY = 82;

    public function store(UploadedFile $file, string $folder): string
    {
        $folder = trim($folder, '/');
        $directory = public_path('uploads/'.$folder);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'jpg');
        $name = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $name = ($name !== '' ? Str::limit($name, 40, '') : 'file').'-'.Str::random(8).'.'.$extension;

        $destination = $directory.'/'.$name;
        $file->move($directory, $name);

        $this->downscale($destination, self::MAX_EDGE[$folder] ?? self::DEFAULT_MAX_EDGE);

        return 'uploads/'.$folder.'/'.$name;
    }

    /**
     * Removes a previously stored upload. Remote URLs and paths outside the
     * uploads folder are ignored so seeded stock imagery is never deleted.
     */
    public function delete(?string $path): void
    {
        if (! $path || ! Str::startsWith($path, 'uploads/')) {
            return;
        }

        $full = public_path($path);

        if (is_file($full)) {
            @unlink($full);
        }
    }

    /**
     * Shrinks an image in place when it is larger than needed. Anything GD
     * cannot open — SVG, an exotic format, a corrupt file — is left untouched
     * rather than destroyed.
     */
    protected function downscale(string $path, int $maxEdge): void
    {
        if (! function_exists('imagecreatetruecolor')) {
            return;
        }

        try {
            $info = @getimagesize($path);

            if (! $info) {
                return;
            }

            [$width, $height] = $info;

            if ($width <= $maxEdge && $height <= $maxEdge) {
                return;
            }

            $source = match ($info[2]) {
                IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
                IMAGETYPE_PNG => @imagecreatefrompng($path),
                IMAGETYPE_WEBP => @imagecreatefromwebp($path),
                IMAGETYPE_GIF => @imagecreatefromgif($path),
                default => null,
            };

            if (! $source) {
                return;
            }

            $scale = $maxEdge / max($width, $height);
            $newWidth = max(1, (int) round($width * $scale));
            $newHeight = max(1, (int) round($height * $scale));

            $target = imagecreatetruecolor($newWidth, $newHeight);

            // Logos are usually transparent PNGs, so alpha has to survive the
            // resample rather than turning into a black background.
            if (in_array($info[2], [IMAGETYPE_PNG, IMAGETYPE_WEBP, IMAGETYPE_GIF], true)) {
                imagealphablending($target, false);
                imagesavealpha($target, true);
                imagefill($target, 0, 0, imagecolorallocatealpha($target, 0, 0, 0, 127));
            }

            imagecopyresampled($target, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($source);

            match ($info[2]) {
                IMAGETYPE_JPEG => imagejpeg($target, $path, self::JPEG_QUALITY),
                IMAGETYPE_PNG => imagepng($target, $path, 6),
                IMAGETYPE_WEBP => imagewebp($target, $path, self::JPEG_QUALITY),
                IMAGETYPE_GIF => imagegif($target, $path),
                default => null,
            };

            imagedestroy($target);
        } catch (Throwable) {
            // Keep the original: a resize failure must not lose the upload.
        }
    }
}
