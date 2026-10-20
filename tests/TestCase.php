<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\UploadedFile;

abstract class TestCase extends BaseTestCase
{
    /**
     * Create a fake PNG upload without relying on the GD extension.
     */
    protected function fakePng(string $name = 'image.png', int $width = 800, int $height = 600): UploadedFile
    {
        $raw = '';

        for ($y = 0; $y < $height; $y++) {
            $raw .= "\x00".str_repeat("\x80\x80\x80", $width);
        }

        $png = "\x89PNG\r\n\x1a\n"
            .$this->pngChunk('IHDR', pack('NNCCCCC', $width, $height, 8, 2, 0, 0, 0))
            .$this->pngChunk('IDAT', gzcompress($raw, 6))
            .$this->pngChunk('IEND', '');

        return UploadedFile::fake()->createWithContent($name, $png);
    }

    /**
     * Build a single PNG chunk.
     */
    private function pngChunk(string $type, string $data): string
    {
        return pack('N', strlen($data)).$type.$data.pack('N', crc32($type.$data));
    }
}
