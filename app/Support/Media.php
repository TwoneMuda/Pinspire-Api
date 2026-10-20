<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

final class Media
{
    /**
     * Build a public URL for a file stored on the public disk.
     *
     * Files are served through a Laravel route instead of the static
     * /storage symlink so browsers receive the same CORS headers as the API.
     */
    public static function url(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        return url('media/'.ltrim($path, '/'));
    }
}
