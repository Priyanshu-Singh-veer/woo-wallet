<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * HELPER COMMENT START
 * 
 * This is the main class that is responsible for registering
 * the core functions, including the files and setting up all features. 
 * 
 * To add a new class, here's what you need to do: 
 * 1. Add your new class within the following folder: core/includes/classes
 * 2. Create a new variable you want to assign the class to (as e.g. public $helpers)
 * 3. Assign the class within the instance() function ( as e.g. self::$instance->helpers = new Woo_Wallet_Helpers();)
 * 4. Register the class you added to core/includes/classes within the includes() function
 * 
 * HELPER COMMENT END
 */

if ( ! class_exists( 'Woo_Wallet' ) ) :

	/**
	 * Main Woo_Wallet Class.
	 *
	 * @package		WOOWALLET
	 * @subpackage	Classes/Woo_Wallet
	 * @since		1.0.0
	 * @author		Priyanshu Singh
	 */
	final class Woo_Wallet {

		/**
		 * The real instance
		 *
		 * @access	private
		 * @since	1.0.0
		 * @var		object|Woo_Wallet
		 */
		private static $instance;

		/**
		 * WOOWALLET helpers object.
		 *
		 * @access	public
		 * @since	1.0.0
		 * @var		object|Woo_Wallet_Helpers
		 */
		public $helpers;

		/**
		 * WOOWALLET settings object.
		 *
		 * @access	public
		 * @since	1.0.0
		 * @var		object|Woo_Wallet_Settings
		 */
		public $settings;

		/**
		 * Throw error on object clone.
		 *
		 * Cloning instances of the class is forbidden.
		 *
		 * @access	public
		 * @since	1.0.0
		 * @return	void
		 */
		public function __clone() {
			_doing_it_wrong( __FUNCTION__, __( 'You are not allowed to clone this class.', 'woo-wallet' ), '1.0.0' );
		}

		/**
		 * Disable unserializing of the class.
		 *
		 * @access	public
		 * @since	1.0.0
		 * @return	void
		 */
		public function __wakeup() {
			_doing_it_wrong( __FUNCTION__, __( 'You are not allowed to unserialize this class.', 'woo-wallet' ), '1.0.0' );
		}

		/**
		 * Main Woo_Wallet Instance.
		 *
		 * Insures that only one instance of Woo_Wallet exists in memory at any one
		 * time. Also prevents needing to define globals all over the place.
		 *
		 * @access		public
		 * @since		1.0.0
		 * @static
		 * @return		object|Woo_Wallet	The one true Woo_Wallet
		 */
		public static function instance() {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Woo_Wallet ) ) {
				self::$instance					= new Woo_Wallet;
				self::$instance->base_hooks();
				self::$instance->includes();
				self::$instance->helpers		= new Woo_Wallet_Helpers();
				self::$instance->settings		= new Woo_Wallet_Settings();

				//Fire the plugin logic
				new Woo_Wallet_Run();

				/**
				 * Fire a custom action to allow dependencies
				 * after the successful plugin setup
				 */
				do_action( 'WOOWALLET/plugin_loaded' );
			}

			return self::$instance;
		}

		/**
		 * Include required files.
		 *
		 * @access  private
		 * @since   1.0.0
		 * @return  void
		 */
		private function includes() {
			require_once WOOWALLET_PLUGIN_DIR . 'core/includes/classes/class-woo-wallet-helpers.php';
			require_once WOOWALLET_PLUGIN_DIR . 'core/includes/classes/class-woo-wallet-settings.php';

			require_once WOOWALLET_PLUGIN_DIR . 'core/includes/classes/class-woo-wallet-run.php';
			// For woocommerce functionality
			require_once WOOWALLET_PLUGIN_DIR . 'core/includes/classes/woocommerce_wlt.php';
		}

		/**
		 * Add base hooks for the core functionality
		 *
		 * @access  private
		 * @since   1.0.0
		 * @return  void
		 */
		private function base_hooks() {
			add_action( 'plugins_loaded', array( self::$instance, 'load_textdomain' ) );
		}

		/**
		 * Loads the plugin language files.
		 *
		 * @access  public
		 * @since   1.0.0
		 * @return  void
		 */
		public function load_textdomain() {
			load_plugin_textdomain( 'woo-wallet', FALSE, dirname( plugin_basename( WOOWALLET_PLUGIN_FILE ) ) . '/languages/' );
		}

	}

endif; // End if class_exists check.