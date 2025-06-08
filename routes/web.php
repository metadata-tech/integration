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
    Route::get('/dashboard', function () {
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

    /**
     ** Admin Management Pages (Livewire)
     */
    Route::prefix('admin')->name('admin.')->group(function () {
        // Users Management (Livewire)
        Route::view('/users', 'admin.users.index')->name('users.index');
        // Roles Management (Livewire)
        Route::view('/roles', 'admin.roles.index')->name('roles.index');
        // Permissions Management (Livewire)
        Route::view('/permissions', 'admin.permissions.index')->name('permissions.index');
    });

});


