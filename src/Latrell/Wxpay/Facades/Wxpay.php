<?php
namespace Latrell\Wxpay\Facades;

use Illuminate\Support\Facades\Facade;

class Wxpay extends Facade
{

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor()
	{
		return 'wxpay';
	}
}