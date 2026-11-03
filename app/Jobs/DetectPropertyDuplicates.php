<?php

namespace App\Jobs;

use App\Models\Property;
use App\Models\PropertyDuplicateMatch;
use App\Services\DuplicateDetectionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class DetectPropertyDuplicates implements ShouldQueue
{
    use Queueable;

    /**
     * Maximum number of job attempts.
     */
    public int $tries = 3;

    /**
     * Maximum execution time in seconds.
     */
    public int $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $propertyId
    ) {
        $this->onQueue('default');
    }

    /**
     * Execute the queued job.
     */
    public function handle(
        DuplicateDetectionService $detector
    ): void {
        $property = Property::find($this->propertyId);

        if (!$property) {
            Log::warning(
                'TPX duplicate detection skipped: property not found.',
                [
                    'property_id' => $this->propertyId,
                ]
            );

            return;
        }

        $duplicates = $detector->findDuplicates($property);

        /*
         * Persist current duplicate matches for this property.
         * Preserve review status for existing matches.
         */
        foreach ($duplicates as $duplicate) {
            PropertyDuplicateMatch::updateOrCreate(
                [
                    'property_id' => $property->id,
                    'matching_property_id' => $duplicate['property_id'],
                ],
                [
                    'matching_images' => $duplicate['matching_images'],
                    'classification' => $duplicate['classification'],
                    'last_checked_at' => now(),
                ]
            );
        }


        /*
         * Remove outdated duplicate matches for this property.
         */
        PropertyDuplicateMatch::query()
            ->where('property_id', $property->id)
            ->whereNotIn(
                'matching_property_id',
                $duplicates->pluck('property_id')->all()
            )
            ->delete();

        if ($duplicates->isEmpty()) {
            Log::info(
                'TPX duplicate detection completed: no matches.',
                [
                    'property_id' => $property->id,
                ]
            );

            return;
        }

        foreach ($duplicates as $duplicate) {
            Log::warning(
                'TPX potential duplicate property detected.',
                [
                    'property_id' => $property->id,
                    'matching_property_id' => $duplicate['property_id'],
                    'matching_images' => $duplicate['matching_images'],
                    'classification' => $duplicate['classification'],
                    'matches' => $duplicate['matches'],
                ]
            );
        }
    }
}
