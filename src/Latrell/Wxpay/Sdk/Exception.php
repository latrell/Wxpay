<?php

class WxpayException extends Exception
{

	public function errorMessage()
	{
		return $this->getMessage();
	}
}
