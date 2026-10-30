<?php

namespace App\Providers;

use App\Auth\AdminGuard;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        $this->registerAdminGuard();

        // The guard every request starts on. The `auth:` middleware switches the default guard
        // for the request it guards, and this keeps the value it started with (see
        // ResetDefaultGuard).
        $this->app->instance('auth.start_guard', config('auth.defaults.guard', 'web'));

        // User-side sign-in is a modal on the landing page, so a guest who lands on a guarded
        // user page is sent there. Admin pages send the guest to the admin sign-in card instead.
        // JSON requests never reach this: the `auth` middleware answers them with 401 JSON.
        Authenticate::redirectUsing(fn (Request $request) => str_starts_with($request->path(), 'admin')
            ? route('admin.login')
            : route('user.landing'));
    }

    /**
     * The `admin` guard: the panel's own session (see App\Auth\AdminGuard).
     */
    private function registerAdminGuard(): void
    {
        Auth::extend('admin', function (Application $app, string $name, array $config) {
            $guard = new AdminGuard(
                $name,
                $app->make('auth')->createUserProvider($config['provider'] ?? null),
                $app['session.store'],
                rehashOnLogin: $app['config']->get('hashing.rehash_on_login', true),
                timeboxDuration: $app['config']->get('auth.timebox_duration', 200000),
                hashKey: $app['config']->get('app.key'),
            );

            $guard->setCookieJar($app['cookie']);
            $guard->setDispatcher($app['events']);
            $guard->setRequest($app->refresh('request', $guard, 'setRequest'));

            return $guard;
        });
    }
}
