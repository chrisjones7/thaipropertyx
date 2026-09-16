<?php

namespace App\Http\Middleware;

use App\Models\ApiClient;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiClient
{
    /**
     * Authenticate a TPX API client using:
     *
     * X-TPX-Client-ID
     * X-TPX-Secret
     */
    public function handle(Request $request, Closure $next): Response
    {
        $clientId = $request->header('X-TPX-Client-ID');
        $secret = $request->header('X-TPX-Secret');

        if (!$clientId || !$secret) {
            return response()->json([
                'message' => 'TPX API credentials are required.',
            ], 401);
        }

        $client = ApiClient::query()
            ->with('agency')
            ->where('client_id', $clientId)
            ->first();

        if (!$client) {
            return response()->json([
                'message' => 'Invalid TPX API credentials.',
            ], 401);
        }

        if ($client->status !== 'active') {
            return response()->json([
                'message' => 'This TPX API client is not active.',
            ], 403);
        }

        if ($client->expires_at && $client->expires_at->isPast()) {
            return response()->json([
                'message' => 'This TPX API client has expired.',
            ], 403);
        }

        if (!$client->agency || $client->agency->status !== 'active') {
            return response()->json([
                'message' => 'The TPX agency account is not active.',
            ], 403);
        }

        if (!Hash::check($secret, $client->secret_hash)) {
            return response()->json([
                'message' => 'Invalid TPX API credentials.',
            ], 401);
        }

        /*
         * Make the authenticated API client and agency available
         * to controllers handling this request.
         */
        $request->attributes->set('tpx_api_client', $client);
        $request->attributes->set('tpx_agency', $client->agency);

        /*
         * Record successful API usage.
         */
        $client->forceFill([
            'last_used_at' => now(),
        ])->save();

        return $next($request);
    }
}