<?php namespace Lasselehtinen\Elvis;

use Illuminate\Support\ServiceProvider;

class ElvisServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->package('lasselehtinen/elvis');

		$loader = \Illuminate\Foundation\AliasLoader::getInstance();
  		$loader->alias('Elvis', 'Lasselehtinen\Elvis\Facades\Elvis');
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app['elvis'] = $this->app->share(function()
  		{
    		return new Elvis;
  		});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return string[]
	 */
	public function provides()
	{
		return array('elvis');
	}

}
