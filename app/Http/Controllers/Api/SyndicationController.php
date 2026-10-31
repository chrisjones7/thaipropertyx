<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\Syndication;
use App\Models\TaxonomyMapping;
use App\Models\User;
use App\Notifications\PropertyImportedNotification;
use App\Notifications\SyndicationCreatedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SyndicationController extends Controller
{
    /**
     * Return the authenticated agency's syndicated properties.
     *
     * This endpoint is used by the receiving Houzez connector
     * to discover which TPX properties should exist on its website.
     */
    public function index(Request $request): JsonResponse
    {
        $targetAgency = $request->attributes->get('tpx_agency');
        $apiClient = $request->attributes->get('tpx_api_client');

        if (!$targetAgency || !$apiClient) {
            return response()->json([
                'message' => 'Authenticated TPX agency could not be resolved.',
            ], 401);
        }

        $validated = $request->validate([
            'status' => [
                'nullable',
                'in:pending,approved,active,paused,revoked,all',
            ],
            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
        ]);

        $status = $validated['status'] ?? 'active';

        $query = Syndication::query()
            ->where('target_agency_id', $targetAgency->id)
            ->with([
                'sourceAgency:id,name,slug',
                'property' => function ($query) {
                    $query->with([
                        'agency:id,name,slug',
                        'images',
                        'features',
                        'labels',
                    ]);
                },
            ]);

        /*
         * By default only active syndications are returned.
         *
         * The receiving connector can request status=all so that
         * paused and revoked syndications are also returned.
         */
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $syndications = $query
            ->orderByDesc('updated_at')
            ->paginate($validated['per_page'] ?? 20)
            ->withQueryString();

        /*
         * Translate canonical TPX property types into the local
         * Houzez terminology used by the receiving agency.
         */
        $propertyTypeMappings = TaxonomyMapping::query()
            ->where('agency_id', $targetAgency->id)
            ->where('source_type', 'tpx')
            ->where('source_taxonomy', 'property_type')
            ->where('target_taxonomy', 'houzez_property_type')
            ->where('active', true)
            ->get()
            ->keyBy('source_slug');

        /*
         * Translate canonical TPX property features into the local
         * Houzez terminology used by the receiving agency.
         */
        $propertyFeatureMappings = TaxonomyMapping::query()
            ->where('agency_id', $targetAgency->id)
            ->where('source_type', 'tpx')
            ->where('source_taxonomy', 'property_feature')
            ->where('target_taxonomy', 'houzez_property_feature')
            ->where('active', true)
            ->get()
            ->keyBy('source_slug');

        $syndications->getCollection()->transform(
            function (Syndication $syndication) use (
                $propertyTypeMappings,
                $propertyFeatureMappings
            ) {
                if (!$syndication->property) {
                    return $syndication;
                }

                /*
                 * Property type mapping.
                 */
                $canonicalPropertyType =
                    $syndication->property->property_type;

                $propertyTypeMapping = $propertyTypeMappings->get(
                    $canonicalPropertyType
                );

                $syndication->property->setAttribute(
                    'houzez_property_type',
                    $propertyTypeMapping?->target_value
                        ?: $canonicalPropertyType
                );

                /*
                 * Property feature mappings.
                 *
                 * Preserve the original canonical features and
                 * provide a separate array of Houzez feature slugs.
                 */
                $houzezPropertyFeatures = $syndication->property
                    ->features
                    ->map(function ($feature) use ($propertyFeatureMappings) {
                        $mapping = $propertyFeatureMappings->get(
                            $feature->slug
                        );

                        return $mapping?->target_value ?: $feature->slug;
                    })
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                $syndication->property->setAttribute(
                    'houzez_property_features',
                    $houzezPropertyFeatures
                );

                return $syndication;
            }
        );

        return response()->json([
            'status' => 'ok',
            'data' => $syndications,
        ]);
    }

    /**
     * Create or reactivate a syndication for a property
     * from the TPX Exchange.
     */
    public function store(Request $request, Property $property): JsonResponse
    {
        $targetAgency = $request->attributes->get('tpx_agency');
        $apiClient = $request->attributes->get('tpx_api_client');

        if (!$targetAgency || !$apiClient) {
            return response()->json([
                'message' => 'Authenticated TPX agency could not be resolved.',
            ], 401);
        }

        /*
         * A property can only be syndicated when it is active
         * and currently available on the TPX Exchange.
         */
        if (
            !$property->exchange_available ||
            $property->status !== 'active'
        ) {
            return response()->json([
                'message' => 'This property is not currently available on the TPX Exchange.',
            ], 422);
        }

        /*
         * An agency cannot syndicate its own property.
         */
        if ($property->agency_id === $targetAgency->id) {
            return response()->json([
                'message' => 'An agency cannot syndicate its own property.',
            ], 422);
        }

        /*
         * Check for an existing syndication.
         *
         * The database has a unique constraint on
         * property_id + target_agency_id.
         */
        $existing = Syndication::where('property_id', $property->id)
            ->where('target_agency_id', $targetAgency->id)
            ->first();

        if ($existing) {

            /*
             * Reactivate a previously revoked syndication.
             *
             * Preserve the original syndication ID so the
             * receiving Houzez connector can identify the
             * existing property.
             */
            if ($existing->status === 'revoked') {

                $existing->status = 'active';
                $existing->revoked_at = null;
                $existing->approved_at = now();
                $existing->activated_at = now();
                $existing->save();

                $existing->load([
                    'property',
                    'sourceAgency:id,name,slug',
                    'targetAgency:id,name,slug',
                ]);

                return response()->json([
                    'status' => 'ok',
                    'message' => 'Previously revoked syndication successfully reactivated.',
                    'data' => $existing,
                ], 200);
            }

            /*
             * Prevent duplicate syndications when an existing
             * record is active, pending, approved or paused.
             */
            return response()->json([
                'message' => 'This property has already been selected by your agency.',
                'data' => $existing,
            ], 409);
        }

        /*
         * Selecting an Exchange property immediately creates
         * an active syndication for the pilot system.
         */
        $syndication = Syndication::create([
            'property_id' => $property->id,
            'source_agency_id' => $property->agency_id,
            'target_agency_id' => $targetAgency->id,
            'status' => 'active',
            'approved_at' => now(),
            'activated_at' => now(),
        ]);

        $syndication->load([
            'property',
            'sourceAgency:id,name,slug',
            'targetAgency:id,name,slug',
        ]);

        /*
         * Notify the property-owning agency's active
         * administrators when another agency selects
         * one of its properties.
         *
         * Email preferences are handled by the
         * PropertyImportedNotification class.
         *
         * This runs only for newly created syndications,
         * not duplicate requests or reactivations.
         */
        $administrators = User::query()
            ->where('agency_id', $syndication->source_agency_id)
            ->where('role', 'agency_admin')
            ->where('status', 'active')
            ->get();

        foreach ($administrators as $administrator) {
            try {
                $administrator->notify(
                    new PropertyImportedNotification($syndication)
                );
            } catch (\Throwable $exception) {
                \Illuminate\Support\Facades\Log::error(
                    'TPX property imported notification failed',
                    [
                        'syndication_id' => $syndication->id,
                        'administrator_id' => $administrator->id,
                        'error' => $exception->getMessage(),
                    ]
                );
            }
        }

        /*
         * Notify the receiving agency's active administrators
         * when a new syndication is created.
         */
        $receivingAdministrators = User::query()
            ->where('agency_id', $syndication->target_agency_id)
            ->where('role', 'agency_admin')
            ->where('status', 'active')
            ->get();

        foreach ($receivingAdministrators as $administrator) {
            try {
                $administrator->notify(
                    new SyndicationCreatedNotification($syndication)
                );
            } catch (\Throwable $exception) {
                \Illuminate\Support\Facades\Log::error(
                    'TPX new syndication notification failed',
                    [
                        'syndication_id' => $syndication->id,
                        'administrator_id' => $administrator->id,
                        'error' => $exception->getMessage(),
                    ]
                );
            }
        }

        return response()->json([
            'status' => 'ok',
            'message' => 'Property successfully syndicated.',
            'data' => $syndication,
        ], 201);
    }
}
