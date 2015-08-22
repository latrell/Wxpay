<?php
namespace Latrell\Wxpay;

use Exception;

class WxpayException extends Exception
{

	public function errorMessage()
	{
		return $this->getMessage();
	}
}
