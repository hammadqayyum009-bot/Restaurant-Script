<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * Writes uploads straight into public/uploads.
 *
 * Shared cPanel accounts frequently cannot create the storage symlink, so the
 * public disk is avoided entirely and files land somewhere the web server can
 * already serve.
 */
class Uploader
{
    public function store(UploadedFile $file, string $folder): string
    {
        $directory = public_path('uploads/'.trim($folder, '/'));

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $name = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $name = ($name !== '' ? Str::limit($name, 40, '') : 'file').'-'.Str::random(8).'.'.$file->getClientOriginalExtension();

        $file->move($directory, $name);

        return 'uploads/'.trim($folder, '/').'/'.$name;
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
}
