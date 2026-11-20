<?php

namespace App\Services;

use App\Models\PropertyMedia;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\ImageManager;
use RuntimeException;

class PropertyImageStorage
{
    private const MAX_IMAGE_BYTES = 15 * 1024 * 1024;

    private function diskName(): string
    {
        return app()->environment('production') ? 's3' : 'public';
    }

    /**
     * Upload an image and an optimised thumbnail.
     *
     * Production uses Cloudflare R2 through the s3 disk.
     * Local development uses the public disk.
     */
    public function upload(UploadedFile $image, int $propertyId): array
    {
        if ($propertyId < 1) {
            throw new RuntimeException('Invalid property ID.');
        }

        $sourcePath = $image->getRealPath();

        if (!$sourcePath || !is_file($sourcePath)) {
            throw new RuntimeException('Unable to read uploaded image.');
        }

        $fileSize = filesize($sourcePath);

        if (
            $fileSize === false ||
            $fileSize < 1 ||
            $fileSize > self::MAX_IMAGE_BYTES
        ) {
            throw new RuntimeException(
                'Image must be between 1 byte and 15 MB.'
            );
        }

        $imageInfo = @getimagesize($sourcePath);

        if ($imageInfo === false) {
            throw new RuntimeException('Invalid image file.');
        }

        $mimeType = $imageInfo['mime'] ?? '';

        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];

        if (!isset($extensions[$mimeType])) {
            throw new RuntimeException(
                'Only JPEG, PNG and WebP images are supported.'
            );
        }

        $diskName = $this->diskName();
        $disk = Storage::disk($diskName);

        $directory = 'properties/' . $propertyId;
        $filename = (string) Str::uuid()
            . '.' . $extensions[$mimeType];

        $path = $directory . '/' . $filename;

        $thumbnailPath = $directory
            . '/thumbnails/'
            . pathinfo($filename, PATHINFO_FILENAME)
            . '.jpg';

        $originalUploaded = false;
        $thumbnailUploaded = false;

        try {
            $stream = fopen($sourcePath, 'rb');

            if ($stream === false) {
                throw new RuntimeException(
                    'Unable to open image for storage.'
                );
            }

            try {
                if (!$disk->put($path, $stream)) {
                    throw new RuntimeException(
                        'Original image upload failed.'
                    );
                }

                $originalUploaded = true;
            } finally {
                fclose($stream);
            }

            $manager = new ImageManager(new Driver());

            $thumbnail = $manager
                ->decodePath($sourcePath)
                ->scaleDown(width: 480, height: 360)
                ->encode(new JpegEncoder(quality: 82));

            if (!$disk->put($thumbnailPath, (string) $thumbnail)) {
                throw new RuntimeException(
                    'Thumbnail upload failed.'
                );
            }

            $thumbnailUploaded = true;

            return [
                'url' => $disk->url($path),
                'thumbnail_url' => $disk->url($thumbnailPath),

                ...PropertyMedia::storageMetadata(
                    $diskName,
                    $path,
                    'image'
                ),
            ];
        } catch (\Throwable $exception) {
            if ($thumbnailUploaded) {
                $disk->delete($thumbnailPath);
            }

            if ($originalUploaded) {
                $disk->delete($path);
            }

            throw $exception;
        }
    }

    /**
     * Download an external property image and store it in TPX.
     *
     * Restricts downloads to public HTTPS addresses and
     * prevents redirects to internal or private services.
     */
    public function importFromUrl(
        string $url,
        int $propertyId
    ): array {
        $host = parse_url($url, PHP_URL_HOST);
        $scheme = parse_url($url, PHP_URL_SCHEME);
        $port = parse_url($url, PHP_URL_PORT);

        if (
            !is_string($host) ||
            !is_string($scheme) ||
            strtolower($scheme) !== 'https' ||
            ($port !== null && $port !== 443) ||
            parse_url($url, PHP_URL_USER) !== null ||
            parse_url($url, PHP_URL_PASS) !== null
        ) {
            throw new RuntimeException(
                'Only public HTTPS image URLs are supported.'
            );
        }

        $host = strtolower(rtrim($host, '.'));

        if (
            $host === 'localhost' ||
            str_ends_with($host, '.localhost') ||
            str_ends_with($host, '.local') ||
            str_ends_with($host, '.internal')
        ) {
            throw new RuntimeException(
                'Private image hosts are not permitted.'
            );
        }

        $addresses = [];

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $addresses[] = $host;
        } else {
            $records = dns_get_record(
                $host,
                DNS_A | DNS_AAAA
            );

            if ($records === false) {
                throw new RuntimeException(
                    'Unable to resolve image host.'
                );
            }

            foreach ($records as $record) {
                if (isset($record['ip'])) {
                    $addresses[] = $record['ip'];
                }

                if (isset($record['ipv6'])) {
                    $addresses[] = $record['ipv6'];
                }
            }
        }

        if (empty($addresses)) {
            throw new RuntimeException(
                'Image host has no valid IP address.'
            );
        }

        foreach ($addresses as $address) {
            if (!filter_var(
                $address,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE |
                FILTER_FLAG_NO_RES_RANGE
            )) {
                throw new RuntimeException(
                    'Image host resolves to a restricted address.'
                );
            }
        }

        /*
         * Pin the connection to the validated address.
         * This avoids a second DNS lookup resolving to
         * a private address after validation.
         */
        $resolvedAddress = $addresses[0];

        $curlAddress = str_contains($resolvedAddress, ':')
            ? '[' . $resolvedAddress . ']'
            : $resolvedAddress;

        $temporaryPath = tempnam(
            sys_get_temp_dir(),
            'tpx_image_'
        );

        if ($temporaryPath === false) {
            throw new RuntimeException(
                'Unable to create temporary image file.'
            );
        }

        try {
            $stream = fopen($temporaryPath, 'w+b');

            if ($stream === false) {
                throw new RuntimeException(
                    'Unable to open temporary image file.'
                );
            }

            try {
                $bytesReceived = 0;

                $response = Http::timeout(45)
                    ->connectTimeout(10)
                    ->withoutRedirecting()
                    ->withOptions([
                        'curl' => [
                            CURLOPT_RESOLVE => [
                                $host . ':443:' . $curlAddress,
                            ],
                        ],
                        'protocols' => ['https'],
                        'sink' => $stream,
                        'progress' => function (
                            $downloadTotal,
                            $downloadedBytes
                        ) use (&$bytesReceived) {
                            $bytesReceived = $downloadedBytes;

                            if (
                                $downloadedBytes >
                                self::MAX_IMAGE_BYTES
                            ) {
                                throw new RuntimeException(
                                    'Remote image exceeds 15 MB.'
                                );
                            }

                            if (
                                $downloadTotal >
                                self::MAX_IMAGE_BYTES
                            ) {
                                throw new RuntimeException(
                                    'Remote image exceeds 15 MB.'
                                );
                            }
                        },
                    ])
                    ->accept('image/jpeg, image/png, image/webp')
                    ->get($url);

                if (!$response->successful()) {
                    throw new RuntimeException(
                        'Image download failed with HTTP status '
                        . $response->status()
                    );
                }
            } finally {
                fclose($stream);
            }

            $actualSize = filesize($temporaryPath);

            if (
                $actualSize === false ||
                $actualSize < 1 ||
                $actualSize > self::MAX_IMAGE_BYTES
            ) {
                throw new RuntimeException(
                    'Downloaded image has an invalid size.'
                );
            }

            $imageInfo = @getimagesize($temporaryPath);

            if ($imageInfo === false) {
                throw new RuntimeException(
                    'Downloaded file is not a valid image.'
                );
            }

            $mimeType = $imageInfo['mime'] ?? '';

            $extensions = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
            ];

            if (!isset($extensions[$mimeType])) {
                throw new RuntimeException(
                    'Unsupported remote image format.'
                );
            }

            $image = new UploadedFile(
                $temporaryPath,
                'imported-image.' . $extensions[$mimeType],
                $mimeType,
                null,
                true
            );

            return $this->upload($image, $propertyId);
        } finally {
            if (is_file($temporaryPath)) {
                @unlink($temporaryPath);
            }
        }
    }

    /**
     * Delete only TPX-managed files.
     * External WordPress images are never deleted.
     */
    public function delete(PropertyMedia $media): void
    {
        $diskName = $media->storage_disk;
        $path = $media->storage_path;

        if (
            !in_array($diskName, ['s3', 'public'], true) ||
            !$path ||
            !str_starts_with($path, 'properties/') ||
            str_contains($path, '..')
        ) {
            return;
        }

        $expectedPrefix = 'properties/'
            . $media->property_id . '/';

        if (!str_starts_with($path, $expectedPrefix)) {
            return;
        }

        $disk = Storage::disk($diskName);

        $thumbnailPath = $expectedPrefix
            . 'thumbnails/'
            . pathinfo($path, PATHINFO_FILENAME)
            . '.jpg';

        $disk->delete($path);

        if ($path !== $thumbnailPath) {
            $disk->delete($thumbnailPath);
        }
    }
}
