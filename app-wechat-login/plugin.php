<?php
/**
 * Plugin Name: 移动APP:微信登录
 * Description: 提供RESTful的微信登录接口，供移动APP使用。
 * Author: tdzl2003
 * Author URI: http://github.com/tdzl2003
 * Version: 0.1.0
 * Plugin URI: https://github.com/tdzl2003
 * License: GPL2+
 */

require_once dirname(__FILE__).'/src/options.php';

require_once dirname(__FILE__).'/src/api.php';

class WP_WECHAT_APP_LOGIN {
	var $Options;
	var $Api;

	public function __construct() {
		$this->Options = new WP_WECHAT_APP_LOGIN_Options();
		$this->Api = new WP_WECHAT_APP_LOGIN_Api();
	}
	public static function instance() {
		static $instance = null;

		if ( is_null( $instance ) ) {
			$instance = new self();
		}

		return $instance;
	}
}

WP_WECHAT_APP_LOGIN::instance();

