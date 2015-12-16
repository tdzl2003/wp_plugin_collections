<?php
/**
 * Plugin Name: 移动APP:优惠券
 * Description: 可以获取自己的优惠券列表。后台可以销毁优惠券。
 * Author: tdzl2003
 * Author URI: http://github.com/tdzl2003
 * Version: 0.1.0
 * Plugin URI: https://github.com/tdzl2003
 * License: GPL2+
 */

define('WP_APP_COUPON_DB_VERSION', '1.0');

add_option('app-coupon', array(
    'signCount' => 0,
    'inviteCount' => 0,
));


class WP_APP_COUPON {
    var $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'coupon';

        register_activation_hook( __FILE__, array($this, 'install') );

        add_action('rest_api_init', array($this, 'initApi'));

        $option = get_option('app-coupon');
        if ($option['signCount'] > 0){
            add_action('signed_count_updated', array($this, 'onSign'));
        }
        if ($option['inviteCount'] >0){
            add_action('invited_count_updated', array($this, 'onInvite'));
        }
    }
    public function install() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE `".$this->table_name."` (
            coupon_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            coupon_code bigint(20) UNSIGNED NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            used_at datetime DEFAULT NULL,
            PRIMARY KEY (coupon_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta($sql);

        add_option('app-coupon-db-version', WP_APP_COUPON_DB_VERSION);
    }
    public function onSign($info){
        $option = get_option('app-coupon');
        $uid = $info['uid'];
        $count = $info['count'];

        if ($count > $option['signCount']){
            if (update_user_meta($uid, 'signed', $count-$option['signCount'], $count)){
                $this->giveCoupon($info['uid']);
            }
        }
    }
    public function onInvite($info){
        $option = get_option('app-coupon');
        $uid = $info['uid'];
        $count = $info['count'];

        if ($count > $option['inviteCount']){
            if (update_user_meta($uid, 'invited', $count-$option['inviteCount'], $count)){
                $this->giveCoupon($info['uid']);
            }
        }
    }
    public function giveCoupon($uid){
        global $wpdb;
        $result = $wpdb->insert( 
            $this->table_name, 
            array( 
                'user_id' => $uid, 
                'coupon_code' => rand(0, 9999),
            ) 
        );
    }
    public function initApi() {
    }
    public static function instance() {
        static $instance = null;

        if ( is_null( $instance ) ) {
            $instance = new self();
        }

        return $instance;
    }
}

WP_APP_COUPON::instance();

