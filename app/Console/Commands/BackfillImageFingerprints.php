<?php

namespace App\Console\Commands;

use App\Models\PropertyMedia;
use App\Services\ImageFingerprintService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

class BackfillImageFingerprints extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tpx:backfill-image-fingerprints
                            {--dry-run : Test without updating the database}
                            {--limit= : Maximum number of image records to examine}';

    /**
     * The console command description.
     */
    protected $description = 'Generate fingerprints for existing TPX property images';

    /**
     * Execute the console command.
     */
    public function handle(ImageFingerprintService $fingerprints): int
    {
        if (!extension_loaded('gd')) {
            $this->error('PHP GD extension is not enabled.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $limitOption = $this->option('limit');

        if (
            $limitOption !== null &&
            (
                !ctype_digit((string) $limitOption) ||
                (int) $limitOption < 1
            )
        ) {
            $this->error('The --limit option must be a positive integer.');

            return self::FAILURE;
        }

        $limit = $limitOption !== null
            ? (int) $limitOption
            : null;

        $processed = 0;
        $fingerprinted = 0;
        $skipped = 0;
        $failed = 0;

        if ($dryRun) {
            $this->warn('DRY RUN: No database records will be updated.');
        } else {
            $this->info('Fingerprint backfill started.');
        }

        $query = PropertyMedia::query()
            ->where('media_type', 'image')
            ->where(function ($query) {
                $query->whereNull('image_sha256')
                    ->orWhereNull('image_phash');
            })
            ->orderBy('id');

        foreach ($query->cursor() as $media) {
            if ($limit !== null && $processed >= $limit) {
                break;
            }

            $processed++;

            try {
                if (!$media->storage_disk || !$media->storage_path) {
                    $skipped++;

                    $this->line(
                        "Skipped image {$media->id}: no storage location."
                    );

                    continue;
                }

                $disk = Storage::disk($media->storage_disk);

                if (!$disk->exists($media->storage_path)) {
                    $skipped++;

                    $this->line(
                        "Skipped image {$media->id}: file not found."
                    );

                    continue;
                }

                $imageData = $disk->get($media->storage_path);

                if (!is_string($imageData) || $imageData === '') {
                    throw new \RuntimeException(
                        'Image file is empty or unreadable.'
                    );
                }

                $result = $fingerprints->fingerprint($imageData);

                if (!$dryRun) {
                    $media->image_sha256 = $result['image_sha256'];
                    $media->image_phash = $result['image_phash'];
                    $media->save();
                }

                $fingerprinted++;

                $this->info(
                    "Image {$media->id}: fingerprint generated."
                );
            } catch (Throwable $exception) {
                $failed++;

                $this->error(
                    "Image {$media->id}: {$exception->getMessage()}"
                );
            }
        }

        $this->newLine();

        $this->table(
            ['Metric', 'Count'],
            [
                ['Records examined', $processed],
                ['Fingerprints generated', $fingerprinted],
                ['Missing/skipped images', $skipped],
                ['Errors', $failed],
            ]
        );

        if ($dryRun) {
            $this->warn('Dry run complete. No changes saved.');
        } else {
            $this->info('Fingerprint backfill complete.');
        }

        return $failed > 0
            ? self::FAILURE
            : self::SUCCESS;
    }
}
