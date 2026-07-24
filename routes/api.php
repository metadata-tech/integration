<?php

use App\Http\Controllers\Api\KpmController;
use App\Http\Controllers\Api\KptController;
use App\Http\Controllers\Api\LpmController;
use App\Http\Controllers\Api\MyGDXController;
use App\Http\Controllers\Api\IscafController;
use App\Http\Controllers\Api\JpnController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    // JPN
    Route::get('/jpn/semak', [JpnController::class, 'semakMaklumat']);
    
    // ePenempatan / eOperasi
    Route::get('/kpm', [KpmController::class, 'penempatanOperasi']);

    // eSIS
    Route::get('/kpt/esis', [KptController::class, 'esis']);

    // SVK SPM
    Route::post('/lpm/svkspm', [LpmController::class, 'svkspm']);
    Route::post('/lpm/svkspmulangan', [LpmController::class, 'svkspmulangan']);
    
    // APKV SVM
    Route::post('/lpm/svm', [LpmController::class, 'svm']);

    //KPT SKPG
    Route::get('/kpt/skpg', [KptController::class, 'skpg']);

    // MyGDX MUET
    Route::post('/mygdx/muet', [MyGDXController::class, 'muet']);
    // MyGDX STPM
    Route::post('/mygdx/stpm', [MyGDXController::class, 'stpm']);

    // ISCAF
    Route::post('/iscaf/status', [IscafController::class, 'status']);
    Route::post('/iscaf/daftar', [IscafController::class, 'daftar']);
    Route::post('/iscaf/informasi', [IscafController::class, 'informasi']);
    Route::post('/iscaf/kemas-kini', [IscafController::class, 'kemasKini']);

});
