<?php
namespace Latrell\Wxpay\Pay;

/**
 *
 * 刷卡支付实现类
 *
 */
class Native
{

	/**
	 *
	 * 生成扫描支付URL,模式一
	 * @param BizPayUrlInput $bizUrlInfo
	 */
	public function GetPrePayUrl($productId)
	{
		$biz = new WxPayBizPayUrl();
		$biz->setProductId($productId);
		$values = WxpayApi::bizpayurl($biz);
		$url = 'weixin://wxpay/bizpayurl?' . $this->toUrlParams($values);
		return $url;
	}

	/**
	 *
	 * 参数数组转换为url参数
	 * @param array $urlObj
	 */
	private function toUrlParams($urlObj)
	{
		$buff = '';
		foreach ($urlObj as $k => $v) {
			$buff .= $k . '=' . $v . '&';
		}

		$buff = trim($buff, '&');
		return $buff;
	}

	/**
	 *
	 * 生成直接支付url，支付url有效期为2小时,模式二
	 * @param UnifiedOrderInput $input
	 */
	public function GetPayUrl($input)
	{
		if ($input->GetTrade_type() == 'NATIVE') {
			$result = WxPayApi::unifiedOrder($input);
			return $result;
		}
	}
}