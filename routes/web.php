<?php

use App\Features\Admin\Livewire\AdminUserStockList;
use App\Features\Admin\Livewire\UserList;
use App\Features\Auth\Livewire\Login;
use App\Features\Auth\Livewire\Register;
use App\Features\Profile\Livewire\Profile;
use App\Features\Stock\Livewire\StockDetail;
use App\Features\Stock\Livewire\StockList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::middleware(['throttle:1000-per-second'])->group(function () {
    // Root redirection based on role.
    Route::get('/', function () {
        if (Auth::check()) {
            return Auth::user()->isAdmin()
                ? redirect()->route('admin.users')
                : redirect()->route('stocks.index');
        }

        return redirect()->route('login');
    });

    // Guest authentication routes.
    Route::middleware('guest')->group(function () {
        Route::get('/login', Login::class)->name('login');
        Route::get('/register', Register::class)->name('register');
    });

    // Authenticated routes.
    Route::middleware('auth')->group(function () {
        Route::post('/logout', function (Request $request) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login');
        })->name('logout');

        Route::get('/stocks', StockList::class)
            ->middleware('owns.stock')
            ->name('stocks.index');
        Route::get('/stocks/{stock}', StockDetail::class)
            ->middleware('owns.stock')
            ->name('stocks.show');

        Route::get('/profile', Profile::class)->name('profile');

        // Read-only admin area.
        Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
            Route::get('/users', UserList::class)->name('users');
            Route::get('/users/{user}/stocks', AdminUserStockList::class)
                ->middleware('owns.stock')
                ->name('users.stocks');
        });
    });
});
