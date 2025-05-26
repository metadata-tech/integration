<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function (\App\Service\LPMService $lpmService) {
        //dd($lpmService->callAPI());
        return view('dashboard');
    })->name('dashboard');

    Route::get('/log_api', [\App\Http\Controllers\LogAPIController::class, 'index'])->name("logapi.index");



    /**
     ** Users Management Page
     */
    Route::prefix('users_management')->name('user_management.')->group(function () {//middleware(['can:viewAny,\App\Models\User'])
        Route::get('/', [\App\Http\Controllers\UsersController::class, 'getUserManagement'])->name('index');
        /*Route::get('/non-staff', [UserController::class, 'getUserManagementNonStaff'])->name('non-staff');
        Route::get('/create', CreateUser::class)->name('create');
        Route::get('/profile/{user}/{role}', [UserController::class, 'getUserProfile'])->name('profile');*/
    });

});


