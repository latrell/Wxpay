<?php
namespace Latrell\Wxpay\Pay;

use Latrell\Wxpay\WxpayException;
use Latrell\Wxpay\Sdk\Api;
use Latrell\Wxpay\Models\BizPayUrl;
use Latrell\Wxpay\Models\CloseOrder;
use Latrell\Wxpay\Models\DownloadBill;
use Latrell\Wxpay\Models\JsApiPay;
use Latrell\Wxpay\Models\MicroPay;
use Latrell\Wxpay\Models\NotifyReply;
use Latrell\Wxpay\Models\OrderQuery;
use Latrell\Wxpay\Models\Refund;
use Latrell\Wxpay\Models\RefundQuery;
use Latrell\Wxpay\Models\Report;
use Latrell\Wxpay\Models\Results;
use Latrell\Wxpay\Models\Reverse;
use Latrell\Wxpay\Models\ShortUrl;
use Latrell\Wxpay\Models\UnifiedOrder;

/**
 *
 * 刷卡支付实现类
 * 该类实现了一个刷卡支付的流程，流程如下：
 * 1、提交刷卡支付
 * 2、根据返回结果决定是否需要查询订单，如果查询之后订单还未变则需要返回查询（一般反复查10次）
 * 3、如果反复查询10订单依然不变，则发起撤销订单
 * 4、撤销订单需要循环撤销，一直撤销成功为止（注意循环次数，建议10次）
 *
 * 该类是微信支付提供的样例程序，商户可根据自己的需求修改，或者使用lib中的api自行开发，为了防止
 * 查询时hold住后台php进程，商户查询和撤销逻辑可在前端调用
 *
 */
class Micro
{

	protected $config;

	protected $api;

	protected $input;

	public function __construct($config)
	{
		$this->config = $config;
		$this->api = new Api($config);

		$this->input = new MicroPay();
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
	 *
	 * 提交刷卡支付，并且确认结果，接口比较慢
	 * @param MicroPay $input
	 * @throws WxpayException
	 * @return 返回查询接口的结果
	 */
	public function pay()
	{
		//①、提交被扫支付
		$result = $this->api->micropay($this->input, 5);

		//如果返回成功
		if (! array_key_exists('return_code', $result) || ! array_key_exists('result_code', $result)) {
			// echo '接口调用失败,请确认是否输入是否有误！';
			throw new WxpayException('接口调用失败！');
		}

		//②、接口调用成功，明确返回调用失败
		if ($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'FAIL' && $result['err_code'] != 'USERPAYING' && $result['err_code'] != 'SYSTEMERROR') {
			throw new WxpayException($result['err_code_des']);
		}

		//签名验证
		$out_trade_no = $this->input->getOutTradeNo();

		//③、确认支付是否成功
		$start_time = time();
		while (time() - $start_time <= 30) {
			$succ_result = 0;
			$query_result = $this->query($out_trade_no, $succ_result);
			switch ($succ_result) {
				case 1:
					// 订单交易成功
					return $query_result;
				case 2:
					// 订单交易失败
					break 2;
				default:
					// 等待1s后继续
					sleep(1);
			}
		}

		//④、确认失败，则撤销订单
		if (! $this->cancel($out_trade_no)) {
			throw new WxpayException('撤销单失败！');
		}

		return false;
	}

	/**
	 *
	 * 查询订单情况
	 * @param string $out_trade_no  商户订单号
	 * @param int $succ_code         查询订单结果
	 * @return 0 状态不确定，1表示订单成功，2表示交易失败，3表示继续等待
	 */
	public function query($out_trade_no, &$succ_code)
	{
		$input = new OrderQuery();
		$input->setOutTradeNo($out_trade_no);
		$result = $this->api->orderQuery($input);

		$succ_code = 0;
		if ($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS') {
			switch ($result['trade_state']) {
				case 'SUCCESS': // 支付成功
					// 订单成功
					$succ_code = 1;
					break;
				case 'REFUND': // 转入退款
				case 'CLOSED': // 已关闭
				case 'REVOKED': // 已撤销（刷卡支付）
				case 'PAYERROR': // 支付失败（其他原因，如银行返回失败）
					// 支付失败
					$succ_code = 2;
					break;
				case 'USERPAYING': // 用户支付中
					case 'NOTPAY': // 未支付
					// 继续等待
					$succ_code = 3;
					return false;
			}
			return $result;
		}
		// 执行到这里，就是网络或系统错误
		return false;
	}

	/**
	 *
	 * 撤销订单，如果失败会重复调用10次
	 * @param string $out_trade_no
	 */
	public function cancel($out_trade_no)
	{
		$depth = 0;
		while ($depth ++ < 10) {
			$input = new Reverse();
			$input->setOutTradeNo($out_trade_no);
			$result = $this->api->reverse($input);

			//接口调用失败
			if ($result['return_code'] != 'SUCCESS') {
				continue;
			}

			//如果结果为success且不需要重新调用撤销，则表示撤销成功
			if ($result['result_code'] != 'SUCCESS' && $result['recall'] == 'N') {
				return true;
			} elseif ($result['recall'] == 'Y') {
				continue;
			}
			return false;
		}
		return false;
	}
}