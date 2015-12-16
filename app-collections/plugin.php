<?php
/**
 * Plugin Name: 移动App:管理菜单
 * Description: 提供APP相关插件的管理整合。
 * Author: tdzl2003
 * Author URI: http://github.com/tdzl2003
 * Version: 0.1.0
 * Plugin URI: https://github.com/tdzl2003
 * License: GPL2+
 */
class WP_APP_Collection {
	public function __construct() {
		if (is_admin()){
			add_action( 'admin_menu', array( $this, 'initMenu' ) );
		}
		add_action( 'init', array($this, 'init'));
		add_action( 'parse_request', array($this, 'parseRequest') );
	}
	public function init(){
		add_rewrite_rule('^app/nonce?$', 'index.php?app_route=get-nonce', 'top');
		global $wp;
		$wp->add_query_var( 'app_route' );
	}
	public function parseRequest(){
		if (empty( $GLOBALS['wp']->query_vars['app_route'] ) ){
			return;
		}
		echo wp_create_nonce('wp_rest');
		die();
	}
	public function initMenu(){
		add_utility_page("移动APP功能管理", '移动APP', 'manage_options', 'app_collection', array($this, 'renderPage'), 'dashicons-megaphone');
	}
	public function renderPage(){
		echo "TODO:";
	}
	public function addOptionPage($page_title, $menu_title, $capability, $menu_slug, $function = ''){
		add_submenu_page('app_collection', $page_title, $menu_title, $capability, $menu_slug, $function);
	}
	public static function instance() {
		static $instance = null;

		if ( is_null( $instance ) ) {
			$instance = new self();
		}

		return $instance;
	}
}

WP_APP_Collection::instance();


function wp_app_sso_login($type, $id, $token){
	global $wpdb;
	$uid = $wpdb -> get_var($wpdb -> prepare("SELECT user_id FROM $wpdb->usermeta um WHERE um.meta_key='%s' AND um.meta_value='%s'", 'open_type_'.$type, $id));
	if (is_wp_error($uid)){
		return $uid;
	}
	if ($uid){
		wp_set_auth_cookie($uid, true, false);
		wp_set_current_user($uid);
		if (isset($token)){
			update_user_meta($uid, 'open_token_'.$type, $token);
		}
	} else {
		return new WP_Error( 'not_registered', '还没有注册。', array( 'status' => 404 ) );
	}
	return $uid;
}

function wp_app_sso_register($type, $id, $token, $info){
	$userdata = array(
		'user_pass' => wp_generate_password(),
		'user_login' => strtoupper($type).'-'.$id,
		'show_admin_bar_front' => 'false',
		'nickname' => $info['nickname'],
		'display_name' => $info['nickname'],
		'user_email' => strtoupper($type).'-'.$id.'@fake.com',
	);

	if(!function_exists('wp_insert_user')){
		include_once( ABSPATH . WPINC . '/registration.php' );
	} 
	$uid = wp_insert_user($userdata);
	if (is_wp_error($uid)){
		return $uid;
	}
	wp_set_auth_cookie($uid, true, false);
	wp_set_current_user($uid);
	if (isset($info['image'])){
		update_user_meta($uid, 'open_img', $info['image']);
	}
	update_user_meta($uid, 'open_type_'.$type, $id);
	
	if (isset($token)){
		update_user_meta($uid, 'open_token_'.$type, $token);
	}

	$info['uid'] = $uid;
	do_action('sso_registered',  $info);
	return $uid;
}
