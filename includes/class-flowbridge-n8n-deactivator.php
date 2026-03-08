<?php
/**
 * Fired during plugin deactivation.
 *
 * @since 1.0.0
 * @package FlowBridge_N8N
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FlowBridge_N8N_Deactivator
 *
 * Cleans up transients on deactivation.
 *
 * @since 1.0.0
 */
class FlowBridge_N8N_Deactivator {

	/**
	 * Run deactivation routines.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function deactivate() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$wpdb->esc_like( '_transient_flowbridge_n8n_' ) . '%',
				$wpdb->esc_like( '_transient_timeout_flowbridge_n8n_' ) . '%'
			)
		);
	}
}
