<?php

use App\Http\Controllers\Api\ExchangePropertyController;
use App\Http\Controllers\Api\PropertySyncController;
use App\Http\Controllers\Api\SyndicationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public TPX API
|--------------------------------------------------------------------------
*/

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'ThaiPropertyX API',
    ]);
});


/*
|--------------------------------------------------------------------------
| Authenticated TPX API
|--------------------------------------------------------------------------
|
| All routes in this group require a valid TPX API client.
|
*/

Route::middleware('tpx.api')->group(function () {

    /*
     * Authentication test.
     */
    Route::get('/auth-test', function () {
        return response()->json([
            'status' => 'authenticated',
            'message' => 'TPX API authentication successful.',
        ]);
    });

    /*
     * Houzez owner-property synchronization.
     *
     * Creates the TPX property the first time.
     * Subsequent requests with the same:
     *
     * agency + source_type + external_id
     *
     * update the existing TPX property.
     */
    Route::post(
        '/v1/properties/sync',
        [PropertySyncController::class, 'sync']
    )->name('api.v1.properties.sync');

    /*
     * TPX Exchange property catalogue.
     *
     * Returns active properties made available to the TPX Network
     * by other agencies.
     */
    Route::get(
        '/v1/exchange/properties',
        [ExchangePropertyController::class, 'index']
    )->name('api.v1.exchange.properties.index');

    /*
     * Syndicate an Exchange property to the authenticated agency.
     *
     * Creates the relationship between the property owner
     * and the agency selecting the property for syndication.
     */
    Route::post(
        '/v1/exchange/properties/{property}/syndicate',
        [SyndicationController::class, 'store']
    )->name('api.v1.exchange.properties.syndicate');

});