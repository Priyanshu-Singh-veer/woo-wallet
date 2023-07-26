<?php
/**
 * Woo Wallet
 *
 * @package       WOOWALLET
 * @author        Priyanshu Singh
 * @license       gplv2
 * @version       1.0.0
 *
 * @wordpress-plugin
 * Plugin Name:   Woo Wallet
 * Plugin URI:    https://wisible.in
 * Description:   Adds wallet functionality to wordpress woocommerce
 * Version:       1.0.0
 * Author:        Priyanshu Singh
 * Author URI:    https://github.com/priyanshu-singh-veer
 * Text Domain:   woo-wallet
 * Domain Path:   /languages
 * License:       GPLv2
 * License URI:   https://www.gnu.org/licenses/gpl-2.0.html
 *
 * You should have received a copy of the GNU General Public License
 * along with Woo Wallet. If not, see <https://www.gnu.org/licenses/gpl-2.0.html/>.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * HELPER COMMENT START
 * 
 * This file contains the main information about the plugin.
 * It is used to register all components necessary to run the plugin.
 * 
 * The comment above contains all information about the plugin 
 * that are used by WordPress to differenciate the plugin and register it properly.
 * It also contains further PHPDocs parameter for a better documentation
 * 
 * The function WOOWALLET() is the main function that you will be able to 
 * use throughout your plugin to extend the logic. Further information
 * about that is available within the sub classes.
 * 
 * HELPER COMMENT END
 */

// Plugin name
define( 'WOOWALLET_NAME',			'Woo Wallet' );

// Plugin version
define( 'WOOWALLET_VERSION',		'1.0.0' );

// Plugin Root File
define( 'WOOWALLET_PLUGIN_FILE',	__FILE__ );

// Plugin base
define( 'WOOWALLET_PLUGIN_BASE',	plugin_basename( WOOWALLET_PLUGIN_FILE ) );

// Plugin Folder Path
define( 'WOOWALLET_PLUGIN_DIR',	plugin_dir_path( WOOWALLET_PLUGIN_FILE ) );

// Plugin Folder URL
define( 'WOOWALLET_PLUGIN_URL',	plugin_dir_url( WOOWALLET_PLUGIN_FILE ) );

/**
 * Load the main class for the core functionality
 */
require_once WOOWALLET_PLUGIN_DIR . 'core/class-woo-wallet.php';

/**
 * The main function to load the only instance
 * of our master class.
 *
 * @author  Priyanshu Singh
 * @since   1.0.0
 * @return  object|Woo_Wallet
 */
function WOOWALLET() {
	return Woo_Wallet::instance();
}

WOOWALLET();
