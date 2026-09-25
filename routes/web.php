<?php
use App\Http\Controllers\Admin\UserTokenController;
use App\Http\Controllers\MQRReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect(auth()->check() ? route('dashboard') : route('login'));
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard', [
            'stats' => [
                'today' => \App\Models\APILog::whereDate('created_at', today())->count(),
                'errors_today' => \App\Models\APILog::whereDate('created_at', today())->where('status_code', '>=', 400)->count(),
                'total' => \App\Models\APILog::count(),
                'last' => \App\Models\APILog::latest('created_at')->value('created_at'),
            ],
        ]);
    })->name('dashboard');

    Route::get('/mqr/reports/{run}/{type}', MQRReportController::class)
        ->where('type', 'all|success|failure')
        ->name('mqr.reports.download');

    /**
     ** Integration Pages
     */
    Route::prefix('integrasi')->name('integrasi.')->group(function () {
        Route::view('/mqr/gagal', 'integrasi.mqr-failures')->name('mqr.gagal');

        foreach (config('integrations') as $slug => $integration) {
            Route::view("/{$slug}", 'integrasi.show', [
                'slug' => $slug,
                'integration' => $integration,
            ])->name($slug);
        }
    });

    Route::get('/log_api', [\App\Http\Controllers\LogAPIController::class, 'index'])->name("logapi.index");

    Route::get('hassantest', \App\Livewire\IscafForm::class)->name("hassantest");
    Route::get('iscaf_search', \App\Livewire\IscafSearch::class)->name("iscaf_search");

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

         // User token management
        Route::get('/{user}/tokens', [UserTokenController::class, 'index'])->name('user-tokens.index');
        Route::post('/{user}/tokens', [UserTokenController::class, 'store'])->name('user-tokens.store');
        Route::put('/{user}/tokens/{token}', [UserTokenController::class, 'update'])->name('user-tokens.update');
        Route::delete('/{user}/tokens/{token}', [UserTokenController::class, 'destroy'])->name('user-tokens.destroy');
    });

});
