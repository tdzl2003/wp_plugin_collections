<?php

add_option('phone-app-login', array(
		"mode" => "static",
		"accountid" => "",
		"accounttoken" => "",
		"appid" => "",
		"tid" => "",
		"lifetime" => 10,
	));


class WP_PHONE_APP_LOGIN_Options {
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'initMenu' ) );
	}
	public function initMenu() {
		if (class_exists('WP_APP_Collection')){
			WP_APP_Collection::instance()->addOptionPage(
					'手机登录', 	// 页面标题
					'手机登录', 		// 菜单标题
					'manage_options', 		// 权限需求
					'phone-app-login', 		// 唯一标识
					array($this, 'render_page')
				);
		} else {
			$hook = add_options_page(
					'移动APP功能：手机登录', 	// 页面标题
					'移动APP：手机登录', 		// 菜单标题
					'manage_options', 		// 权限需求
					'phone-app-login', 		// 唯一标识
					array($this, 'render_page')
				);
		}
	}
	public function isValidMode($mode){
		return ($mode=='static') || ($mode=='yuntongxun') || ($mode=='yuntongxuntest');
	}

	public function render_page() {
		if (!current_user_can('manage_options'))
	    {
	      wp_die( __('您没有权限修改此设置，请咨询您的网站管理员。') );
	    }

	    $opt_val = get_option('phone-app-login');

	    if (isset($_POST['mode'])){
	    	if (!wp_verify_nonce($_POST['nonce'], 'set_option') || !$this->isValidMode($_POST['mode'])){
	    		?><div class="err"><p><strong><?php _e('Nonce error.'); ?></strong></p></div><?php
	    	} else {
				$opt_val = array(
					'mode' => $_POST['mode'],
		    		'accountid' => $_POST['accountid'],
		    		'accounttoken' => $_POST['accounttoken'],
		    		'appid' => $_POST['appid'],
		    		'tid' => $_POST['tid'],
		    		'lifetime' => $_POST['lifetime'],
		    	);
		    	update_option('phone-app-login', $opt_val);

		    	?><div class="updated"><p><strong><?php _e('Settings saved.'); ?></strong></p></div><?php
		    }
	    }

	    $csrfkey = ''.rand();

		?><div class="wrap">
			<h2><?php _e('移动APP功能：手机登录插件'); ?></h2>
			<form method="post" action="">
				<input type="hidden" name="nonce" value="<?php echo wp_create_nonce('set_option'); ?>" />
				<p>
					<?php _e('模式:')?><select name="mode" >
						<option value="static" <?php if ($opt_val['mode'] == 'static'){echo 'selected';}?>>不发送验证码</option>
						<option value="yuntongxun" <?php if ($opt_val['mode'] == 'yuntongxun'){echo 'selected';}?>>通过云通讯发送验证码</option>
						<option value="yuntongxuntest" <?php if ($opt_val['mode'] == 'yuntongxuntest'){echo 'selected';}?>>通过云通讯（开发）发送验证码</option>
					</select>
				</p>
				<p>
					<?php _e('Account ID:')?>
					<input type="text" name="accountid" value="<?php echo $opt_val['accountid'];?>" />
				</p>
				<p>
					<?php _e('Account Key:')?>
					<input type="text" name="accounttoken" value="<?php echo $opt_val['accounttoken'];?>" />
				</p>
				<p>
					<?php _e('App Id:')?>
					<input type="text" name="appid" value="<?php echo $opt_val['appid'];?>" />
				</p>
				<p>
					<?php _e('Template ID:')?>
					<input type="text" name="tid" value="<?php echo $opt_val['tid'];?>" />
				</p>
				<p>
					<?php _e('Verify code lifetime:')?>
					<input type="text" name="lifetime" value="<?php echo $opt_val['lifetime'];?>" />
				</p>
				<p class="submit">
					<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
				</p>
			</form>
		</div><?php
	}
}
