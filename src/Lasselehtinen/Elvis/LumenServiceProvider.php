<?php

namespace LasseLehtinen\Elvis;

use Illuminate\Support\ServiceProvider;

class LumenServiceProvider extends ServiceProvider
{
    /**
    * Bootstrap the application services.
    *
    * @return void
    */
    public function boot()
    {
      //
    }

    /**
    * Register the application services.
    *
    * @return void
    */
    public function register()
    {
        $this->app->singleton('elvis', function () {
            return new Elvis;
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['elvis'];
    }
}
