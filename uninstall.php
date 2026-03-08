<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @since 1.0.0
 * @package FlowBridge_N8N
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'flowbridge_n8n_settings' );
delete_option( 'flowbridge_n8n_post_config' );
delete_option( 'flowbridge_n8n_taxonomy_config' );
delete_option( 'flowbridge_n8n_user_config' );
delete_option( 'flowbridge_n8n_cf7_config' );
delete_option( 'flowbridge_n8n_db_version' );

// Drop the logs table.
global $wpdb;
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}flowbridge_n8n_logs" );
