<?php
/**
 * Plugin Name: FlowBridge (n8n Edition)
 * Plugin URI:  https://equalpixels.com/plugins/flowbridge-for-n8n
 * Description: Connect your WordPress site with n8n workflow automation.
 * Version:     1.1.0
 * Author:      Equal Pixels
 * Author URI:  https://equalpixels.com
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: flowbridge-for-n8n
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'FLOWBRIDGE_N8N_VERSION', '1.1.0' );
define( 'FLOWBRIDGE_N8N_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'FLOWBRIDGE_N8N_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'FLOWBRIDGE_N8N_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Activation hook.
 *
 * @since 1.0.0
 */
function flowbridge_n8n_activate() {
	require_once FLOWBRIDGE_N8N_PLUGIN_DIR . 'includes/class-flowbridge-n8n-activator.php';
	FlowBridge_N8N_Activator::activate();
}
register_activation_hook( __FILE__, 'flowbridge_n8n_activate' );

/**
 * Deactivation hook.
 *
 * @since 1.0.0
 */
function flowbridge_n8n_deactivate() {
	require_once FLOWBRIDGE_N8N_PLUGIN_DIR . 'includes/class-flowbridge-n8n-deactivator.php';
	FlowBridge_N8N_Deactivator::deactivate();
}
register_deactivation_hook( __FILE__, 'flowbridge_n8n_deactivate' );

require_once FLOWBRIDGE_N8N_PLUGIN_DIR . 'includes/class-flowbridge-n8n-loader.php';
FlowBridge_N8N_Loader::get_instance();
