<?php
/**
 * Plugin Name: 移动APP:签到助手
 * Description: 可以每日签到，并累计签到次数。
 * Author: tdzl2003
 * Author URI: http://github.com/tdzl2003
 * Version: 0.1.0
 * Plugin URI: https://github.com/tdzl2003
 * License: GPL2+
 */

class WP_APP_SIGN {
    public function __construct() {
        add_action('rest_api_init', array($this, 'initApi'));
        add_action('manage_users_columns', array($this, 'getUserColumn'));
        add_action('manage_users_custom_column', array($this, 'getColumnData'), 10, 3);
    }
    public function initApi() {
        register_api_field( 'user',
            'signed',
            array(
                'get_callback'    => array($this, 'getSignedCount'),
                'update_callback' => null,
                'schema'          => null,
            )
        );
        register_api_field( 'user', 
            'canSign', 
            array(
                'get_callback'    => array($this, 'getCanSign'),
                'update_callback' => null,
                'schema'          => null,
            )
        );
        register_rest_route( 'app/v1', '/sign', array(
                'methods'=>'POST',
                'callback'=> array($this, 'doSign'),
                'permission_callback' => function () {
                    return current_user_can( 'level_0' );
                }
            ));
    }
    public function doSign(){
        $offset = get_option( 'gmt_offset' ) * HOUR_IN_SECONDS;
        $dayid = floor((time() + $offset) / DAY_IN_SECONDS);
        $uid = get_current_user_id();
        $lastSign = get_user_meta($uid, 'last_sign_at', true);
        if ($lastSign && $lastSign >= $dayid){
            return new WP_Error( 'signed', '当日已签到过', array('status'=>403));
        }
        if ($lastSign){
            $result = update_user_meta($uid, 'last_sign_at', $dayid, $lastSign);
        } else {
            $result = add_user_meta($uid, 'last_sign_at', $dayid, true);
        }
        if (!$result){
            return new WP_Error( 'failed', '签到失败', array('status'=>500));
        }

        while (true){
            $count = get_user_meta($uid, 'signed', true);
            if (!$count){
                if (add_user_meta($uid, 'signed', 1, true)){
                    do_action('signed_count_updated', array(
                            'uid' => $uid,
                            'count' => 1,
                        ));
                    break;
                }
            } else {
                if (update_user_meta($uid, 'signed', $count+1, $count)){
                    do_action('signed_count_updated', array(
                            'uid' => $uid,
                            'count' => $count+1,
                        ));
                    break;
                }
            }
        }
        
        return array(
                "ok" => 1
            );
    }
    public function getCanSign($object, $field_name, $request){
        $offset = get_option( 'gmt_offset' ) * HOUR_IN_SECONDS;
        $dayid = floor((time()+$offset) / DAY_IN_SECONDS);
        $lastSign = get_user_meta($object['id'], 'last_sign_at', true);
        if (!$lastSign || $lastSign < $dayid){
            return true;
        }
        return false;
    }
    public function getSignedCount($object, $field_name, $request){
        $result = get_user_meta($object['id'], $field_name, true);
        if ($result){
            return intval($result);
        }
        return 0;
    }
    public static function instance() {
        static $instance = null;

        if ( is_null( $instance ) ) {
            $instance = new self();
        }

        return $instance;
    }
    public function getUserColumn($headers){
        unset($headers['posts']);
        $headers['signed'] = '签到次数';
        return $headers;
    }
    public function getColumnData($value, $column_name, $user_id){
        if ($column_name == 'signed') {
            return ''.get_user_meta($user_id, $column_name, true);
        }
        return $value;
    }
}

WP_APP_SIGN::instance();

