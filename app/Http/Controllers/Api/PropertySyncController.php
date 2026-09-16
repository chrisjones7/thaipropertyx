<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\PropertyFeature;
use App\Models\PropertyLabel;
use App\Models\PropertyMedia;
use App\Models\PropertySource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PropertySyncController extends Controller
{
    /**
     * Create or update an OWNER property sent from a Houzez website.
     *
     * The authenticated API client determines the agency.
     * We NEVER accept agency_id from the remote website.
     */
    public function sync(Request $request): JsonResponse
    {
        $agency = $request->attributes->get('tpx_agency');
        $apiClient = $request->attributes->get('tpx_api_client');

        if (!$agency || !$apiClient) {
            return response()->json([
                'message' => 'Authenticated TPX agency could not be resolved.',
            ], 401);
        }

        $validated = $request->validate([
            /*
             * Houzez / WordPress identity.
             */
            'external_id' => ['required', 'string', 'max:255'],
            'external_url' => ['nullable', 'url', 'max:2048'],

            /*
             * Core property details.
             */
            'reference' => ['nullable', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'development' => ['nullable', 'string', 'max:255'],

            /*
             * Sale / rental status.
             */
            'for_sale' => ['required', 'boolean'],
            'for_rent' => ['required', 'boolean'],

            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'rental_price' => ['nullable', 'numeric', 'min:0'],
            'rental_period' => ['nullable', 'string', 'max:50'],

            'currency' => ['nullable', 'string', 'size:3'],

            /*
             * Property classification.
             */
            'property_type' => ['required', 'string', 'max:100'],

            /*
             * Specifications.
             */
            'bedrooms' => ['nullable', 'integer', 'min:0'],
            'bathrooms' => ['nullable', 'integer', 'min:0'],

            'land_size' => ['nullable', 'numeric', 'min:0'],
            'building_size' => ['nullable', 'numeric', 'min:0'],
            'size_unit' => ['nullable', 'string', 'max:20'],

            'furnishing' => ['nullable', 'string', 'max:100'],
            'year_built' => ['nullable', 'integer', 'min:1800', 'max:2200'],
            'parking_spaces' => ['nullable', 'integer', 'min:0'],

            /*
             * Ownership / fees.
             */
            'tenure' => ['nullable', 'string', 'max:100'],
            'lease_term_years' => ['nullable', 'integer', 'min:0'],
            'foreign_quota' => ['nullable', 'boolean'],

            'common_fee' => ['nullable', 'numeric', 'min:0'],
            'common_fee_period' => ['nullable', 'string', 'max:50'],

            /*
             * Location.
             */
            'address' => ['nullable', 'string', 'max:500'],
            'subdistrict' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255'],
            'postcode' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:100'],

            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],

            /*
             * TPX publication state.
             */
            'status' => [
                'nullable',
                'in:draft,pending,active,sold,rented,withdrawn,expired',
            ],

            'featured' => ['nullable', 'boolean'],

            /*
             * Make available to TPX Network.
             */
            'exchange_available' => ['required', 'boolean'],

            /*
             * Property media supplied by the owner's Houzez website.
             *
             * When the images field is supplied, TPX treats it as the
             * authoritative gallery for this property.
             */
            'features' => ['sometimes', 'array', 'max:100'],
            'features.*' => ['required', 'string', 'max:150'],

            'labels' => ['sometimes', 'array', 'max:50'],
            'labels.*' => ['required', 'string', 'max:150'],

            'images' => ['sometimes', 'array', 'max:100'],
            'images.*.url' => ['required', 'url', 'max:2048'],
            'images.*.thumbnail_url' => ['nullable', 'url', 'max:2048'],
            'images.*.title' => ['nullable', 'string', 'max:255'],
            'images.*.alt_text' => ['nullable', 'string', 'max:255'],
            'images.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'images.*.is_primary' => ['nullable', 'boolean'],
        ]);

        /*
         * A property must have at least one commercial listing type.
         */
        if (
            !$validated['for_sale'] &&
            !$validated['for_rent']
        ) {
            throw ValidationException::withMessages([
                'for_sale' => [
                    'The property must be for sale, for rent, or both.',
                ],
            ]);
        }

        /*
         * If it is for sale, require a sale price.
         */
        if (
            $validated['for_sale'] &&
            !array_key_exists('sale_price', $validated)
        ) {
            throw ValidationException::withMessages([
                'sale_price' => [
                    'A sale price is required when the property is for sale.',
                ],
            ]);
        }

        /*
         * If it is for rent, require a rental price.
         */
        if (
            $validated['for_rent'] &&
            !array_key_exists('rental_price', $validated)
        ) {
            throw ValidationException::withMessages([
                'rental_price' => [
                    'A rental price is required when the property is for rent.',
                ],
            ]);
        }

        $result = DB::transaction(function () use (
            $validated,
            $agency
        ) {
            /*
             * Find the existing TPX property through its Houzez source.
             */
            $source = PropertySource::query()
                ->where('agency_id', $agency->id)
                ->where('source_type', 'houzez')
                ->where('external_id', $validated['external_id'])
                ->first();

            $property = $source?->property;

            $isNewProperty = !$property;

            if (!$property) {
                $property = new Property();
                $property->agency_id = $agency->id;
                $property->slug = $this->makeUniqueSlug(
                    $validated['title']
                );
            }

            /*
             * Prevent one agency from overwriting another agency's property.
             */
            if (
                !$isNewProperty &&
                (int) $property->agency_id !== (int) $agency->id
            ) {
                abort(403, 'This API client does not own this property.');
            }

            $wasExchangeAvailable =
                (bool) ($property->exchange_available ?? false);

            $isExchangeAvailable =
                (bool) $validated['exchange_available'];

            /*
             * Keep legacy listing_type / price populated.
             */
            $legacyListingType =
                $validated['for_sale']
                    ? 'sale'
                    : 'rent';

            $legacyPrice =
                $validated['for_sale']
                    ? ($validated['sale_price'] ?? 0)
                    : ($validated['rental_price'] ?? 0);

            /*
             * Exchange timestamps.
             */
            $exchangeAvailableAt =
                $property->exchange_available_at;

            $exchangeWithdrawnAt =
                $property->exchange_withdrawn_at;

            if (
                $isExchangeAvailable &&
                !$wasExchangeAvailable
            ) {
                $exchangeAvailableAt = now();
                $exchangeWithdrawnAt = null;
            } elseif (
                !$isExchangeAvailable &&
                $wasExchangeAvailable
            ) {
                $exchangeWithdrawnAt = now();
            } elseif ($isExchangeAvailable) {
                $exchangeWithdrawnAt = null;
            }

            $property->fill([
                'reference' =>
                    $validated['reference'] ?? null,

                'title' =>
                    $validated['title'],

                'description' =>
                    $validated['description'] ?? null,

                'development' =>
                    $validated['development'] ?? null,

                /*
                 * Legacy compatibility.
                 */
                'listing_type' =>
                    $legacyListingType,

                'price' =>
                    $legacyPrice,

                /*
                 * Current TPX listing model.
                 */
                'for_sale' =>
                    $validated['for_sale'],

                'for_rent' =>
                    $validated['for_rent'],

                'sale_price' =>
                    $validated['for_sale']
                        ? ($validated['sale_price'] ?? null)
                        : null,

                'rental_price' =>
                    $validated['for_rent']
                        ? ($validated['rental_price'] ?? null)
                        : null,

                'rental_period' =>
                    $validated['rental_period'] ?? null,

                'currency' =>
                    strtoupper(
                        $validated['currency'] ?? 'THB'
                    ),

                'property_type' =>
                    $validated['property_type'],

                'bedrooms' =>
                    $validated['bedrooms'] ?? null,

                'bathrooms' =>
                    $validated['bathrooms'] ?? null,

                'land_size' =>
                    $validated['land_size'] ?? null,

                'building_size' =>
                    $validated['building_size'] ?? null,

                'size_unit' =>
                    $validated['size_unit'] ?? 'sqm',

                'furnishing' =>
                    $validated['furnishing'] ?? null,

                'year_built' =>
                    $validated['year_built'] ?? null,

                'parking_spaces' =>
                    $validated['parking_spaces'] ?? null,

                'tenure' =>
                    $validated['tenure'] ?? null,

                'lease_term_years' =>
                    $validated['lease_term_years'] ?? null,

                'foreign_quota' =>
                    $validated['foreign_quota'] ?? null,

                'common_fee' =>
                    $validated['common_fee'] ?? null,

                'common_fee_period' =>
                    $validated['common_fee_period'] ?? null,

                'address' =>
                    $validated['address'] ?? null,

                'subdistrict' =>
                    $validated['subdistrict'] ?? null,

                'district' =>
                    $validated['district'] ?? null,

                'province' =>
                    $validated['province'] ?? null,

                'postcode' =>
                    $validated['postcode'] ?? null,

                'country' =>
                    $validated['country'] ?? 'Thailand',

                'latitude' =>
                    $validated['latitude'] ?? null,

                'longitude' =>
                    $validated['longitude'] ?? null,

                'status' =>
                    $validated['status'] ?? 'active',

                'featured' =>
                    $validated['featured'] ?? false,

                'exchange_available' =>
                    $isExchangeAvailable,

                'exchange_available_at' =>
                    $exchangeAvailableAt,

                'exchange_withdrawn_at' =>
                    $exchangeWithdrawnAt,
            ]);

            /*
             * Preserve/set publication time for active properties.
             */
            if (
                $property->status === 'active' &&
                !$property->published_at
            ) {
                $property->published_at = now();
            }

            $property->save();

            /*
             * Create/update the permanent Houzez -> TPX source relationship.
             */
            PropertySource::updateOrCreate(
                [
                    'agency_id' => $agency->id,
                    'source_type' => 'houzez',
                    'external_id' => $validated['external_id'],
                ],
                [
                    'property_id' => $property->id,
                    'external_url' =>
                        $validated['external_url'] ?? null,
                    'feed_name' => 'TPX Houzez Connector',
                    'last_synced_at' => now(),
                ]
            );

            /*
             * Synchronize TPX property features and labels.
             */
            if (array_key_exists('features', $validated)) {
                $featureIds = PropertyFeature::query()
                    ->where('active', true)
                    ->whereIn('houzez_slug', array_unique($validated['features']))
                    ->pluck('id')
                    ->all();

                $property->features()->sync($featureIds);
            }

            if (array_key_exists('labels', $validated)) {
                $labelIds = PropertyLabel::query()
                    ->where('active', true)
                    ->whereIn('houzez_slug', array_unique($validated['labels']))
                    ->pluck('id')
                    ->all();

                $property->labels()->sync($labelIds);
            }

            /*
             * Synchronize the owner's Houzez image gallery.
             *
             * Important:
             * If "images" is omitted completely, the existing TPX gallery
             * is left unchanged.
             *
             * If "images" is supplied as an empty array, the existing
             * image gallery is removed.
             */
            if (array_key_exists('images', $validated)) {
                $property->media()
                    ->where('media_type', 'image')
                    ->delete();

                $primaryAssigned = false;

                foreach ($validated['images'] as $index => $image) {
                    $requestedPrimary =
                        (bool) ($image['is_primary'] ?? false);

                    $isPrimary = false;

                    if ($requestedPrimary && !$primaryAssigned) {
                        $isPrimary = true;
                        $primaryAssigned = true;
                    }

                    PropertyMedia::create([
                        'property_id' => $property->id,
                        'media_type' => 'image',
                        'url' => $image['url'],
                        'thumbnail_url' =>
                            $image['thumbnail_url'] ?? null,
                        'title' =>
                            $image['title'] ?? null,
                        'alt_text' =>
                            $image['alt_text'] ?? null,
                        'sort_order' =>
                            $image['sort_order'] ?? $index,
                        'is_primary' =>
                            $isPrimary,
                    ]);
                }

                /*
                 * If the connector supplied images but did not explicitly
                 * mark a primary image, make the first image primary.
                 */
                if (
                    count($validated['images']) > 0 &&
                    !$primaryAssigned
                ) {
                    $firstImage = $property->media()
                        ->where('media_type', 'image')
                        ->orderBy('sort_order')
                        ->orderBy('id')
                        ->first();

                    if ($firstImage) {
                        $firstImage->is_primary = true;
                        $firstImage->save();
                    }
                }
            }

            /*
             * If the owner removes the property from the TPX Network,
             * centrally revoke every non-revoked syndication.
             */
            if (
                $wasExchangeAvailable &&
                !$isExchangeAvailable
            ) {
                $property->syndications()
                    ->where('status', '!=', 'revoked')
                    ->update([
                        'status' => 'revoked',
                        'revoked_at' => now(),
                    ]);
            }

            return [
                'property' => $property->fresh([
                    'images',
                ]),
                'created' => $isNewProperty,
            ];
        });

        $property = $result['property'];

        return response()->json([
            'status' => 'ok',

            'action' =>
                $result['created']
                    ? 'created'
                    : 'updated',

            'property' => [
                'id' => $property->id,
                'reference' => $property->reference,
                'title' => $property->title,
                'slug' => $property->slug,

                'agency_id' => $property->agency_id,

                'status' => $property->status,

                'for_sale' => $property->for_sale,
                'for_rent' => $property->for_rent,

                'sale_price' => $property->sale_price,
                'rental_price' => $property->rental_price,

                'exchange_available' =>
                    $property->exchange_available,

                'exchange_available_at' =>
                    $property->exchange_available_at,

                'images' => $property->images,

                'last_synced_at' => now(),
            ],
        ], $result['created'] ? 201 : 200);
    }

    /**
     * Generate a unique TPX slug.
     */
    private function makeUniqueSlug(string $title): string
    {
        $baseSlug = Str::slug($title);

        if ($baseSlug === '') {
            $baseSlug = 'property';
        }

        $slug = $baseSlug;
        $counter = 2;

        while (
            Property::query()
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}




