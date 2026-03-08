<?php
/**
 * Admin page, menu, and asset registration.
 *
 * @since 1.0.0
 * @package FlowBridge_N8N
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FlowBridge_N8N_Admin
 *
 * Registers the admin menu page, enqueues assets, and routes tabs.
 *
 * @since 1.0.0
 */
class FlowBridge_N8N_Admin {

	/**
	 * Valid tab slugs.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private $tabs = array(
		'webhook'       => 'Webhook',
		'posts'         => 'Posts',
		'taxonomies'    => 'Taxonomies',
		'users'         => 'Users',
		'contact-forms' => 'Contact Forms',
	);

	/**
	 * Constructor. Registers hooks.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'plugin_action_links_' . FLOWBRIDGE_N8N_PLUGIN_BASENAME, array( $this, 'add_settings_link' ) );
	}

	/**
	 * Register the admin menu page under Settings.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function add_menu_page() {
		add_menu_page(
			__( 'FlowBridge Settings', 'flowbridge-for-n8n' ),
			__( 'FlowBridge', 'flowbridge-for-n8n' ),
			'manage_options',
			'flowbridge-for-n8n',
			array( $this, 'render_page' ),
			'dashicons-rest-api',
			80
		);

		add_submenu_page(
			'flowbridge-for-n8n',
			__( 'FlowBridge Settings', 'flowbridge-for-n8n' ),
			__( 'Settings', 'flowbridge-for-n8n' ),
			'manage_options',
			'flowbridge-for-n8n'
		);

		add_submenu_page(
			'flowbridge-for-n8n',
			__( 'Webhook Logs', 'flowbridge-for-n8n' ),
			__( 'Logs', 'flowbridge-for-n8n' ),
			'manage_options',
			'flowbridge-n8n-logs',
			array( $this, 'render_logs_page' )
		);
	}

	/**
	 * Enqueue admin CSS and JS on our settings page only.
	 *
	 * @since 1.0.0
	 * @param string $hook_suffix The current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_assets( $hook_suffix ) {
		$allowed_pages = array(
			'toplevel_page_flowbridge-for-n8n',
			'flowbridge_page_flowbridge-n8n-logs',
		);

		if ( ! in_array( $hook_suffix, $allowed_pages, true ) ) {
			return;
		}

		wp_enqueue_style(
			'flowbridge-admin',
			FLOWBRIDGE_N8N_PLUGIN_URL . 'assets/css/flowbridge-admin.css',
			array(),
			FLOWBRIDGE_N8N_VERSION
		);

		wp_enqueue_script(
			'flowbridge-admin',
			FLOWBRIDGE_N8N_PLUGIN_URL . 'assets/js/flowbridge-admin.js',
			array( 'jquery' ),
			FLOWBRIDGE_N8N_VERSION,
			true
		);

		$settings = FlowBridge_N8N_Settings::get_settings();

		wp_localize_script(
			'flowbridge-admin',
			'flowbridgeAdmin',
			array(
				'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
				'nonce'             => wp_create_nonce( 'flowbridge_n8n_admin' ),
				'hasTestWebhookUrl' => ! empty( $settings['test_webhook_url'] ),
				'hasWebhookUrl'     => ! empty( $settings['webhook_url'] ),
				'entityConfigs'     => array(
					'post'     => get_option( 'flowbridge_n8n_post_config', array() ),
					'taxonomy' => get_option( 'flowbridge_n8n_taxonomy_config', array() ),
					'user'     => get_option( 'flowbridge_n8n_user_config', array() ),
					'cf7'      => get_option( 'flowbridge_n8n_cf7_config', array() ),
				),
				'i18n'              => array(
					'saving'                => __( 'Saving...', 'flowbridge-for-n8n' ),
					'saved'                 => __( 'Saved!', 'flowbridge-for-n8n' ),
					'error'                 => __( 'Error saving settings.', 'flowbridge-for-n8n' ),
					'loading'               => __( 'Loading fields...', 'flowbridge-for-n8n' ),
					'noFields'              => __( 'No fields found.', 'flowbridge-for-n8n' ),
					'testSending'           => __( 'Sending...', 'flowbridge-for-n8n' ),
					'testSuccess'           => __( 'Test webhook sent successfully!', 'flowbridge-for-n8n' ),
					'testError'             => __( 'Test webhook failed.', 'flowbridge-for-n8n' ),
					'confirmReset'          => __( 'Are you sure you want to reset this configuration?', 'flowbridge-for-n8n' ),
					'confirmTestEvent'      => __( 'Send test event to test webhook?', 'flowbridge-for-n8n' ),
					'confirmLiveFallback'   => __( 'Test webhook URL not configured. Send to live URL?', 'flowbridge-for-n8n' ),
					'noWebhookUrlConfigured' => __( 'No webhook URL is configured. Please configure first.', 'flowbridge-for-n8n' ),
					'confirmClearLogs'       => __( 'Are you sure you want to delete all logs? This cannot be undone.', 'flowbridge-for-n8n' ),
				'previewLoading'         => __( 'Generating preview...', 'flowbridge-for-n8n' ),
				'previewError'           => __( 'Could not generate preview.', 'flowbridge-for-n8n' ),
				'selectSampleForPreview' => __( 'Select a sample to preview the output.', 'flowbridge-for-n8n' ),
				),
			)
		);
	}

	/**
	 * Add Settings link on the Plugins page.
	 *
	 * @since 1.0.0
	 * @param array $links Existing plugin action links.
	 * @return array
	 */
	public function add_settings_link( $links ) {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'admin.php?page=flowbridge-for-n8n' ) ),
			esc_html__( 'Settings', 'flowbridge-for-n8n' )
		);
		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Render the admin page with tab routing.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Tab navigation, no data modification.
		$flowbridge_n8n_current_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'webhook';

		if ( ! array_key_exists( $flowbridge_n8n_current_tab, $this->tabs ) ) {
			$flowbridge_n8n_current_tab = 'webhook';
		}

		include FLOWBRIDGE_N8N_PLUGIN_DIR . 'admin/partials/admin-page.php';
	}

	/**
	 * Get the tabs array.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_tabs() {
		return $this->tabs;
	}

	/**
	 * Render the Logs admin page.
	 *
	 * @since 1.2.0
	 * @return void
	 */
	public function render_logs_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		include FLOWBRIDGE_N8N_PLUGIN_DIR . 'admin/partials/page-logs.php';
	}
}
