<?php
/**
 * Plugin Name: 移动APP:QQ登录
 * Description: 提供RESTful的QQ登录接口，供移动APP使用。
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

class WP_QQ_APP_LOGIN {
	var $Options;
	var $Api;

	public function __construct() {
		$this->Options = new WP_QQ_APP_LOGIN_Options();
		$this->Api = new WP_QQ_APP_LOGIN_Api();
	}
	public static function instance() {
		static $instance = null;

		if ( is_null( $instance ) ) {
			$instance = new self();
		}

		return $instance;
	}
}

WP_QQ_APP_LOGIN::instance();

