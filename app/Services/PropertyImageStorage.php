<?php

namespace App\Services;

use App\Models\PropertyMedia;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Intervention\Image\Encoders\JpegEncoder;
use RuntimeException;

class PropertyImageStorage
{
    private const DISK = 's3';

    /**
     * Upload an original image and an optimised thumbnail to R2.
     * Return the fields needed to create a PropertyMedia record.
     */
    public function upload(UploadedFile $image, int $propertyId): array
    {
        $disk = Storage::disk(self::DISK);

        $directory = 'properties/' . $propertyId;
        $extension = strtolower(
            $image->extension() ?: 'jpg'
        );

        $filename = (string) Str::uuid() . '.' . $extension;
        $path = $directory . '/' . $filename;

        $thumbnailPath = $directory
            . '/thumbnails/'
            . pathinfo($filename, PATHINFO_FILENAME)
            . '.jpg';

        $thumbnailUploaded = false;

        try {
            $stream = fopen($image->getRealPath(), 'rb');

            if ($stream === false) {
                throw new RuntimeException('Unable to read uploaded image.');
            }

            try {
                if (!$disk->put($path, $stream)) {
                    throw new RuntimeException('R2 original image upload failed.');
                }
            } finally {
                fclose($stream);
            }

            $thumbnailUrl = null;

            try {
                $manager = new ImageManager(new Driver());

                $thumbnail = $manager
                    ->decodePath($image->getRealPath())
                    ->scaleDown(width: 480, height: 360)
                    ->encode(new JpegEncoder(quality: 82));

                if (!$disk->put($thumbnailPath, (string) $thumbnail)) {
                    throw new RuntimeException('R2 thumbnail upload failed.');
                }

                $thumbnailUploaded = true;
                $thumbnailUrl = $disk->url($thumbnailPath);
            } catch (\Throwable $exception) {
    report($exception);

    throw new RuntimeException(
        'Property thumbnail generation or R2 upload failed.',
        0,
        $exception
    );
}

            return [
                'url' => $disk->url($path),
                'thumbnail_url' => $thumbnailUrl,
                ...PropertyMedia::storageMetadata(
                    self::DISK,
                    $path,
                    'image'
                ),
            ];
        } catch (\Throwable $exception) {
            $disk->delete($path);

            if ($thumbnailUploaded) {
                $disk->delete($thumbnailPath);
            }

            throw $exception;
        }
    }

    /**
     * Delete only TPX-managed files, using the recorded storage disk.
     * External image URLs are never deleted.
     */
    public function delete(PropertyMedia $media): void
    {
        $diskName = $media->storage_disk;
        $path = $media->storage_path;

        if (
            !in_array($diskName, ['s3', 'public'], true)
            || !$path
            || !str_starts_with($path, 'properties/')
            || str_contains($path, '..')
        ) {
            return;
        }

        $disk = Storage::disk($diskName);

        $disk->delete($path);

        $thumbnailPath = 'properties/'
            . $media->property_id
            . '/thumbnails/'
            . pathinfo($path, PATHINFO_FILENAME)
            . '.jpg';

        if (
            $path !== $thumbnailPath
            && str_starts_with(
                $path,
                'properties/' . $media->property_id . '/'
            )
        ) {
            $disk->delete($thumbnailPath);
        }
    }
}
