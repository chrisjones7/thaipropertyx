<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyMedia extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'media_type',
        'storage_disk',
        'storage_path',
        'url',
        'thumbnail_url',
        'file_size',
        'mime_type',
        'width',
        'height',
        'title',
        'alt_text',
        'sort_order',
        'is_primary',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'sort_order' => 'integer',
            'is_primary' => 'boolean',
        ];
    }

    /**
     * Return an absolute URL for property media.
     */
    public function getUrlAttribute($value): ?string
    {
        if (!$value) {
            return null;
        }

        if (
            str_starts_with($value, 'http://') ||
            str_starts_with($value, 'https://')
        ) {
            return $value;
        }

        return url($value);
    }

    /**
     * Return an absolute URL for property thumbnails.
     */
    public function getThumbnailUrlAttribute($value): ?string
    {
        if (!$value) {
            return null;
        }

        if (
            str_starts_with($value, 'http://') ||
            str_starts_with($value, 'https://')
        ) {
            return $value;
        }

        return url($value);
    }

    /**
     * Build storage metadata for a TPX-managed file.
     *
     * This keeps media accounting independent of the public URL and allows
     * the storage disk to move from local public storage to R2 later.
     */
    public static function storageMetadata(
        string $disk,
        string $path,
        string $mediaType = 'image'
    ): array {
        $storage = \Illuminate\Support\Facades\Storage::disk($disk);

        if (!$storage->exists($path)) {
            return [
                'storage_disk' => $disk,
                'storage_path' => $path,
                'file_size' => null,
                'mime_type' => null,
                'width' => null,
                'height' => null,
            ];
        }

        $fileSize = $storage->size($path);
        $mimeType = $storage->mimeType($path);

        $width = null;
        $height = null;

        if ($mediaType === 'image') {
            try {
                $localPath = $storage->path($path);
                $dimensions = @getimagesize($localPath);

                if ($dimensions !== false) {
                    $width = $dimensions[0] ?? null;
                    $height = $dimensions[1] ?? null;
                }
            } catch (\Throwable $exception) {
                // Remote disks such as R2 may not expose a local filesystem path.
            }
        }

        return [
            'storage_disk' => $disk,
            'storage_path' => $path,
            'file_size' => $fileSize,
            'mime_type' => $mimeType ?: null,
            'width' => $width,
            'height' => $height,
        ];
    }

    public function property()
    {
        return $this->belongsTo(Property::class);
    }
}