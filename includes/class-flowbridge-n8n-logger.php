<?php
/**
 * Webhook event logger.
 *
 * @since 1.2.0
 * @package FlowBridge_N8N
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FlowBridge_N8N_Logger
 *
 * Logs webhook dispatch events to a custom database table for debugging and auditing.
 *
 * @since 1.2.0
 */
class FlowBridge_N8N_Logger {

	/**
	 * Insert a log entry.
	 *
	 * @since 1.2.0
	 * @param array $args {
	 *     Log entry data.
	 *
	 *     @type string $event            Event name (e.g. 'post.created').
	 *     @type string $entity_type      Entity type (e.g. 'post', 'term', 'user').
	 *     @type string $entity_key       Entity key (e.g. post type slug).
	 *     @type int    $entity_id        Entity ID.
	 *     @type string $webhook_url      The webhook URL that was called.
	 *     @type string $status           Status: 'success', 'failed', or 'filtered'.
	 *     @type int    $http_code        HTTP response code.
	 *     @type string $response_message Response message or error description.
	 *     @type string $payload          JSON-encoded payload string.
	 *     @type string $created_at       Datetime string.
	 *     @type int    $duration_ms      Duration of the request in milliseconds.
	 * }
	 * @return int|false The number of rows inserted, or false on error.
	 */
	public static function log( $args ) {
		global $wpdb;

		$defaults = array(
			'event'            => '',
			'entity_type'      => '',
			'entity_key'       => '',
			'entity_id'        => 0,
			'webhook_url'      => '',
			'status'           => 'success',
			'http_code'        => null,
			'response_message' => null,
			'payload'          => null,
			'created_at'       => current_time( 'mysql' ),
			'duration_ms'      => null,
		);

		$data = wp_parse_args( $args, $defaults );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom logging table.
		return $wpdb->insert(
			$wpdb->prefix . 'flowbridge_n8n_logs',
			array(
				'event'            => sanitize_text_field( $data['event'] ),
				'entity_type'      => sanitize_text_field( $data['entity_type'] ),
				'entity_key'       => sanitize_text_field( $data['entity_key'] ),
				'entity_id'        => absint( $data['entity_id'] ),
				'webhook_url'      => esc_url_raw( $data['webhook_url'] ),
				'status'           => sanitize_key( $data['status'] ),
				'http_code'        => null !== $data['http_code'] ? absint( $data['http_code'] ) : null,
				'response_message' => null !== $data['response_message'] ? sanitize_text_field( $data['response_message'] ) : null,
				'payload'          => $data['payload'],
				'created_at'       => $data['created_at'],
				'duration_ms'      => null !== $data['duration_ms'] ? absint( $data['duration_ms'] ) : null,
			),
			array( '%s', '%s', '%s', '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%d' )
		);
	}

	/**
	 * Get paginated log entries with optional filters.
	 *
	 * @since 1.2.0
	 * @param array $args {
	 *     Query arguments.
	 *
	 *     @type int    $per_page Number of results per page. Default 20.
	 *     @type int    $page     Current page number. Default 1.
	 *     @type string $status   Filter by status.
	 *     @type string $event    Filter by event name.
	 *     @type string $search   Search event, entity_type, and entity_key.
	 *     @type string $orderby  Column to order by. Default 'created_at'.
	 *     @type string $order    Sort direction. Default 'DESC'.
	 * }
	 * @return array {
	 *     @type array $items Array of log row objects.
	 *     @type int   $total Total number of matching rows.
	 * }
	 */
	public static function get_logs( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'per_page' => 20,
			'page'     => 1,
			'status'   => '',
			'event'    => '',
			'search'   => '',
			'orderby'  => 'created_at',
			'order'    => 'DESC',
		);

		$args  = wp_parse_args( $args, $defaults );
		$table = $wpdb->prefix . 'flowbridge_n8n_logs';

		$where  = array();
		$values = array();

		if ( ! empty( $args['status'] ) ) {
			$where[]  = 'status = %s';
			$values[] = sanitize_key( $args['status'] );
		}

		if ( ! empty( $args['event'] ) ) {
			$where[]  = 'event LIKE %s';
			$values[] = '%' . $wpdb->esc_like( sanitize_text_field( $args['event'] ) ) . '%';
		}

		if ( ! empty( $args['search'] ) ) {
			$like     = '%' . $wpdb->esc_like( sanitize_text_field( $args['search'] ) ) . '%';
			$where[]  = '(event LIKE %s OR entity_type LIKE %s OR entity_key LIKE %s)';
			$values[] = $like;
			$values[] = $like;
			$values[] = $like;
		}

		$where_sql = '';
		if ( ! empty( $where ) ) {
			$where_sql = 'WHERE ' . implode( ' AND ', $where );
		}

		$allowed_orderby = array( 'id', 'event', 'status', 'http_code', 'created_at', 'duration_ms' );
		$orderby         = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'created_at';
		$order           = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';

		$per_page = absint( $args['per_page'] );
		$page     = absint( $args['page'] );
		if ( $per_page < 1 ) {
			$per_page = 20;
		}
		if ( $page < 1 ) {
			$page = 1;
		}
		$offset = ( $page - 1 ) * $per_page;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table is safe, $where_sql built with placeholders.
		$count_query = "SELECT COUNT(*) FROM {$table} {$where_sql}";
		if ( ! empty( $values ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Dynamic query built safely above. Custom logging table, not cacheable.
			$total = (int) $wpdb->get_var( $wpdb->prepare( $count_query, $values ) );
		} else {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- No user input in query. Custom logging table, not cacheable.
			$total = (int) $wpdb->get_var( $count_query );
		}

		$query_values   = $values;
		$query_values[] = $offset;
		$query_values[] = $per_page;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table, $where_sql, $orderby, $order are safe.
		$items_query = "SELECT * FROM {$table} {$where_sql} ORDER BY {$orderby} {$order} LIMIT %d, %d";
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Dynamic query built safely above. Custom logging table, not cacheable.
		$items = $wpdb->get_results( $wpdb->prepare( $items_query, $query_values ) );

		return array(
			'items' => $items ? $items : array(),
			'total' => $total,
		);
	}

	/**
	 * Delete a single log entry.
	 *
	 * @since 1.2.0
	 * @param int $id Log entry ID.
	 * @return int|false Number of rows deleted, or false on error.
	 */
	public static function delete_log( $id ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom logging table.
		return $wpdb->delete(
			$wpdb->prefix . 'flowbridge_n8n_logs',
			array( 'id' => absint( $id ) ),
			array( '%d' )
		);
	}

	/**
	 * Delete all log entries.
	 *
	 * @since 1.2.0
	 * @return int|false Number of rows deleted, or false on error.
	 */
	public static function clear_logs() {
		global $wpdb;

		$table = $wpdb->prefix . 'flowbridge_n8n_logs';

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- $table uses $wpdb->prefix which is safe. Custom logging table.
		return $wpdb->query( "TRUNCATE TABLE {$table}" );
	}

	/**
	 * Get total number of log entries.
	 *
	 * @since 1.2.0
	 * @return int
	 */
	public static function get_total_count() {
		global $wpdb;

		$table = $wpdb->prefix . 'flowbridge_n8n_logs';

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- $table uses $wpdb->prefix which is safe. Custom logging table, not cacheable.
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
	}
}
