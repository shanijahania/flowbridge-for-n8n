<?php
/**
 * Main plugin orchestrator.
 *
 * @since 1.0.0
 * @package FlowBridge_N8N
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FlowBridge_N8N_Loader
 *
 * Singleton orchestrator that loads all plugin components.
 *
 * @since 1.0.0
 */
class FlowBridge_N8N_Loader {

	/**
	 * Singleton instance.
	 *
	 * @since 1.0.0
	 * @var FlowBridge_N8N_Loader|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @since 1.0.0
	 * @return FlowBridge_N8N_Loader
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor. Loads dependencies and registers hooks.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		$this->load_includes();
		$this->maybe_update_db();

		if ( is_admin() ) {
			$this->load_admin();
		}

		$this->register_hooks();
	}

	/**
	 * Check if the database schema needs updating.
	 *
	 * Runs on every load so the logs table is created even on plugin updates
	 * (not just fresh activations).
	 *
	 * @since 1.2.0
	 * @return void
	 */
	private function maybe_update_db() {
		$current_db_version = '1.0.0';
		$installed_version  = get_option( 'flowbridge_n8n_db_version', '0' );

		if ( version_compare( $installed_version, $current_db_version, '<' ) ) {
			require_once FLOWBRIDGE_N8N_PLUGIN_DIR . 'includes/class-flowbridge-n8n-activator.php';
			FlowBridge_N8N_Activator::create_logs_table();
		}
	}

	/**
	 * Load core include files.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function load_includes() {
		require_once FLOWBRIDGE_N8N_PLUGIN_DIR . 'includes/class-flowbridge-n8n-webhook-sender.php';
		require_once FLOWBRIDGE_N8N_PLUGIN_DIR . 'includes/class-flowbridge-n8n-logger.php';
		require_once FLOWBRIDGE_N8N_PLUGIN_DIR . 'includes/class-flowbridge-n8n-payload-builder.php';
		require_once FLOWBRIDGE_N8N_PLUGIN_DIR . 'includes/class-flowbridge-n8n-field-detector.php';
		require_once FLOWBRIDGE_N8N_PLUGIN_DIR . 'includes/class-flowbridge-n8n-post-hooks.php';
		require_once FLOWBRIDGE_N8N_PLUGIN_DIR . 'includes/class-flowbridge-n8n-taxonomy-hooks.php';
		require_once FLOWBRIDGE_N8N_PLUGIN_DIR . 'includes/class-flowbridge-n8n-user-hooks.php';
		require_once FLOWBRIDGE_N8N_PLUGIN_DIR . 'includes/class-flowbridge-n8n-cf7-hooks.php';
	}

	/**
	 * Load admin-specific files.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function load_admin() {
		require_once FLOWBRIDGE_N8N_PLUGIN_DIR . 'admin/class-flowbridge-n8n-admin.php';
		require_once FLOWBRIDGE_N8N_PLUGIN_DIR . 'admin/class-flowbridge-n8n-settings.php';
		require_once FLOWBRIDGE_N8N_PLUGIN_DIR . 'admin/class-flowbridge-n8n-admin-ajax.php';

		new FlowBridge_N8N_Admin();
		new FlowBridge_N8N_Settings();
		new FlowBridge_N8N_Admin_Ajax();
	}

	/**
	 * Register webhook hook handlers.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function register_hooks() {
		$settings = get_option( 'flowbridge_n8n_settings', array() );

		if ( empty( $settings['global_enabled'] ) || empty( $settings['webhook_url'] ) ) {
			return;
		}

		new FlowBridge_N8N_Post_Hooks();
		new FlowBridge_N8N_Taxonomy_Hooks();
		new FlowBridge_N8N_User_Hooks();
		new FlowBridge_N8N_CF7_Hooks();
	}
}
