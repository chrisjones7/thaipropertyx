<?php

namespace App\Services;

use RuntimeException;

class ImageFingerprintService
{
    /**
     * Generate fingerprints from image file contents.
     *
     * @return array{image_sha256: string, image_phash: string}
     */
    public function fingerprint(string $imageData): array
    {
        if (!extension_loaded('gd')) {
            throw new RuntimeException(
                'The PHP GD extension is required for image fingerprinting.'
            );
        }

        $image = @imagecreatefromstring($imageData);

        if ($image === false) {
            throw new RuntimeException(
                'Unable to decode image for fingerprinting.'
            );
        }

        $sample = imagecreatetruecolor(9, 8);

        if ($sample === false) {
            throw new RuntimeException(
                'Unable to create image fingerprint sample.'
            );
        }

        imagecopyresampled(
            $sample,
            $image,
            0,
            0,
            0,
            0,
            9,
            8,
            imagesx($image),
            imagesy($image)
        );

        $hash = '';

        for ($y = 0; $y < 8; $y++) {
            for ($x = 0; $x < 8; $x++) {
                $left = $this->brightness(
                    imagecolorat($sample, $x, $y)
                );

                $right = $this->brightness(
                    imagecolorat($sample, $x + 1, $y)
                );

                $hash .= $left > $right ? '1' : '0';
            }
        }

        return [
            'image_sha256' => hash('sha256', $imageData),
            'image_phash' => $this->binaryToHex($hash),
        ];
    }

    /**
     * Calculate the Hamming distance between two 64-bit hashes.
     *
     * Zero means identical fingerprints.
     */
    public function distance(string $first, string $second): int
    {
        if (
            !preg_match('/^[0-9a-f]{16}$/i', $first) ||
            !preg_match('/^[0-9a-f]{16}$/i', $second)
        ) {
            throw new RuntimeException(
                'Both perceptual hashes must contain 16 hexadecimal characters.'
            );
        }

        $first = strtolower($first);
        $second = strtolower($second);

        $distance = 0;

        for ($i = 0; $i < 16; $i++) {
            $difference = hexdec($first[$i]) ^ hexdec($second[$i]);

            while ($difference !== 0) {
                $distance += $difference & 1;
                $difference >>= 1;
            }
        }

        return $distance;
    }

    /**
     * Calculate approximate pixel brightness.
     */
    private function brightness(int $color): float
    {
        $red = ($color >> 16) & 0xff;
        $green = ($color >> 8) & 0xff;
        $blue = $color & 0xff;

        return (
            0.299 * $red +
            0.587 * $green +
            0.114 * $blue
        );
    }

    /**
     * Convert a 64-bit binary string to 16 hexadecimal characters.
     */
    private function binaryToHex(string $binary): string
    {
        $hex = '';

        foreach (str_split($binary, 4) as $group) {
            $hex .= dechex(bindec($group));
        }

        return $hex;
    }
}
