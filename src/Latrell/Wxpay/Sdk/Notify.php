<?php
namespace Latrell\Wxpay\Sdk;

use Latrell\Wxpay\Models\NotifyReply;

/**
 *
 * 回调基础类
 *
 */
class Notify extends NotifyReply
{

	protected $config;

	protected $api;

	public function __construct($config)
	{
		$this->config = $config;
		$this->api = new Api($config);
	}

	/**
	 *
	 * 回调入口
	 * @param bool $needSign  是否需要签名输出
	 */
	final public function handle($callback, $needSign = true)
	{
		$msg = 'OK';
		//当返回false的时候，表示notify中调用NotifyCallBack回调失败获取签名校验失败，此时直接回复失败
		$result = $this->api->notify($callback, $msg);
		if ($result == false) {
			$this->setReturnCode('FAIL');
			$this->setReturnMsg($msg);
			return $this->replyNotify(false);
		} else {
			//该分支在成功回调到NotifyCallBack方法，处理完成之后流程
			$this->setReturnCode('SUCCESS');
			$this->setReturnMsg('OK');
		}
		return $this->replyNotify($needSign);
	}

	/**
	 *
	 * 回复通知
	 * @param bool $needSign 是否需要签名输出
	 */
	final private function replyNotify($need_sign = true)
	{
		//如果需要签名
		if ($need_sign == true && $this->getReturnCode() == 'SUCCESS') {
			$this->setSign();
		}
		return $this->toXml();
	}
}