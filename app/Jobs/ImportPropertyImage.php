<?php

namespace App\Jobs;

use App\Models\PropertyMedia;
use App\Services\PropertyImageStorage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ImportPropertyImage implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 180;

    public function __construct(
        public int $mediaId,
        public int $propertyId,
        public string $sourceUrl
    ) {
        $this->onQueue('default');
    }

    public function handle(PropertyImageStorage $storage): void
    {
        $media = PropertyMedia::find($this->mediaId);

        if (!$media || !$this->isCurrentSource($media)) {
            return;
        }

        if ($media->storage_disk || $media->storage_path) {
            return;
        }

        // Network activity happens outside the database transaction.
        $uploaded = $storage->importFromUrl(
            $this->sourceUrl,
            $this->propertyId
        );

        $saved = false;

        try {
            DB::transaction(function () use ($uploaded, &$saved): void {
                $media = PropertyMedia::query()
                    ->lockForUpdate()
                    ->find($this->mediaId);

                if (
                    !$media ||
                    !$this->isCurrentSource($media) ||
                    $media->storage_disk ||
                    $media->storage_path
                ) {
                    return;
                }

                $media->fill([
                    'url' => $uploaded['url'],
                    'thumbnail_url' => $uploaded['thumbnail_url'],
                    'storage_disk' => $uploaded['storage_disk'],
                    'storage_path' => $uploaded['storage_path'],
                    'file_size' => $uploaded['file_size'] ?? null,
                    'mime_type' => $uploaded['mime_type'] ?? null,
                    'width' => $uploaded['width'] ?? null,
                    'height' => $uploaded['height'] ?? null,
                ]);

                $media->save();
                $saved = true;
            });
        } catch (Throwable $exception) {
            $this->deleteUnusedUpload($storage, $uploaded);
            throw $exception;
        }

        if (!$saved) {
            $this->deleteUnusedUpload($storage, $uploaded);
            return;
        }

        FingerprintPropertyImage::dispatch($this->mediaId)
            ->afterCommit();
    }

    private function isCurrentSource(PropertyMedia $media): bool
    {
        return $media->media_type === 'image'
            && (int) $media->property_id === $this->propertyId
            && $media->source_url === $this->sourceUrl;
    }

    private function deleteUnusedUpload(
        PropertyImageStorage $storage,
        array $uploaded
    ): void {
        try {
            $temporaryMedia = new PropertyMedia();

            $temporaryMedia->forceFill([
                'property_id' => $this->propertyId,
                'storage_disk' => $uploaded['storage_disk'],
                'storage_path' => $uploaded['storage_path'],
                'url' => $uploaded['url'],
                'thumbnail_url' => $uploaded['thumbnail_url'],
            ]);

            $storage->delete($temporaryMedia);
        } catch (Throwable $exception) {
            Log::warning('Unable to clean up unused imported image', [
                'media_id' => $this->mediaId,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Property image import failed', [
            'media_id' => $this->mediaId,
            'property_id' => $this->propertyId,
            'source_url' => $this->sourceUrl,
            'message' => $exception?->getMessage(),
        ]);
    }
}
