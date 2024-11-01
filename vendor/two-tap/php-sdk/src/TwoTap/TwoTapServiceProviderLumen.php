<?php

namespace TwoTap;

use Illuminate\Support\ServiceProvider;

class TwoTapServiceProviderLumen extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $app = $this->app;

        // create api
        $app->singleton('twotap', function ($app) {
            return new Api([
                'public_token' => getenv('TWOTAP_PUBLIC_TOKEN'),
                'private_token' => getenv('TWOTAP_PRIVATE_TOKEN'),
            ]);
        });

        $app->alias('twotap', 'TwoTap/Api');
    }
}