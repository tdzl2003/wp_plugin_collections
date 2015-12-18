<?php

class WP_QQ_APP_LOGIN_Api{
	public function __construct(){
		add_action('rest_api_init', array($this, 'initApi'));
	}
	public function initApi(){
		register_rest_route( 'app/v1', '/login/qq', array(
				'methods'=>'POST',
				'callback'=> array($this, 'processLogin')
			));
		register_rest_route( 'app/v1', '/register/qq', array(
				'methods'=>'POST',
				'callback'=> array($this, 'processRegister')
			));
	}
	public function processLogin(WP_REST_Request $request){
		$params = $request->get_params();

		$option = get_option('qq-app-login');
		$appid = $option['appid'];

		$openid = $params['openId'];
		$token = $params['accessToken'];

		$result = json_decode(wp_remote_get("https://graph.qq.com/user/get_simple_userinfo?access_token={$token}&openid={$openid}&oauth_consumer_key=$appid", [
				'sslcertificates' => dirname(__FILE__).'/ca-bundle.crt'
			])['body'], true);

		if (isset($result['ret']) && $result['ret'] != 0){
			error_log(print_r($result, true));
			return new WP_Error( 'auth_failed', $result['msg'], array( 'status' => 403 ) );
		}

		$uid = wp_app_sso_login("qq", $openid, $token);

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

		$result = json_decode(wp_remote_get("https://graph.qq.com/user/get_simple_userinfo?access_token={$token}&openid={$openid}&oauth_consumer_key=$appid", [
				'sslcertificates' => dirname(__FILE__).'/ca-bundle.crt'
			])['body'], true);

		if (isset($result['ret']) && $result['ret'] != 0){
			error_log(print_r($result, true));
			return new WP_Error( 'auth_failed', $result['msg'], array( 'status' => 403 ) );
		}

		$params['image'] = $result['figureurl_qq_2'];
		$params['nickname'] = $result['nickname'];

		$uid = wp_app_sso_register("qq", $openid, $token, $params);
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
