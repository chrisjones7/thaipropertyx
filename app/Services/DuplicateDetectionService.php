<?php

namespace App\Services;

use App\Models\Property;
use App\Models\PropertyMedia;
use Illuminate\Support\Collection;

class DuplicateDetectionService
{
    /**
     * Maximum Hamming distance for visually similar images.
     * This threshold is provisional and requires further testing.
     */
    private const MAX_HASH_DISTANCE = 5;

    /**
     * Find potential duplicate listings for a property.
     *
     * This method only reads database records.
     * It does not block publication or create notifications.
     */
    public function findDuplicates(Property $property): Collection
    {
        $sourceImages = PropertyMedia::query()
            ->where('property_id', $property->id)
            ->where('media_type', 'image')
            ->whereNotNull('image_phash')
            ->get(['id', 'image_sha256', 'image_phash']);

        if ($sourceImages->isEmpty()) {
            return collect();
        }

$candidateImages = PropertyMedia::query()
    ->whereHas('property', function ($query) use ($property) {
        $query->where(function ($query) use ($property) {
            $query->where('created_at', '<', $property->created_at)
                ->orWhere(function ($query) use ($property) {
                    $query->where('created_at', $property->created_at)
                        ->where('id', '<', $property->id);
                });
        });
    })
    ->where('media_type', 'image')
    ->whereNotNull('image_phash')
            ->get([
                'id',
                'property_id',
                'image_sha256',
                'image_phash',
            ]);

        $matchesByProperty = [];

        foreach ($candidateImages as $candidate) {
            foreach ($sourceImages as $source) {
                if (!$this->imagesMatch($source, $candidate)) {
                    continue;
                }

                $candidatePropertyId = $candidate->property_id;

                $matchesByProperty[$candidatePropertyId][] = [
                    'source_media_id' => $source->id,
                    'candidate_media_id' => $candidate->id,
                ];
            }
        }

        $results = collect();

        foreach ($matchesByProperty as $propertyId => $matches) {
            $usedSource = [];
            $usedCandidate = [];
            $uniqueMatches = [];

            foreach ($matches as $match) {
                if (
                    isset($usedSource[$match['source_media_id']]) ||
                    isset($usedCandidate[$match['candidate_media_id']])
                ) {
                    continue;
                }

                $usedSource[$match['source_media_id']] = true;
                $usedCandidate[$match['candidate_media_id']] = true;

                $uniqueMatches[] = $match;
            }

            $matchCount = count($uniqueMatches);

            if ($matchCount === 0) {
                continue;
            }

            $classification = match (true) {
                $matchCount >= 3 => 'high_confidence',
                $matchCount === 2 => 'potential_duplicate',
                default => 'low_confidence',
            };

            $results->push([
                'property_id' => (int) $propertyId,
                'matching_images' => $matchCount,
                'classification' => $classification,
                'matches' => $uniqueMatches,
            ]);
        }

        return $results
            ->sortByDesc('matching_images')
            ->values();
    }

    /**
     * Compare two image fingerprints.
     */
    private function imagesMatch(
        PropertyMedia $first,
        PropertyMedia $second
    ): bool {
        if (
            $first->image_sha256 &&
            $second->image_sha256 &&
            hash_equals(
                $first->image_sha256,
                $second->image_sha256
            )
        ) {
            return true;
        }

        if (!$first->image_phash || !$second->image_phash) {
            return false;
        }

        return app(ImageFingerprintService::class)->distance(
            $first->image_phash,
            $second->image_phash
        ) <= self::MAX_HASH_DISTANCE;
    }
}
