<?php
namespace Latrell\Wxpay\Pay;

use Latrell\Wxpay\Sdk\Api;
use Latrell\Wxpay\Models\Refund as RefundModel;
use Latrell\Wxpay\WxpayException;

/**
 *
 * 退款实现类
 * 注意：
 * 1、交易时间超过一年的订单无法提交退款；
 * 2、微信支付退款支持单笔交易分多次退款，多次退款需要提交原支付订单的商户订单号和设置不同的退款单号。
 *       一笔退款失败后重新提交，要采用原来的退款单号。
 *       总退款金额不能超过用户实际支付金额。
 *
 */
class Refund
{

	protected $config;

	protected $api;

	protected $input;

	public function __construct($config)
	{
		$this->config = $config;
		$this->api = new Api($config);

		$this->input = new RefundModel();
	}

	public function __call($method, $arguments)
	{
		if (method_exists($this->input, $method)) {
			return call_user_func_array([
				$this->input,
				$method
			], $arguments);
		}
		return call_user_func_array([
			$this,
			$method
		], $arguments);
	}

	/**
	 * 执行退款
	 */
	public function refund()
	{
		$result = $this->api->refund($this->input);

		if (@$result['return_code'] != 'SUCCESS' || @$result['result_code'] != 'SUCCESS') {
			$message = '接口调用失败！';
			if (key_exists('err_code_des', $result)) {
				$message = $result['err_code_des'];
			}
			throw new WxpayException($message);
		}

		return $result;
	}
}