<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Property;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExchangePropertyController extends Controller
{
    /**
     * Return properties currently available on the TPX Exchange.
     *
     * The authenticated agency's own properties are excluded.
     */
    public function index(Request $request): JsonResponse
    {
        $agency = $request->attributes->get('tpx_agency');
        $apiClient = $request->attributes->get('tpx_api_client');

        if (!$agency || !$apiClient) {
            return response()->json([
                'message' => 'Authenticated TPX agency could not be resolved.',
            ], 401);
        }

        $validated = $request->validate([
            'property_type' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:255'],
            'development' => ['nullable', 'string', 'max:255'],

            'for_sale' => ['nullable', 'boolean'],
            'for_rent' => ['nullable', 'boolean'],

            'min_sale_price' => ['nullable', 'numeric', 'min:0'],
            'max_sale_price' => ['nullable', 'numeric', 'min:0'],

            'min_rental_price' => ['nullable', 'numeric', 'min:0'],
            'max_rental_price' => ['nullable', 'numeric', 'min:0'],

            'bedrooms' => ['nullable', 'integer', 'min:0'],
            'bathrooms' => ['nullable', 'integer', 'min:0'],

            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Property::query()
            ->with([
                'agency:id,name,slug',
                'images',
                'features',
                'labels',
            ])
            ->where('exchange_available', true)
            ->where('status', 'active')
            ->where('agency_id', '!=', $agency->id);

        if (!empty($validated['property_type'])) {
            $query->where('property_type', $validated['property_type']);
        }

        if (!empty($validated['province'])) {
            $query->where('province', $validated['province']);
        }

        if (!empty($validated['district'])) {
            $query->where('district', $validated['district']);
        }

        if (!empty($validated['development'])) {
            $query->where(
                'development',
                'like',
                '%' . $validated['development'] . '%'
            );
        }

        if (array_key_exists('for_sale', $validated)) {
            $query->where('for_sale', $validated['for_sale']);
        }

        if (array_key_exists('for_rent', $validated)) {
            $query->where('for_rent', $validated['for_rent']);
        }

        if (isset($validated['min_sale_price'])) {
            $query->where(
                'sale_price',
                '>=',
                $validated['min_sale_price']
            );
        }

        if (isset($validated['max_sale_price'])) {
            $query->where(
                'sale_price',
                '<=',
                $validated['max_sale_price']
            );
        }

        if (isset($validated['min_rental_price'])) {
            $query->where(
                'rental_price',
                '>=',
                $validated['min_rental_price']
            );
        }

        if (isset($validated['max_rental_price'])) {
            $query->where(
                'rental_price',
                '<=',
                $validated['max_rental_price']
            );
        }

        if (isset($validated['bedrooms'])) {
            $query->where('bedrooms', '>=', $validated['bedrooms']);
        }

        if (isset($validated['bathrooms'])) {
            $query->where('bathrooms', '>=', $validated['bathrooms']);
        }

        $properties = $query
            ->orderByDesc('exchange_available_at')
            ->paginate($validated['per_page'] ?? 20);

        return response()->json([
            'status' => 'ok',
            'data' => $properties,
        ]);
    }
}
