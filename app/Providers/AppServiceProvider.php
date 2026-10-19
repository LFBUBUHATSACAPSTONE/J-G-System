<?php

namespace App\Providers;

use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // User-side sign-in is a modal on the landing page, so a guest who lands on a guarded
        // user page is sent there. Admin pages send the guest to the admin sign-in card instead.
        // JSON requests never reach this: the `auth` middleware answers them with 401 JSON.
        Authenticate::redirectUsing(fn (Request $request) => str_starts_with($request->path(), 'admin')
            ? route('admin.login')
            : route('user.landing'));
    }
}
