<?php

use App\Http\Controllers\Api\PropertySyncController;
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

});