<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class Woo_Wallet_Helpers
 *
 * -This class contains user field action takes place on init.
 *
 * @package		WOOWALLET
 * @subpackage	Classes/Woo_Wallet_Helpers
 * @author		Priyanshu Singh
 * @since		1.0.0
 */
class Woo_Wallet_Helpers{

	
	/**
	 * Constructor Method
	 */
	function __construct(){
		// Hooks to add wallet balance field to user profile
		add_action('show_user_profile',array($this,'wallet_balance_field'));
		add_action('edit_user_profile', array($this,'wallet_balance_field'));
		add_action('personal_options_update', array($this,'custom_save_user_wallet_balance_field'));
		add_action('edit_user_profile_update', array($this,'custom_save_user_wallet_balance_field'));
		add_action('user_register', array($this,'set_wallet_balance_for_new_user'));
		
	}
	 /**
	  * Render wallet Balance field in User Profile Sections
	  * @param object $user
	  */
	public function wallet_balance_field($user){
		if (current_user_can('edit_users')) {
			?>
			<h3><?php _e('Wallet Balance', 'textdomain'); ?></h3>
			<table class="form-table">
				<tr>
					<th><label for="wallet_balance"><?php _e('Wallet Balance', 'textdomain'); ?></label></th>
					<td>
						<input type="number" name="wallet_balance" id="wallet_balance" value="<?php echo esc_attr(get_user_meta($user->ID, 'wallet_balance', true)); ?>" class="regular-text" step="any" min="0" />
						<span class="description"><?php _e('The user\'s wallet balance.', 'textdomain'); ?></span>
					</td>
				</tr>
			</table>
			<?php
		}
	}
	/**
	 * Saves wallet balance data on profile update
	 * @param int $user_id
	 */
	public function custom_save_user_wallet_balance_field($user_id) {
		if (current_user_can('edit_users')) {
			if (isset($_POST['wallet_balance'])) {
				$wallet_balance = floatval($_POST['wallet_balance']);
				update_user_meta($user_id, 'wallet_balance', $wallet_balance);
			}
		}
	}
	/**
	 * Set wallet balance to 0 when new user created
	 * @param int $user_id
	 */
	public function set_wallet_balance_for_new_user($user_id) {
		// Check if the user is newly registered
		if (is_int($user_id) && !get_user_meta($user_id, 'wallet_balance', true)) {
			update_user_meta($user_id, 'wallet_balance', 0);
		}
	}
	

}
