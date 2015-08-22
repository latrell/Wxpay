<?php
namespace Latrell\Wxpay;

use WxPayException;

class Wxpay
{

	public function instance($type)
	{
		switch ($type) {
			case 'jsapi':
				return app('wxpay.jsapi');
				break;
			case 'micro':
				return app('wxpay.micro');
				break;
			case 'native':
				return app('wxpay.native');
				break;
			default:
				throw new WxPayException('SDK只支持jsapi、micro和native三种！');
				break;
		}
	}
}