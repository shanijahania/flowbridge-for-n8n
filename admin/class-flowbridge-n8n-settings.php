<?php
/**
 * Settings save/load via options API.
 *
 * @since 1.0.0
 * @package FlowBridge_N8N
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FlowBridge_N8N_Settings
 *
 * Handles saving and loading plugin settings via admin POST.
 *
 * @since 1.0.0
 */
class FlowBridge_N8N_Settings {

	/**
	 * Constructor. Registers hooks.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'handle_save' ) );
	}

	/**
	 * Handle form submissions for webhook settings.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function handle_save() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Only checking if form was submitted, nonce verified below.
		if ( ! isset( $_POST['flowbridge_n8n_save_settings'] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'flowbridge_n8n_save_settings' ) ) {
			add_action( 'admin_notices', array( $this, 'notice_nonce_error' ) );
			return;
		}

		$settings = array(
			'webhook_url'      => isset( $_POST['webhook_url'] ) ? sanitize_url( wp_unslash( $_POST['webhook_url'] ) ) : '',
			'test_webhook_url' => isset( $_POST['test_webhook_url'] ) ? sanitize_url( wp_unslash( $_POST['test_webhook_url'] ) ) : '',
			'global_enabled'   => ! empty( $_POST['global_enabled'] ),
			'secret_key'       => isset( $_POST['secret_key'] ) ? sanitize_text_field( wp_unslash( $_POST['secret_key'] ) ) : '',
			'timeout'          => isset( $_POST['timeout'] ) ? absint( $_POST['timeout'] ) : 30,
		);

		if ( $settings['timeout'] < 1 ) {
			$settings['timeout'] = 30;
		}

		if ( $settings['timeout'] > 120 ) {
			$settings['timeout'] = 120;
		}

		update_option( 'flowbridge_n8n_settings', $settings );

		add_action( 'admin_notices', array( $this, 'notice_saved' ) );
	}

	/**
	 * Display a success admin notice.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function notice_saved() {
		printf(
			'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
			esc_html__( 'Settings saved.', 'flowbridge-for-n8n' )
		);
	}

	/**
	 * Display a nonce error admin notice.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function notice_nonce_error() {
		printf(
			'<div class="notice notice-error is-dismissible"><p>%s</p></div>',
			esc_html__( 'Security check failed. Please try again.', 'flowbridge-for-n8n' )
		);
	}

	/**
	 * Get the current webhook settings.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public static function get_settings() {
		$defaults = array(
			'webhook_url'      => '',
			'test_webhook_url' => '',
			'global_enabled'   => false,
			'secret_key'       => '',
			'timeout'          => 30,
		);

		$settings = get_option( 'flowbridge_n8n_settings', array() );
		return wp_parse_args( $settings, $defaults );
	}
}
