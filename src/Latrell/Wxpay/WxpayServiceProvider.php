<?php
namespace Latrell\Wxpay;

use Illuminate\Support\ServiceProvider;

class WxpayServiceProvider extends ServiceProvider
{

	/**
	 * boot process
	 */
	public function boot()
	{
		$this->publishes([
			__DIR__ . '/../../config/config.php' => config_path('latrell-wxpay.php'),
			__DIR__ . '/../../config/cert/apiclient_cert.pem' => config_path('wxpay/cert/apiclient_cert.pem'),
			__DIR__ . '/../../config/cert/apiclient_key.pem' => config_path('wxpay/cert/apiclient_key.pem')
		], 'config');
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->mergeConfigFrom(__DIR__ . '/../../config/config.php', 'latrell-wxpay');

		$this->app->singleton('wxpay', function ($app) {
			return new Wxpay($app->config->get('latrell-wxpay'));
		});

		$this->app->singleton('wxpay.jsapi', function ($app) {
			return new Pay\JsApi($app->config->get('latrell-wxpay'));
		});

		$this->app->singleton('wxpay.micro', function ($app) {
			return new Pay\Micro($app->config->get('latrell-wxpay'));
		});

		$this->app->singleton('wxpay.native', function ($app) {
			return new Pay\Native($app->config->get('latrell-wxpay'));
		});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return [
			'wxpay',
			'wxpay.jsapi',
			'wxpay.micro',
			'wxpay.native'
		];
	}
}
