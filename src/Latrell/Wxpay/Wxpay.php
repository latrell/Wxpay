<?php
namespace Latrell\Wxpay;

use Cache;
use Curl\Curl;
use Carbon\Carbon;
use Request;

class Wxpay
{

	protected $config;

	public function __construct($config)
	{
		$this->config = $config;
	}

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

	public function getAccessToken()
	{
		$key = 'Wxpay/access_token';
		if (Cache::has($key)) {
			return Cache::get($key);
		}

		$curl = new Curl();
		$ret = $curl->get('https://api.weixin.qq.com/cgi-bin/token', [
			'grant_type' => 'client_credential',
			'appid' => $this->config['appid'],
			'secret' => $this->config['appsecret']
		]);
		$json = json_decode($ret, true);
		if (key_exists('errcode', $json)) {
			throw new WxPayException($json['errmsg'], $json['errcode']);
		}
		$access_token = $json['access_token'];
		$expires_at = Carbon::now()->addSeconds($json['expires_in'] - 60);
		Cache::put($key, $access_token, $expires_at);

		return $access_token;
	}

	public function getJsapiTicket()
	{
		$key = 'Wxpay/jsapi_ticket';
		if (Cache::has($key)) {
			return Cache::get($key);
		}

		$curl = new Curl();
		$ret = $curl->get('https://api.weixin.qq.com/cgi-bin/ticket/getticket', [
			'type' => 'jsapi',
			'ticket' => $this->getAccessToken()
		]);
		$json = json_decode($ret, true);
		if ((int) $json['errcode'] > 0) {
			throw new WxPayException($json['errmsg'], $json['errcode']);
		}
		$ticket = $json['ticket'];
		$expires_at = Carbon::now()->addSeconds($json['expires_in'] - 60);
		Cache::put($key, $ticket, $expires_at);

		return $ticket;
	}

	public function getSignature()
	{
		static $cache = null;

		if (is_null($cache)) {
			$data = [
				'timestamp' => (string) time(),
				'noncestr' => str_random(16),
				'jsapi_ticket' => $this->getJsapiTicket(),
				'url' => Request::getUri()
			];
			ksort($data);
			$params = [];
			foreach ($data as $k => $v) {
				$params[] = $k . '=' . $v;
			}
			$params = join('&', $params);
			$signature = sha1($params);

			$cache = new stdClass();
			$cache->timestamp = $data['timestamp'];
			$cache->noncestr = $data['noncestr'];
			$cache->signature = $signature;
		}

		return $cache;
	}
}