<?php
/**
 * Plugin Name: 移动APP:手机登录
 * Description: 提供RESTful的手机登录接口，可以发送验证码，供移动APP使用。
 * Author: tdzl2003
 * Author URI: http://github.com/tdzl2003
 * Version: 0.1.0
 * Plugin URI: https://github.com/tdzl2003
 * License: GPL2+
 */

if(!session_id()) {
    session_start();
}

require_once dirname(__FILE__).'/src/options.php';

require_once dirname(__FILE__).'/src/api.php';

class WP_PHONE_APP_LOGIN {
	var $Options;
	var $Api;

	public function __construct() {
		$this->Options = new WP_PHONE_APP_LOGIN_Options();
		$this->Api = new WP_PHONE_APP_LOGIN_Api();

		register_activation_hook( __FILE__, array($this->Api, 'install') );
	}
	public static function instance() {
		static $instance = null;

		if ( is_null( $instance ) ) {
			$instance = new self();
		}

		return $instance;
	}
}

WP_PHONE_APP_LOGIN::instance();

