<?php

add_option('qq-app-login', array(
		"appid" => "",
		"appkey" => "",
	));

class WP_QQ_APP_LOGIN_Options {
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'initMenu' ) );
	}
	public function initMenu() {
		if (class_exists('WP_APP_Collection')){
			WP_APP_Collection::instance()->addOptionPage(
					'QQ登录', 	// 页面标题
					'QQ登录', 		// 菜单标题
					'manage_options', 		// 权限需求
					'qq-app-login', 		// 唯一标识
					array($this, 'render_page')
				);
		} else {
			$hook = add_options_page(
					'移动APP功能：QQ登录', 	// 页面标题
					'移动APP：QQ登录', 		// 菜单标题
					'manage_options', 		// 权限需求
					'qq-app-login', 		// 唯一标识
					array($this, 'render_page')
				);
		}
	}

	public function render_page() {
		if (!current_user_can('manage_options'))
	    {
	      wp_die( __('您没有权限修改此设置，请咨询您的网站管理员。') );
	    }

	    $opt_val = get_option('qq-app-login');

	    if (isset($_POST['appid']) && isset($_POST['appkey'])){
	    	if (!wp_verify_nonce($_POST['nonce'], 'set_option')){
	    		?><div class="err"><p><strong><?php _e('Nonce error.'); ?></strong></p></div><?php
	    	} else {
				$opt_val = array(
		    		'appid'=> $_POST['appid'],
		    		'appkey'=> $_POST['appkey'],
		    	);
		    	update_option('qq-app-login', $opt_val);

		    	?><div class="updated"><p><strong><?php _e('Settings saved.'); ?></strong></p></div><?php
		    }
	    }

	    $csrfkey = ''.rand();

		?><div class="wrap">
			<h2><?php _e('移动APP功能：QQ登录插件'); ?></h2>
			<form method="post" action="">
				<input type="hidden" name="nonce" value="<?php echo wp_create_nonce('set_option'); ?>" />
				<p>
					<?php _e('APPID:')?>
					<input type="text" name="appid" value="<?php echo $opt_val['appid'];?>" />
				</p>
				<p>
					<?php _e('APPKEY:')?>
					<input type="text" name="appkey" value="<?php echo $opt_val['appkey'];?>" />
				</p>
				<p class="submit">
					<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
				</p>
			</form>
		</div><?php
	}
}
