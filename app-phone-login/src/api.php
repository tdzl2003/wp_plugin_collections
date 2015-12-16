<?php

define('WP_PHONE_APP_DB_VERSION', '1.0');

class WP_PHONE_APP_LOGIN_Api{
	var $table_name;
	public function __construct(){
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'verifycode';

		add_action('rest_api_init', array($this, 'initApi'));
	}
	public function install(){
		global $wpdb;
		
		$charset_collate = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE `".$this->table_name."` (
			phone char(12) NOT NULL,
			time datetime  NOT NULL,
			code char(6)  NOT NULL,
			PRIMARY KEY (phone)
		) $charset_collate;";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta($sql);

		add_option('phone-app-db-version', WP_PHONE_APP_DB_VERSION);
	}
	public function validatePhone($phone){
		return preg_match('/^\d{11}$/', $phone);
	}
	public function initApi(){
		register_rest_route( 'app/v1', '/phone/verify', array(
				'methods'=>'POST',
				'callback'=> array($this, 'sendVerifyCode'),
				'args' => array(
		            'phone' => array(
		                'validate_callback' => array($this, 'validatePhone')
		            ),
		        ),
			));
		register_rest_route( 'app/v1', '/login/phone', array(
				'methods'=>'POST',
				'callback'=> array($this, 'processLogin'),
				'args' => array(
		            'phone' => array(
		                'validate_callback' => array($this, 'validatePhone')
		            ),
		        ),
			));
		register_rest_route( 'app/v1', '/register/phone', array(
				'methods'=>'POST',
				'callback'=> array($this, 'processRegister'),
				'args' => array(
		            'phone' => array(
		                'validate_callback' => array($this, 'validatePhone')
		            ),
		        ),
			));
	}
	public function sendVerifyCode(WP_REST_Request $request){
		global $wpdb;

		$params = $request->get_params();
		$option = get_option('phone-app-login');

		$phone = $params['phone'];

		$phone = $wpdb->escape($phone);
		
		$limit = gmdate( 'Y-m-d H:i:s',  time() - 60 );
		// 1分钟内有效的验证码禁止重发。
		$result = $wpdb->query(
			"DELETE FROM `".$this->table_name."`
				WHERE
					`time` < '$limit' AND
					`phone` = '$phone'
				");
		if (is_wp_error($result)){
			return $result;
		}

		$code = sprintf('%04d', rand(0, 9999));
		if ($option['mode'] == 'static') {
			// $code = '1234';
		}

		$result = $wpdb->insert( 
			$this->table_name, 
			array( 
				'time' => gmdate( 'Y-m-d H:i:s',  time() ), 
				'phone' => $phone, 
				'code' => $code, 
			) 
		);
		if (is_wp_error($result)){
			return $result;
		}

		$args = array(
				$code,
				$option['lifetime'],
			);
		if ($option['mode'] == 'static'){
			// 假装已经发了验证码
			return array(
				'ok' => 1,
				'code' => $code,
			);
		} else if ($option['mode'] == 'yuntongxun') {
			// 通过云通讯发送验证码
			return $this->sendYunTongXun($phone, $option, $args);
		} else if ($option['mode'] == 'yuntongxuntest') {
			// 通过云通讯测试发送验证码
			return $this->sendYunTongXun($phone, $option, $args, true);
		} else {
			return new WP_Error( 'internal_error', '模式设置不正确', array('status'=>500));
		}

		return array(
			'ok' => 1,
		);
	}
	public function sendYunTongXun($phone, $option, $args, $isTest){
		if(!function_exists('sendTemplateSMS')){
			include_once( dirname(__FILE__) . '/SendTemplateSMS.php' );
		} 
		$rest = new CCPRESTSmsSDK($isTest ? 'sandboxapp.cloopen.com' : 'app.cloopen.com',8883,'2013-12-26');
		$rest->setAccount($option['accountid'],$option['accounttoken']);
		$rest->setAppId($option['appid']);

		// 发送模板短信
		$result = $rest->sendTemplateSMS($phone,$args,$option['tid']);
		if($result == NULL ) {
			break;
		}
		if($result->statusCode!=0) {
			return new WP_Error('send_sms_error', $result->statusMsg, array('status'=>500));
		}else{
			// 获取返回信息
			$smsmessage = $result->TemplateSMS;
			return array(
				'ok' => 1,
			);
		}
	}
	public function verify($phone, $code){
		global $wpdb;

		$option = get_option('phone-app-login');
		$limit = gmdate( 'Y-m-d H:i:s',  time() - $option['lifetime']*60 );

		$phone = $wpdb->escape($phone);
		$code = $wpdb->escape($code);

		return $wpdb->query(
			"DELETE FROM `".$this->table_name."`
				WHERE
					`time` >= '$limit' AND
					`phone` = '$phone' AND
					`code` = '$code'" );
	}
	public function processRegister(WP_REST_Request $request){
		$params = $request->get_params();
		$option = get_option('phone-app-login');

		$phone = $params['phone'];
		$code = $params['code'];

		$result = $this->verify($phone, $code);
		error_log($result);
		if (is_wp_error($result)){
			return $result;
		}
		if (!$result) {
			return new WP_Error('verify_failed', '验证码验证失败。', array('status'=>403));
		}

		$userdata = array(
			'user_pass' => $params['password'],
			'user_login' => $phone,
			'show_admin_bar_front' => 'false',
			'nickname' => $params['nickname'],
			'display_name' => $params['nickname'],
			'user_email' => strtoupper($phone).'-'.$id.'@fake.com',
		);

		if(!function_exists('wp_insert_user')){
			include_once( ABSPATH . WPINC . '/registration.php' );
		} 
		$uid = wp_insert_user($userdata);
		wp_set_auth_cookie($uid, true, false);
		wp_set_current_user($uid);

		if (is_wp_error($result)){
			return $result;
		}

		update_user_meta($uid, 'open_type_phone', $phone);

		$params['uid'] = $uid;
		do_action('sso_registered', $params);

		return array(
			'ok' => 1,
			'user'=>wp_get_current_user()
		);
	}
	public function processLogin(WP_REST_Request $request){
		$params = $request->get_params();
		$phone = $params['phone'];

		$result = wp_signon(array(
			'user_login' => $phone,
			'user_password' => $params['password'],
			'remember' => true
		));
		if (is_wp_error($result)){
			return $result;
		}
		return array(
			'ok' => 1
		);
	}
}
