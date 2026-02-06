<?php

namespace Rifkiard\Gatekeeper;

use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;

class GatekeeperClientServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Socialite::extend('gatekeeper', function ($app) {
            $config = $app['config']['services.gatekeeper'];

            return new GatekeeperSocialiteProvider(
                $app->make('request'),
                $config['client_id'],
                $config['client_secret'],
                $config['redirect'],
                $config
            );
        });

        Auth::extend('gatekeeper-guard', function ($app, $name, array $config) {
            return new GatekeeperGuard(
                Auth::createUserProvider($config['provider']),
                $app->make('request')
            );
        });

        Auth::provider('gatekeeper-provider', function ($app, array $config) {
            return new GatekeeperUserProvider();
        });
    }

    public function register() {}
}
