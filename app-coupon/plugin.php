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
        add_action( 'admin_menu', array( $this, 'initMenu' ) );
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
            PRIMARY KEY (coupon_id),
            KEY user (user_id, used_at)
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
        register_rest_route('app/v1', '/coupons', array(
            'methods'         => 'GET',
            'callback'        => array( $this, 'getItems' ),
            'permission_callback' => function () {
                return current_user_can( 'level_0' );
            }
        ));
        register_api_field( 'user',
            'coupons',
            array(
                'get_callback'    => array($this, 'getCouponCount'),
                'update_callback' => null,
                'schema'          => null,
            )
        );
    }
    public function getCouponCount($object, $field_name, $request){
        global $wpdb;
        $count = $wpdb->get_var( $wpdb->prepare( "SELECT count(*)
            FROM $this->table_name
            WHERE user_id = %d AND ISNULL(used_at)
            ORDER BY used_at=NULL DESC", $object['id'] ) );
        return $count;
    }
    public function getItems(){
        global $wpdb;
        $data = $wpdb->get_results( $wpdb->prepare( "SELECT coupon_id, coupon_code, created_at, used_at
            FROM $this->table_name
            WHERE user_id = %d 
            ORDER BY used_at=NULL DESC", get_current_user_id() ) );

        foreach ($data as $key=>$value){
            $data[$key] = array(
                    'key'=> sprintf('%06d-%04d', $value->coupon_id, $value->coupon_code),
                    'created_at' => $value->created_at,
                    'used_at' => $value->used_at,
                );
        }

        return array(
                'ok' => 1,
                'data' => $data,
            );
    }
    public static function instance() {
        static $instance = null;

        if ( is_null( $instance ) ) {
            $instance = new self();
        }

        return $instance;
    }
    public function initMenu() {
        add_utility_page(
            '兑换券管理', 
            '兑换券', 
            'manage_options', 
            'coupon', 
            array($this, 'renderHomePage'), 
            'dashicons-megaphone'
        );
        add_submenu_page(
            'coupon',
            '兑换券设置',     // 页面标题
            '设置',         // 菜单标题
            'manage_options',       // 权限需求
            'coupon-options',         // 唯一标识
            array($this, 'renderOptionPage')
        );
    }
    public function renderError($msg) {
        ?><div class="error"><p><strong><?php _e($msg); ?></strong></p></div><?php
    }
    public function renderOk($msg) {
        ?><div class="updated"><p><strong><?php _e($msg); ?></strong></p></div><?php
    }
    public function renderGiveResult() {
        $code = $_POST['invite-code'];
        $uid = $code - 100000;
        $user = get_userdata( $uid );
        if ( empty( $uid ) || empty( $user->ID ) ) {
            $this->renderError('找不到该用户，邀请码：'.$code);
            return;
        }
        $this->giveCoupon($uid);
        $this->renderOk('兑换券发放成功。');
    }
    public function renderDismissResult(){
        global $wpdb;

        $code = $_POST['coupon-code'];
        if (!preg_match("/^(\d{6})\-(\d{4})$/", $code, $m)){
            $this->renderError('非法的兑换券'.$code);
            return;
        }
        $count = $wpdb->query( $wpdb->prepare( "UPDATE
            $this->table_name
            SET used_at=CURRENT_TIMESTAMP
            WHERE coupon_id=%d AND coupon_code=%d AND ISNULL(used_at)", $m[1], $m[2] ) );
        if ($count > 0) {
            $this->renderOk('核销成功：'.$code);
        } else {
            $time = $wpdb->get_var($wpdb->prepare("SELECT used_at
                    FROM $this->table_name
                    WHERE coupon_id=%d AND coupon_code=%d
                ", $m[1], $m[2]));
            if ($time){
                $this->renderError('该兑换券已与'.$time.'被核销');
            } else {
                $this->renderError('无效的兑换券');
            }
        }
    }
    public function renderHomePage() {
        if ($_POST['action'] == 'give') {
            $this->renderGiveResult();
        }
        if ($_POST['action'] == 'dismiss') {
            $this->renderDismissResult();
        }
        ?>
        <div class="wrap">
            <h2><?php _e('兑换券发放'); ?></h2>
            <form method="post" action="">
                <input type="hidden" name="action" value="give"/>
                <p>
                    <?php _e('邀请码：'); ?>
                    <input type="text" name="invite-code" />
                </p>
                <p class="submit">
                    <input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('发放'); ?>" />
                </p>
            </form>
        </div>
        <div class="wrap">
            <h2><?php _e('兑换券核销'); ?></h2>
            <form method="post" action="">
                <input type="hidden" name="action" value="dismiss"/>
                <p>
                    <?php _e('兑换券：'); ?>
                    <input type="text" name="coupon-code" />
                </p>
                <p class="submit">
                    <input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('核销'); ?>" />
                </p>
            </form>
        </div>
        <?php
    }
    public function renderOptionPage() {
        if (!current_user_can('manage_options'))
        {
          wp_die( __('您没有权限修改此设置，请咨询您的网站管理员。') );
        }

        $opt_val = get_option('app-coupon');

        if (isset($_POST['signCount']) && isset($_POST['inviteCount'])){
            if (!wp_verify_nonce($_POST['nonce'], 'set_option')){
                ?><div class="err"><p><strong><?php _e('Nonce error.'); ?></strong></p></div><?php
            } else {
                $opt_val = array(
                    'signCount'=> $_POST['signCount'],
                    'inviteCount'=> $_POST['inviteCount'],
                );
                update_option('app-coupon', $opt_val);

                ?><div class="updated"><p><strong><?php _e('Settings saved.'); ?></strong></p></div><?php
            }
        }

        ?><div class="wrap">
            <h2><?php _e('兑换券设置'); ?></h2>
            <form method="post" action="">
                <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('set_option'); ?>" />
                <p>
                    <?php _e('签到多少次奖励兑换券:')?>
                    <input type="text" name="signCount" value="<?php echo $opt_val['signCount'];?>" />
                </p>
                <p>
                    <?php _e('邀请多少好友奖励兑换券:')?>
                    <input type="text" name="inviteCount" value="<?php echo $opt_val['inviteCount'];?>" />
                </p>
                <p class="submit">
                    <input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
                </p>
            </form>
        </div><?php
    }
}

WP_APP_COUPON::instance();

