<?php
/**
 * Fired during plugin activation.
 *
 * @since 1.0.0
 * @package FlowBridge_N8N
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FlowBridge_N8N_Activator
 *
 * Sets default options on activation.
 *
 * @since 1.0.0
 */
class FlowBridge_N8N_Activator {

	/**
	 * Run activation routines.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function activate() {
		$defaults = array(
			'webhook_url'    => '',
			'global_enabled' => false,
			'secret_key'     => '',
			'timeout'        => 30,
		);

		if ( false === get_option( 'flowbridge_n8n_settings' ) ) {
			add_option( 'flowbridge_n8n_settings', $defaults );
		}

		if ( false === get_option( 'flowbridge_n8n_post_config' ) ) {
			add_option( 'flowbridge_n8n_post_config', array() );
		}

		if ( false === get_option( 'flowbridge_n8n_taxonomy_config' ) ) {
			add_option( 'flowbridge_n8n_taxonomy_config', array() );
		}

		if ( false === get_option( 'flowbridge_n8n_user_config' ) ) {
			add_option( 'flowbridge_n8n_user_config', array() );
		}

		if ( false === get_option( 'flowbridge_n8n_cf7_config' ) ) {
			add_option( 'flowbridge_n8n_cf7_config', array() );
		}

		self::create_logs_table();
	}

	/**
	 * Create the webhook event logs table.
	 *
	 * Uses dbDelta for safe creation and future migrations.
	 *
	 * @since 1.2.0
	 * @return void
	 */
	public static function create_logs_table() {
		global $wpdb;

		$table           = $wpdb->prefix . 'flowbridge_n8n_logs';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			event varchar(100) NOT NULL DEFAULT '',
			entity_type varchar(50) NOT NULL DEFAULT '',
			entity_key varchar(100) NOT NULL DEFAULT '',
			entity_id bigint(20) unsigned NOT NULL DEFAULT 0,
			webhook_url text NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'success',
			http_code smallint(5) unsigned DEFAULT NULL,
			response_message text DEFAULT NULL,
			payload longtext DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			duration_ms int(10) unsigned DEFAULT NULL,
			PRIMARY KEY  (id),
			KEY event (event),
			KEY status (status),
			KEY created_at (created_at)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		update_option( 'flowbridge_n8n_db_version', '1.0.0' );
	}
}
