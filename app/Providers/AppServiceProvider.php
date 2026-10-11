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
        // Sign-in is a modal on the landing page: there is no /login page to send a guest to. JSON requests
        // never reach this (the `auth` middleware answers them with 401 JSON).
        Authenticate::redirectUsing(fn (Request $request) => route('user.landing'));
    }
}
