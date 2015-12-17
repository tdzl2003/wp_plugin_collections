<?php
/**
 * Plugin Name: 移动APP:分享助手
 * Description: 可以获取自己的邀请码、获取自己的邀请数量，在注册的时候填写邀请码会帮助对方增加邀请数量。注册时可传递参数inviteCode
 * Author: tdzl2003
 * Author URI: http://github.com/tdzl2003
 * Version: 0.1.0
 * Plugin URI: https://github.com/tdzl2003
 * License: GPL2+
 */

class WP_APP_SHARE {
    public function __construct() {
        add_action('rest_api_init', array($this, 'initApi'));
        add_action('sso_registered', array($this, 'onRegisterd'));
    }
    public function initApi() {
        register_api_field( 'user',
            'invited',
            array(
                'get_callback'    => array($this, 'getInvitedCount'),
                'update_callback' => null,
                'schema'          => null,
            )
        );
        register_api_field( 'user', 
            'inviteCode', 
            array(
                'get_callback'    => array($this, 'getInviteCode'),
                'update_callback' => null,
                'schema'          => null,
            )
        );
    }
    public function getInviteCode($object, $field_name, $request){
        return 100000 + $object['id'];
    }
    public function getInvitedCount($object, $field_name, $request){
        $result = get_user_meta($object['id'], $field_name, true);
        if ($result){
            return intval($result);
        }
        return 0;
    }
    public function onRegisterd($params){
        $uid = $params['id'];
        if (isset($params['inviteCode'])){
            $code = $params['inviteCode'];
            $uid = $code - 100000;

            while (true){
                $count = get_user_meta($uid, 'invited', true);
                if (!$count){
                    if (add_user_meta($uid, 'invited', 1, true)){
                        do_action('invited_count_updated', array(
                                'uid' => $uid,
                                'count' => 1,
                            ));
                        return;
                    }
                } else {
                    if (update_user_meta($uid, 'invited', $count+1, $count)){
                        do_action('invited_count_updated', array(
                                'uid' => $uid,
                                'count' => $count+1,
                            ));
                        return;
                    }
                }
            }
        }
    }
    public static function instance() {
        static $instance = null;

        if ( is_null( $instance ) ) {
            $instance = new self();
        }

        return $instance;
    }
}

WP_APP_SHARE::instance();

