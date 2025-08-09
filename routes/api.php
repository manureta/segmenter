<?php

use Illuminate\Http\Request;
use App\Http\Controllers\Api\LocalidadApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Authentication routes (temporarily disabled - sanctum not configured)
// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

// Health check endpoint
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now(),
        'version' => config('app.version', '1.0.0')
    ]);
});

// API v1 routes
Route::prefix('v1')->group(function () {
    
    // Localidades API
    Route::prefix('localidades')->group(function () {
        Route::get('/', [LocalidadApiController::class, 'index']);
        Route::get('/stats', [LocalidadApiController::class, 'bulkStats']);
        Route::get('/{codigo}', [LocalidadApiController::class, 'show']);
        Route::get('/{codigo}/radios', [LocalidadApiController::class, 'radios']);
        Route::get('/{codigo}/svg', [LocalidadApiController::class, 'svg']);
        Route::delete('/{codigo}/cache', [LocalidadApiController::class, 'clearCache']);
        Route::post('/bulk-stats', [LocalidadApiController::class, 'stats']);
    });

    // Future API endpoints (commented out until controllers are created)
    // Route::apiResource('provincias', ProvinciaApiController::class)->except(['store', 'update', 'destroy']);
    // Route::apiResource('departamentos', DepartamentoApiController::class)->except(['store', 'update', 'destroy']);
    // Route::apiResource('radios', RadioApiController::class)->except(['store', 'update', 'destroy']);
    
    // Segmentation API endpoints (commented out until controllers are created)
    // Route::prefix('segmentation')->group(function () {
    //     Route::post('/{codigo}/start', 'SegmentationApiController@start');
    //     Route::get('/{codigo}/status', 'SegmentationApiController@status');
    //     Route::get('/{codigo}/result', 'SegmentationApiController@result');
    // });
    
    // Statistics API (commented out until controllers are created)
    // Route::prefix('stats')->group(function () {
    //     Route::get('/overview', 'StatsApiController@overview');
    //     Route::get('/processing', 'StatsApiController@processing');
    //     Route::get('/performance', 'StatsApiController@performance');
    // });
});
