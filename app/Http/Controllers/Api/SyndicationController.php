<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\Syndication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SyndicationController extends Controller
{
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
