<?php

use App\Http\Controllers\Api\KpmController;
use App\Http\Controllers\Api\KptController;
use App\Http\Controllers\Api\LpmController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    // ePenempatan / eOperasi
    Route::get('/kpm', [KpmController::class, 'penempatanOperasi']);
    // eSIS
    Route::get('/kpt/esis', [KptController::class, 'esis']);
    // SVK SPM
    Route::post('/lpm/svkspm', [LpmController::class, 'svkspm']);
});
