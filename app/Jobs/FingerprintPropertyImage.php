<?php

namespace App\Jobs;

use App\Models\PropertyMedia;
use App\Jobs\DetectPropertyDuplicates;
use App\Services\ImageFingerprintService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class FingerprintPropertyImage implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(
        public int $mediaId
    ) {
        $this->onQueue('default');
    }

    public function handle(ImageFingerprintService $fingerprints): void
    {
        $media = PropertyMedia::find($this->mediaId);

        if (!$media || $media->media_type !== 'image') {
            return;
        }

        // Avoid unnecessary processing.
        if ($media->image_sha256 && $media->image_phash) {
            return;
        }

        // External image URLs will be supported in a later step.
        if (!$media->storage_disk || !$media->storage_path) {
            Log::info('TPX image fingerprint skipped: no local storage.', [
                'media_id' => $this->mediaId,
            ]);

            return;
        }

        $disk = Storage::disk($media->storage_disk);

        if (!$disk->exists($media->storage_path)) {
            Log::warning('TPX image fingerprint skipped: file missing.', [
                'media_id' => $this->mediaId,
            ]);

            return;
        }

        $imageData = $disk->get($media->storage_path);

        if (!is_string($imageData) || $imageData === '') {
            throw new RuntimeException('Image file is empty or unreadable.');
        }

        $result = $fingerprints->fingerprint($imageData);

        // The gallery may have been replaced while the job was running.
        $updated = PropertyMedia::query()
            ->whereKey($this->mediaId)
            ->where('media_type', 'image')
            ->where('storage_disk', $media->storage_disk)
            ->where('storage_path', $media->storage_path)
            ->whereNull('image_sha256')
            ->whereNull('image_phash')
            ->update($result);

        if ($updated === 0) {
            Log::info('TPX image fingerprint discarded: media changed.', [
                'media_id' => $this->mediaId,
            ]);

            return;
        }

        Log::info('TPX image fingerprint generated.', [
            'media_id' => $this->mediaId,
            'property_id' => $media->property_id,
        ]);
DetectPropertyDuplicates::dispatch($media->property_id)->afterCommit();
    }
}
