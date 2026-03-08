<?php
/**
 * Post lifecycle hooks.
 *
 * @since 1.0.0
 * @package FlowBridge_N8N
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FlowBridge_N8N_Post_Hooks
 *
 * Listens for post create/update/delete/status-change events.
 *
 * @since 1.0.0
 */
class FlowBridge_N8N_Post_Hooks {

	/**
	 * Queued webhook items to send at shutdown.
	 *
	 * Each item contains either a pre-built 'payload' or the info needed to
	 * build the payload at send time (so post meta is available).
	 *
	 * @since 1.1.0
	 * @var array
	 */
	private static $queued = array();

	/**
	 * Constructor. Registers WordPress hooks.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'save_post', array( $this, 'handle_save_post' ), 10, 3 );
		add_action( 'before_delete_post', array( $this, 'handle_delete_post' ), 10, 2 );
		add_action( 'transition_post_status', array( $this, 'handle_status_change' ), 10, 3 );
		add_action( 'shutdown', array( $this, 'send_queued_webhooks' ) );
	}

	/**
	 * Handle post save (create or update).
	 *
	 * @since 1.0.0
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @param bool    $update  Whether this is an update.
	 * @return void
	 */
	public function handle_save_post( $post_id, $post, $update ) {
		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		$post_type = $post->post_type;
		$config    = get_option( 'flowbridge_n8n_post_config', array() );

		if ( empty( $config[ $post_type ]['enabled'] ) ) {
			return;
		}

		$event_name = $update ? 'post.updated' : 'post.created';
		$events     = isset( $config[ $post_type ]['events'] ) ? $config[ $post_type ]['events'] : array();

		if ( ! in_array( $event_name, $events, true ) ) {
			return;
		}

		$fields = isset( $config[ $post_type ]['fields'] ) ? $config[ $post_type ]['fields'] : array();

		self::$queued[] = array(
			'type'       => 'deferred',
			'post_id'    => $post_id,
			'post_type'  => $post_type,
			'event_name' => $event_name,
			'fields'     => $fields,
		);
	}

	/**
	 * Handle post deletion.
	 *
	 * @since 1.0.0
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @return void
	 */
	public function handle_delete_post( $post_id, $post ) {
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		$post_type = $post->post_type;
		$config    = get_option( 'flowbridge_n8n_post_config', array() );

		if ( empty( $config[ $post_type ]['enabled'] ) ) {
			return;
		}

		$events = isset( $config[ $post_type ]['events'] ) ? $config[ $post_type ]['events'] : array();

		if ( ! in_array( 'post.deleted', $events, true ) ) {
			return;
		}

		$fields   = isset( $config[ $post_type ]['fields'] ) ? $config[ $post_type ]['fields'] : array();
		$raw_data = FlowBridge_N8N_Payload_Builder::get_post_data( $post );
		$mapped   = FlowBridge_N8N_Payload_Builder::map_fields( $raw_data, $fields );
		$payload  = FlowBridge_N8N_Payload_Builder::build( 'post.deleted', 'post', $post_type, $post_id, $mapped );

		self::$queued[] = array(
			'type'    => 'ready',
			'payload' => $payload,
		);
	}

	/**
	 * Handle post status transitions.
	 *
	 * @since 1.0.0
	 * @param string  $new_status New post status.
	 * @param string  $old_status Old post status.
	 * @param WP_Post $post       Post object.
	 * @return void
	 */
	public function handle_status_change( $new_status, $old_status, $post ) {
		if ( $new_status === $old_status ) {
			return;
		}

		$post_type = $post->post_type;
		$config    = get_option( 'flowbridge_n8n_post_config', array() );

		if ( empty( $config[ $post_type ]['enabled'] ) ) {
			return;
		}

		$events = isset( $config[ $post_type ]['events'] ) ? $config[ $post_type ]['events'] : array();

		if ( ! in_array( 'post.status_changed', $events, true ) ) {
			return;
		}

		$fields = isset( $config[ $post_type ]['fields'] ) ? $config[ $post_type ]['fields'] : array();

		self::$queued[] = array(
			'type'       => 'deferred',
			'post_id'    => $post->ID,
			'post_type'  => $post_type,
			'event_name' => 'post.status_changed',
			'fields'     => $fields,
			'extra'      => array(
				'_old_status' => $old_status,
				'_new_status' => $new_status,
			),
		);
	}

	/**
	 * Send all queued webhooks.
	 *
	 * Fires on the `shutdown` hook so that post meta saved after
	 * `wp_insert_post()` is available in the payload.
	 *
	 * @since 1.1.0
	 * @return void
	 */
	public function send_queued_webhooks() {
		foreach ( self::$queued as $item ) {
			if ( 'ready' === $item['type'] ) {
				FlowBridge_N8N_Webhook_Sender::send( $item['payload'] );
				continue;
			}

			$post = get_post( $item['post_id'] );
			if ( ! $post ) {
				continue;
			}

			$raw_data = FlowBridge_N8N_Payload_Builder::get_post_data( $post );

			if ( ! empty( $item['extra'] ) ) {
				foreach ( $item['extra'] as $key => $value ) {
					$raw_data[ $key ] = $value;
				}
			}

			$mapped  = FlowBridge_N8N_Payload_Builder::map_fields( $raw_data, $item['fields'] );
			$payload = FlowBridge_N8N_Payload_Builder::build( $item['event_name'], 'post', $item['post_type'], $item['post_id'], $mapped );

			FlowBridge_N8N_Webhook_Sender::send( $payload );
		}

		self::$queued = array();
	}
}
