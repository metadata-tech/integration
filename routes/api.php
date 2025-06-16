<?php

use App\Http\Controllers\Api\KpmController;
use App\Http\Controllers\Api\KptController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::get('/esis/{ic}', function (Request $request, $ic) {
    return response()->json([
        'message' => 'This is a placeholder for the ESIS API endpoint.',
        'ic' => $ic,
    ]);

})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    // ePenempatan / eOperasi
    Route::get('/kpm', [KpmController::class, 'penempatanOperasi']);
    // eSIS
    Route::get('/kpt/esis', [KptController::class, 'esis']);
});
