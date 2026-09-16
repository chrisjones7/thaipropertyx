<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\Syndication;
use App\Models\TaxonomyMapping;
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
         * paused and revoked syndications are also returned. This
         * allows the remote Houzez site to reliably unpublish
         * listings when their TPX syndication state changes.
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
         *
         * Example:
         *
         * TPX canonical type: condo
         * Arnold Property Houzez type: condos
         *
         * The canonical property_type is deliberately preserved.
         * houzez_property_type is supplied separately so the
         * receiving connector knows which local Houzez taxonomy
         * term should be assigned.
         *
         * If the receiving agency has no reverse mapping, the
         * canonical TPX property type is used as the fallback.
         */
        $propertyTypeMappings = TaxonomyMapping::query()
            ->where('agency_id', $targetAgency->id)
            ->where('source_type', 'tpx')
            ->where('source_taxonomy', 'property_type')
            ->where('target_taxonomy', 'houzez_property_type')
            ->where('active', true)
            ->get()
            ->keyBy('source_slug');

        $syndications->getCollection()->transform(
            function (Syndication $syndication) use ($propertyTypeMappings) {
                if (!$syndication->property) {
                    return $syndication;
                }

                $canonicalPropertyType =
                    $syndication->property->property_type;

                $mapping = $propertyTypeMappings->get(
                    $canonicalPropertyType
                );

                $syndication->property->setAttribute(
                    'houzez_property_type',
                    $mapping?->target_value ?: $canonicalPropertyType
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
     * Create a syndication for a property from the TPX Exchange.
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
         * Prevent duplicate syndications.
         *
         * The database also has a unique constraint on
         * property_id + target_agency_id.
         */
        $existing = Syndication::where('property_id', $property->id)
            ->where('target_agency_id', $targetAgency->id)
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'This property has already been selected by your agency.',
                'data' => $existing,
            ], 409);
        }

        /*
         * For the pilot system, selecting an Exchange property
         * immediately creates an active syndication.
         *
         * We retain the approval fields so that a future workflow
         * can require owner approval if desired.
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

        return response()->json([
            'status' => 'ok',
            'message' => 'Property successfully syndicated.',
            'data' => $syndication,
        ], 201);
    }
}