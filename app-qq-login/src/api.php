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
	}
	public function processLogin(WP_REST_Request $request){
		if (!function_exists(''))

		$params = $request->get_params();

		$option = get_option('qq-app-login');
		$appid = $option['appid'];

		$openid = $params['openid'];
		$token = $params['access_token'];

		$result = json_decode(wp_remote_get("https://graph.qq.com/user/get_simple_userinfo?access_token={$token}&openid={$openid}&oauth_consumer_key=$appid", [
				'sslcertificates' => dirname(__FILE__).'/ca-bundle.crt'
			])['body'], true);

		if (isset($val['ret']) && $val['ret'] != 0){
			return new WP_Error( 'auth_failed', $val['msg'], array( 'status' => 403 ) );
		}

		if (!wp_app_sso_login("qq", $openid, $token))
		{
			wp_app_sso_register("qq", $openid, $token, array(
					'nickname' => $result['nickname'],
					'image' => $result['figureurl_qq_2'],
				));
		}

		return array(
			'ok'=>1, 
			'user'=>wp_get_current_user()
		);
	}
}
