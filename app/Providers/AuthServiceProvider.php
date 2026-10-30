<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Foundation\App\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [];

    public function boot()
    {
        $this->registerPolicies();

        // Register the admin guard
        $this->app['auth']->extend('admin', function ($app) {
            $guard = new \App\Auth\AdminGuard($app['request'], $app['session.store']);
            $guard->setRequest($app['request']);
            return $guard;
        });

        parent::boot();
    }
}