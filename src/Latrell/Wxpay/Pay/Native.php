<?php
namespace Latrell\Wxpay\Pay;

use Latrell\Wxpay\WxpayException;
use Latrell\Wxpay\Sdk\Api;
use Latrell\Wxpay\Models\BizPayUrl;
use Latrell\Wxpay\Models\ShortUrl;

/**
 *
 * 刷卡支付实现类
 *
 */
class Native
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
	 * 生成扫描支付URL,模式一
	 * @param BizPayUrlInput $bizUrlInfo
	 */
	public function getPrePayUrl($productId)
	{
		$biz = new BizPayUrl();
		$biz->setProductId($productId);
		$values = $this->api->bizpayurl($biz);
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
			$result = $this->api->unifiedOrder($input);
			return $result;
		}
	}

	/**
	 * 转换短链接
	 * @param string $url
	 */
	public function shortUrl($url){
		$input = new ShortUrl();
		$input->setLongUrl($url);
		$values = $this->api->shorturl($input);
		if($values['return_code'] === 'FAIL'){
			throw new WxpayException($values['return_msg']);
		}
		return $values['short_url'];
	}
}