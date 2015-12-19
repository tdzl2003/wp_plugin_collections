<?php

class WP_WECHAT_APP_LOGIN_Api{
	public function __construct(){
		add_action('rest_api_init', array($this, 'initApi'));
	}
	public function initApi(){
		register_rest_route( 'app/v1', 'exchange/wechat', array(
				'methods' => 'POST',
				'callback' => array($this, 'exchangeToken')
			));
		register_rest_route( 'app/v1', '/login/wechat', array(
				'methods'=>'POST',
				'callback'=> array($this, 'processLogin')
			));
		register_rest_route( 'app/v1', '/register/wechat', array(
				'methods'=>'POST',
				'callback'=> array($this, 'processRegister')
			));
	}
	public function exchangeToken(WP_REST_Request $request) {
		$params = $request->get_params();
		$option = get_option('wechat-app-login');
		$code = $params['code'];

		$appid = $option['appid'];
		$secret = $option['appkey'];
		$result = json_decode(wp_remote_get("https://api.weixin.qq.com/sns/oauth2/access_token?appid=$appid&secret=$secret&code=$code&grant_type=authorization_code",
			[
				'sslcertificates' => dirname(__FILE__).'/ca-bundle.crt'
			])['body'], true);

		if (isset($result['errcode']) && $result['errcode'] != 0){
			error_log(print_r($result, true));
			return new WP_Error( 'auth_failed', $result['errmsg'], array( 'status' => 403 ) );
		}
		return array(
				'ok' => 1,
				'openId' => $result['openid'],
				'accessToken' => $result['access_token'],
			);

	}
	public function processLogin(WP_REST_Request $request){
		$params = $request->get_params();

		$option = get_option('wechat-app-login');
		$appid = $option['appid'];

		$openid = $params['openId'];
		$token = $params['accessToken'];

		$result = json_decode(wp_remote_get("https://api.weixin.qq.com/sns/auth?access_token=$token&openid=$openid", [
				'sslcertificates' => dirname(__FILE__).'/ca-bundle.crt'
			])['body'], true);

		if (isset($result['errcode']) && $result['errcode'] != 0){
			error_log(print_r($result, true));
			return new WP_Error( 'auth_failed', $result['errmsg'], array( 'status' => 403 ) );
		}

		$uid = wp_app_sso_login("wechat", $openid, $token);

		if (is_wp_error($uid))
		{
			return $uid;
		}

		return array(
			'ok'=>1,
			'uid'=>$uid,
		);
	}
	public function processRegister(WP_REST_Request $request){
		$params = $request->get_params();

		$option = get_option('qq-app-login');
		$appid = $option['appid'];

		$openid = $params['openId'];
		$token = $params['accessToken'];

		$result = json_decode(wp_remote_get("https://api.weixin.qq.com/sns/userinfo?access_token=$token&openid=$openid", [
				'sslcertificates' => dirname(__FILE__).'/ca-bundle.crt'
			])['body'], true);

		if (isset($result['errcode']) && $result['errcode'] != 0){
			error_log(print_r($result, true));
			return new WP_Error( 'auth_failed', $result['errmsg'], array( 'status' => 403 ) );
		}

		$params['image'] = $result['headimgurl'];
		$params['nickname'] = $result['nickname'];

		$uid = wp_app_sso_register("wechat", $openid, $token, $params);
		if (is_wp_error($uid))
		{
			return $uid;
		}

		return array(
			'ok'=>1, 
			'uid'=>$uid,
		);
	}
}
