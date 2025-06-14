<?php

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