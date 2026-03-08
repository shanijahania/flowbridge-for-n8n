<?php
/**
 * AJAX handlers for admin operations.
 *
 * @since 1.0.0
 * @package FlowBridge_N8N
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FlowBridge_N8N_Admin_Ajax
 *
 * Handles AJAX requests for loading sample meta and saving entity configs.
 *
 * @since 1.0.0
 */
// phpcs:disable WordPress.Security.NonceVerification.Missing -- All handlers call verify_request() which runs check_ajax_referer() before any $_POST access.
class FlowBridge_N8N_Admin_Ajax {

	/**
	 * Constructor. Registers AJAX hooks.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'wp_ajax_flowbridge_n8n_load_post_fields', array( $this, 'load_post_fields' ) );
		add_action( 'wp_ajax_flowbridge_n8n_load_term_fields', array( $this, 'load_term_fields' ) );
		add_action( 'wp_ajax_flowbridge_n8n_load_user_fields', array( $this, 'load_user_fields' ) );
		add_action( 'wp_ajax_flowbridge_n8n_load_cf7_fields', array( $this, 'load_cf7_fields' ) );
		add_action( 'wp_ajax_flowbridge_n8n_load_sample_posts', array( $this, 'load_sample_posts' ) );
		add_action( 'wp_ajax_flowbridge_n8n_load_sample_terms', array( $this, 'load_sample_terms' ) );
		add_action( 'wp_ajax_flowbridge_n8n_save_entity_config', array( $this, 'save_entity_config' ) );
		add_action( 'wp_ajax_flowbridge_n8n_test_webhook', array( $this, 'test_webhook' ) );
		add_action( 'wp_ajax_flowbridge_n8n_send_test_event', array( $this, 'send_test_event' ) );
		add_action( 'wp_ajax_flowbridge_n8n_toggle_entity', array( $this, 'toggle_entity' ) );
		add_action( 'wp_ajax_flowbridge_n8n_preview_payload', array( $this, 'preview_payload' ) );
		add_action( 'wp_ajax_flowbridge_n8n_clear_logs', array( $this, 'clear_logs' ) );
	}

	/**
	 * Verify AJAX request nonce and capability.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	private function verify_request() {
		if ( ! check_ajax_referer( 'flowbridge_n8n_admin', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'flowbridge-for-n8n' ) ) );
			return false;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'flowbridge-for-n8n' ) ) );
			return false;
		}

		return true;
	}

	/**
	 * Load post fields (columns + meta from a sample post).
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function load_post_fields() {
		$this->verify_request();

		$post_id   = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		$post_type = isset( $_POST['post_type'] ) ? sanitize_key( $_POST['post_type'] ) : '';

		$columns = FlowBridge_N8N_Field_Detector::get_post_columns();
		$meta    = array();
		$post    = null;

		if ( $post_id > 0 ) {
			$post = get_post( $post_id );
			$meta = FlowBridge_N8N_Field_Detector::get_post_meta_keys( $post_id );

			if ( ! $post_type && $post ) {
				$post_type = $post->post_type;
			}
		}

		// Merge in all known meta keys for this post type from the DB.
		if ( $post_type ) {
			$all_keys = FlowBridge_N8N_Field_Detector::get_post_type_meta_keys( $post_type );
			foreach ( $all_keys as $key ) {
				if ( ! array_key_exists( $key, $meta ) ) {
					$meta[ $key ] = '';
				}
			}
		}

		$fields = array();

		foreach ( $columns as $key => $label ) {
			$sample = '';
			if ( $post && isset( $post->$key ) ) {
				$sample = $post->$key;
				if ( is_array( $sample ) ) {
					$sample = wp_json_encode( $sample );
				}
				$sample = (string) $sample;
				if ( strlen( $sample ) > 100 ) {
					$sample = substr( $sample, 0, 100 ) . '...';
				}
			}

			$fields[] = array(
				'source'  => $key,
				'label'   => $label,
				'is_meta' => false,
				'sample'  => $sample,
			);
		}

		foreach ( $meta as $key => $value ) {
			$sample = $value;
			if ( is_array( $sample ) ) {
				$sample = wp_json_encode( $sample );
			}
			if ( is_string( $sample ) && strlen( $sample ) > 100 ) {
				$sample = substr( $sample, 0, 100 ) . '...';
			}

			$fields[] = array(
				'source'  => 'meta:' . $key,
				'label'   => $key,
				'is_meta' => true,
				'sample'  => $sample,
			);
		}

		wp_send_json_success( array( 'fields' => $fields ) );
	}

	/**
	 * Load term fields (columns + meta from a sample term).
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function load_term_fields() {
		$this->verify_request();

		$term_id = isset( $_POST['term_id'] ) ? absint( $_POST['term_id'] ) : 0;

		$columns = FlowBridge_N8N_Field_Detector::get_term_columns();
		$meta    = array();
		$term    = null;

		if ( $term_id > 0 ) {
			$term = get_term( $term_id );
			if ( is_wp_error( $term ) ) {
				$term = null;
			}
			$meta = FlowBridge_N8N_Field_Detector::get_term_meta_keys( $term_id );
		}

		$fields = array();

		foreach ( $columns as $key => $label ) {
			$sample = '';
			if ( $term && isset( $term->$key ) ) {
				$sample = $term->$key;
				if ( is_array( $sample ) ) {
					$sample = wp_json_encode( $sample );
				}
				$sample = (string) $sample;
				if ( strlen( $sample ) > 100 ) {
					$sample = substr( $sample, 0, 100 ) . '...';
				}
			}

			$fields[] = array(
				'source'  => $key,
				'label'   => $label,
				'is_meta' => false,
				'sample'  => $sample,
			);
		}

		foreach ( $meta as $key => $value ) {
			$sample = is_array( $value ) ? wp_json_encode( $value ) : (string) $value;
			if ( strlen( $sample ) > 100 ) {
				$sample = substr( $sample, 0, 100 ) . '...';
			}

			$fields[] = array(
				'source'  => 'meta:' . $key,
				'label'   => $key,
				'is_meta' => true,
				'sample'  => $sample,
			);
		}

		wp_send_json_success( array( 'fields' => $fields ) );
	}

	/**
	 * Load user fields (columns + meta from a sample user).
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function load_user_fields() {
		$this->verify_request();

		$user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;

		$columns = FlowBridge_N8N_Field_Detector::get_user_columns();
		$meta    = array();
		$user    = null;

		if ( $user_id > 0 ) {
			$user = get_userdata( $user_id );
			if ( ! $user ) {
				$user = null;
			}
			$meta = FlowBridge_N8N_Field_Detector::get_user_meta_keys( $user_id );
		}

		$fields = array();

		foreach ( $columns as $key => $label ) {
			$sample = '';
			if ( $user ) {
				$value = isset( $user->$key ) ? $user->$key : '';
				if ( is_array( $value ) ) {
					$sample = wp_json_encode( $value );
				} else {
					$sample = (string) $value;
				}
				if ( strlen( $sample ) > 100 ) {
					$sample = substr( $sample, 0, 100 ) . '...';
				}
			}

			$fields[] = array(
				'source'  => $key,
				'label'   => $label,
				'is_meta' => false,
				'sample'  => $sample,
			);
		}

		foreach ( $meta as $key => $value ) {
			$sample = is_array( $value ) ? wp_json_encode( $value ) : (string) $value;
			if ( strlen( $sample ) > 100 ) {
				$sample = substr( $sample, 0, 100 ) . '...';
			}

			$fields[] = array(
				'source'  => 'meta:' . $key,
				'label'   => $key,
				'is_meta' => true,
				'sample'  => $sample,
			);
		}

		wp_send_json_success( array( 'fields' => $fields ) );
	}

	/**
	 * Load CF7 form fields.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function load_cf7_fields() {
		$this->verify_request();

		$form_id = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;

		if ( ! class_exists( 'WPCF7_ContactForm' ) ) {
			wp_send_json_error( array( 'message' => __( 'Contact Form 7 is not active.', 'flowbridge-for-n8n' ) ) );
			return;
		}

		$cf7_fields = FlowBridge_N8N_Field_Detector::get_cf7_fields( $form_id );
		$fields     = array();

		foreach ( $cf7_fields as $name => $type ) {
			$fields[] = array(
				'source'  => $name,
				'label'   => $name,
				'is_meta' => false,
				'sample'  => $type,
			);
		}

		wp_send_json_success( array( 'fields' => $fields ) );
	}

	/**
	 * Load sample posts filtered by post type.
	 *
	 * @since 1.1.0
	 * @return void
	 */
	public function load_sample_posts() {
		$this->verify_request();

		$post_type = isset( $_POST['post_type'] ) ? sanitize_key( $_POST['post_type'] ) : '';

		if ( ! post_type_exists( $post_type ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid post type.', 'flowbridge-for-n8n' ) ) );
			return;
		}

		$posts = get_posts( array(
			'post_type'              => $post_type,
			'posts_per_page'         => 50,
			'post_status'            => 'any',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		) );

		$items = array();
		foreach ( $posts as $post ) {
			$items[] = array(
				'id'    => $post->ID,
				'title' => $post->post_title . ' (#' . $post->ID . ')',
			);
		}

		wp_send_json_success( array( 'items' => $items ) );
	}

	/**
	 * Load sample terms filtered by taxonomy.
	 *
	 * @since 1.1.0
	 * @return void
	 */
	public function load_sample_terms() {
		$this->verify_request();

		$taxonomy = isset( $_POST['taxonomy'] ) ? sanitize_key( $_POST['taxonomy'] ) : '';

		if ( ! taxonomy_exists( $taxonomy ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid taxonomy.', 'flowbridge-for-n8n' ) ) );
			return;
		}

		$terms = get_terms( array(
			'taxonomy'   => $taxonomy,
			'number'     => 50,
			'hide_empty' => false,
		) );

		$items = array();
		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$items[] = array(
					'id'    => $term->term_id,
					'title' => $term->name . ' (#' . $term->term_id . ')',
				);
			}
		}

		wp_send_json_success( array( 'items' => $items ) );
	}

	/**
	 * Save entity configuration (fields, events, enabled).
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function save_entity_config() {
		$this->verify_request();

		$entity_type = isset( $_POST['entity_type'] ) ? sanitize_key( $_POST['entity_type'] ) : '';
		$entity_key  = isset( $_POST['entity_key'] ) ? sanitize_key( $_POST['entity_key'] ) : '';
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized below with deep sanitization.
		$config_data = isset( $_POST['config'] ) ? wp_unslash( $_POST['config'] ) : '';

		if ( empty( $entity_type ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid entity type.', 'flowbridge-for-n8n' ) ) );
			return;
		}

		$config = json_decode( $config_data, true );

		if ( ! is_array( $config ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid configuration data.', 'flowbridge-for-n8n' ) ) );
			return;
		}

		$config = $this->sanitize_entity_config( $config );

		switch ( $entity_type ) {
			case 'post':
				$all_config = get_option( 'flowbridge_n8n_post_config', array() );
				$all_config[ $entity_key ] = $config;
				update_option( 'flowbridge_n8n_post_config', $all_config );
				break;

			case 'taxonomy':
				$all_config = get_option( 'flowbridge_n8n_taxonomy_config', array() );
				$all_config[ $entity_key ] = $config;
				update_option( 'flowbridge_n8n_taxonomy_config', $all_config );
				break;

			case 'user':
				update_option( 'flowbridge_n8n_user_config', $config );
				break;

			case 'cf7':
				$all_config = get_option( 'flowbridge_n8n_cf7_config', array() );
				$all_config[ $entity_key ] = $config;
				update_option( 'flowbridge_n8n_cf7_config', $all_config );
				break;

			default:
				wp_send_json_error( array( 'message' => __( 'Unknown entity type.', 'flowbridge-for-n8n' ) ) );
				return;
		}

		wp_send_json_success( array( 'message' => __( 'Configuration saved.', 'flowbridge-for-n8n' ) ) );
	}

	/**
	 * Sanitize an entity configuration array.
	 *
	 * @since 1.0.0
	 * @param array $config Raw config data.
	 * @return array Sanitized config.
	 */
	private function sanitize_entity_config( $config ) {
		$sanitized = array(
			'enabled' => ! empty( $config['enabled'] ),
			'events'  => array(),
			'fields'  => array(),
		);

		if ( isset( $config['events'] ) && is_array( $config['events'] ) ) {
			foreach ( $config['events'] as $event ) {
				$sanitized['events'][] = sanitize_text_field( $event );
			}
		}

		if ( isset( $config['fields'] ) && is_array( $config['fields'] ) ) {
			foreach ( $config['fields'] as $field ) {
				$sanitized['fields'][] = array(
					'source'  => isset( $field['source'] ) ? sanitize_text_field( $field['source'] ) : '',
					'label'   => isset( $field['label'] ) ? sanitize_text_field( $field['label'] ) : '',
					'send_as' => isset( $field['send_as'] ) ? sanitize_key( $field['send_as'] ) : '',
					'type'    => isset( $field['type'] ) ? sanitize_key( $field['type'] ) : 'string',
					'enabled' => ! empty( $field['enabled'] ),
					'is_meta' => ! empty( $field['is_meta'] ),
				);
			}
		}

		return $sanitized;
	}

	/**
	 * Test the webhook connection.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_webhook() {
		$this->verify_request();

		$response = FlowBridge_N8N_Webhook_Sender::send_test();

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( array( 'message' => $response->get_error_message() ) );
			return;
		}

		$code = wp_remote_retrieve_response_code( $response );

		if ( $code >= 200 && $code < 300 ) {
			wp_send_json_success( array(
				'message' => sprintf(
					/* translators: %d: HTTP response code */
					__( 'Webhook sent successfully (HTTP %d).', 'flowbridge-for-n8n' ),
					$code
				),
			) );
		} else {
			wp_send_json_error( array(
				'message' => sprintf(
					/* translators: %d: HTTP response code */
					__( 'Webhook returned HTTP %d.', 'flowbridge-for-n8n' ),
					$code
				),
			) );
		}
	}

	/**
	 * Send a test event for a configured entity to the test webhook URL.
	 *
	 * @since 1.1.0
	 * @return void
	 */
	public function send_test_event() {
		$this->verify_request();

		$entity_type = isset( $_POST['entity_type'] ) ? sanitize_key( $_POST['entity_type'] ) : '';
		$entity_key  = isset( $_POST['entity_key'] ) ? sanitize_key( $_POST['entity_key'] ) : '';
		$sample_id   = isset( $_POST['sample_id'] ) ? absint( $_POST['sample_id'] ) : 0;

		$payload = null;

		switch ( $entity_type ) {
			case 'post':
				if ( ! post_type_exists( $entity_key ) ) {
					wp_send_json_error( array( 'message' => __( 'Invalid post type.', 'flowbridge-for-n8n' ) ) );
					return;
				}

				$post = get_post( $sample_id );
				if ( ! $post || $post->post_type !== $entity_key ) {
					wp_send_json_error( array( 'message' => __( 'Invalid sample post.', 'flowbridge-for-n8n' ) ) );
					return;
				}

				$all_config = get_option( 'flowbridge_n8n_post_config', array() );
				$config     = isset( $all_config[ $entity_key ] ) ? $all_config[ $entity_key ] : array();
				$fields     = isset( $config['fields'] ) ? $config['fields'] : array();

				$raw_data = FlowBridge_N8N_Payload_Builder::get_post_data( $post );
				$mapped   = FlowBridge_N8N_Payload_Builder::map_fields( $raw_data, $fields );
				$payload  = FlowBridge_N8N_Payload_Builder::build( 'post.test', 'post', $entity_key, $post->ID, $mapped );
				break;

			case 'taxonomy':
				if ( ! taxonomy_exists( $entity_key ) ) {
					wp_send_json_error( array( 'message' => __( 'Invalid taxonomy.', 'flowbridge-for-n8n' ) ) );
					return;
				}

				$term = get_term( $sample_id );
				if ( ! $term || is_wp_error( $term ) || $term->taxonomy !== $entity_key ) {
					wp_send_json_error( array( 'message' => __( 'Invalid sample term.', 'flowbridge-for-n8n' ) ) );
					return;
				}

				$all_config = get_option( 'flowbridge_n8n_taxonomy_config', array() );
				$config     = isset( $all_config[ $entity_key ] ) ? $all_config[ $entity_key ] : array();
				$fields     = isset( $config['fields'] ) ? $config['fields'] : array();

				$raw_data = FlowBridge_N8N_Payload_Builder::get_term_data( $term );
				$mapped   = FlowBridge_N8N_Payload_Builder::map_fields( $raw_data, $fields );
				$payload  = FlowBridge_N8N_Payload_Builder::build( 'term.test', 'term', $entity_key, $term->term_id, $mapped );
				break;

			case 'user':
				$user = get_userdata( $sample_id );
				if ( ! $user ) {
					wp_send_json_error( array( 'message' => __( 'Invalid sample user.', 'flowbridge-for-n8n' ) ) );
					return;
				}

				$config = get_option( 'flowbridge_n8n_user_config', array() );
				$fields = isset( $config['fields'] ) ? $config['fields'] : array();

				$raw_data = FlowBridge_N8N_Payload_Builder::get_user_data( $user );
				$mapped   = FlowBridge_N8N_Payload_Builder::map_fields( $raw_data, $fields );
				$payload  = FlowBridge_N8N_Payload_Builder::build( 'user.test', 'user', 'user', $user->ID, $mapped );
				break;

			case 'cf7':
				$all_config = get_option( 'flowbridge_n8n_cf7_config', array() );
				$config     = isset( $all_config[ $entity_key ] ) ? $all_config[ $entity_key ] : array();
				$fields     = isset( $config['fields'] ) ? $config['fields'] : array();

				$raw_data = array();
				foreach ( $fields as $field ) {
					if ( ! empty( $field['enabled'] ) && ! empty( $field['source'] ) ) {
						$raw_data[ $field['source'] ] = 'test_value';
					}
				}

				$mapped  = FlowBridge_N8N_Payload_Builder::map_fields( $raw_data, $fields );
				$payload = FlowBridge_N8N_Payload_Builder::build( 'cf7.test', 'cf7', 'form_' . $entity_key, 0, $mapped );
				break;

			default:
				wp_send_json_error( array( 'message' => __( 'Unknown entity type.', 'flowbridge-for-n8n' ) ) );
				return;
		}

		$use_live = ! empty( $_POST['use_live'] );

		if ( $use_live ) {
			$response = FlowBridge_N8N_Webhook_Sender::send( $payload, true );
		} else {
			$response = FlowBridge_N8N_Webhook_Sender::send_to_test( $payload );
		}

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( array( 'message' => $response->get_error_message() ) );
			return;
		}

		$code = wp_remote_retrieve_response_code( $response );

		if ( $code >= 200 && $code < 300 ) {
			wp_send_json_success( array(
				'message' => sprintf(
					/* translators: %d: HTTP response code */
					__( 'Test event sent successfully (HTTP %d).', 'flowbridge-for-n8n' ),
					$code
				),
			) );
		} else {
			$hint = '';
			if ( 404 === $code && ! $use_live ) {
				$hint = ' ' . __( 'Make sure your n8n workflow is in test mode and the webhook node is listening.', 'flowbridge-for-n8n' );
			}
			wp_send_json_error( array(
				'message' => sprintf(
					/* translators: %d: HTTP response code */
					__( 'Webhook returned HTTP %d.', 'flowbridge-for-n8n' ),
					$code
				) . $hint,
			) );
		}
	}

	/**
	 * Preview the JSON payload for the current modal configuration.
	 *
	 * Builds the payload using the unsaved modal state so users can see
	 * exactly what will be sent before saving or testing.
	 *
	 * @since 1.3.0
	 * @return void
	 */
	public function preview_payload() {
		$this->verify_request();

		$entity_type = isset( $_POST['entity_type'] ) ? sanitize_key( $_POST['entity_type'] ) : '';
		$entity_key  = isset( $_POST['entity_key'] ) ? sanitize_key( $_POST['entity_key'] ) : '';
		$sample_id   = isset( $_POST['sample_id'] ) ? absint( $_POST['sample_id'] ) : 0;
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized below with deep sanitization.
		$config_data = isset( $_POST['config'] ) ? wp_unslash( $_POST['config'] ) : '';

		$config = json_decode( $config_data, true );

		if ( ! is_array( $config ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid configuration data.', 'flowbridge-for-n8n' ) ) );
			return;
		}

		$config = $this->sanitize_entity_config( $config );
		$fields = isset( $config['fields'] ) ? $config['fields'] : array();
		$events = isset( $config['events'] ) ? $config['events'] : array();

		$payload = null;

		switch ( $entity_type ) {
			case 'post':
				$default_event = ! empty( $events ) ? $events[0] : 'post.created';

				$post = get_post( $sample_id );
				if ( ! $post || $post->post_type !== $entity_key ) {
					wp_send_json_error( array( 'message' => __( 'Invalid sample post.', 'flowbridge-for-n8n' ) ) );
					return;
				}

				$raw_data = FlowBridge_N8N_Payload_Builder::get_post_data( $post );
				$mapped   = FlowBridge_N8N_Payload_Builder::map_fields( $raw_data, $fields );
				$payload  = FlowBridge_N8N_Payload_Builder::build( $default_event, 'post', $entity_key, $post->ID, $mapped );
				break;

			case 'taxonomy':
				$default_event = ! empty( $events ) ? $events[0] : 'term.created';

				$term = get_term( $sample_id );
				if ( ! $term || is_wp_error( $term ) || $term->taxonomy !== $entity_key ) {
					wp_send_json_error( array( 'message' => __( 'Invalid sample term.', 'flowbridge-for-n8n' ) ) );
					return;
				}

				$raw_data = FlowBridge_N8N_Payload_Builder::get_term_data( $term );
				$mapped   = FlowBridge_N8N_Payload_Builder::map_fields( $raw_data, $fields );
				$payload  = FlowBridge_N8N_Payload_Builder::build( $default_event, 'term', $entity_key, $term->term_id, $mapped );
				break;

			case 'user':
				$default_event = ! empty( $events ) ? $events[0] : 'user.registered';

				$user = get_userdata( $sample_id );
				if ( ! $user ) {
					wp_send_json_error( array( 'message' => __( 'Invalid sample user.', 'flowbridge-for-n8n' ) ) );
					return;
				}

				$raw_data = FlowBridge_N8N_Payload_Builder::get_user_data( $user );
				$mapped   = FlowBridge_N8N_Payload_Builder::map_fields( $raw_data, $fields );
				$payload  = FlowBridge_N8N_Payload_Builder::build( $default_event, 'user', 'user', $user->ID, $mapped );
				break;

			case 'cf7':
				$default_event = 'cf7.submitted';

				$form_id  = absint( $entity_key );
				$raw_data = FlowBridge_N8N_Field_Detector::get_cf7_sample_data( $form_id );

				$mapped  = FlowBridge_N8N_Payload_Builder::map_fields( $raw_data, $fields );
				$payload = FlowBridge_N8N_Payload_Builder::build( $default_event, 'cf7', 'form_' . $entity_key, 0, $mapped );
				break;

			default:
				wp_send_json_error( array( 'message' => __( 'Unknown entity type.', 'flowbridge-for-n8n' ) ) );
				return;
		}

		wp_send_json_success( array( 'payload' => $payload ) );
	}

	/**
	 * Toggle entity enabled/disabled state.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function toggle_entity() {
		$this->verify_request();

		$entity_type = isset( $_POST['entity_type'] ) ? sanitize_key( $_POST['entity_type'] ) : '';
		$entity_key  = isset( $_POST['entity_key'] ) ? sanitize_key( $_POST['entity_key'] ) : '';
		$enabled     = ! empty( $_POST['enabled'] );

		switch ( $entity_type ) {
			case 'post':
				$config = get_option( 'flowbridge_n8n_post_config', array() );
				if ( ! isset( $config[ $entity_key ] ) ) {
					$config[ $entity_key ] = array( 'enabled' => false, 'events' => array(), 'fields' => array() );
				}
				$config[ $entity_key ]['enabled'] = $enabled;
				update_option( 'flowbridge_n8n_post_config', $config );
				break;

			case 'taxonomy':
				$config = get_option( 'flowbridge_n8n_taxonomy_config', array() );
				if ( ! isset( $config[ $entity_key ] ) ) {
					$config[ $entity_key ] = array( 'enabled' => false, 'events' => array(), 'fields' => array() );
				}
				$config[ $entity_key ]['enabled'] = $enabled;
				update_option( 'flowbridge_n8n_taxonomy_config', $config );
				break;

			case 'user':
				$config = get_option( 'flowbridge_n8n_user_config', array() );
				$config['enabled'] = $enabled;
				update_option( 'flowbridge_n8n_user_config', $config );
				break;

			case 'cf7':
				$config = get_option( 'flowbridge_n8n_cf7_config', array() );
				if ( ! isset( $config[ $entity_key ] ) ) {
					$config[ $entity_key ] = array( 'enabled' => false, 'fields' => array() );
				}
				$config[ $entity_key ]['enabled'] = $enabled;
				update_option( 'flowbridge_n8n_cf7_config', $config );
				break;
		}

		wp_send_json_success( array( 'message' => __( 'Updated.', 'flowbridge-for-n8n' ) ) );
	}

	/**
	 * Clear all webhook event logs.
	 *
	 * @since 1.2.0
	 * @return void
	 */
	public function clear_logs() {
		$this->verify_request();

		FlowBridge_N8N_Logger::clear_logs();

		wp_send_json_success( array( 'message' => __( 'All logs have been cleared.', 'flowbridge-for-n8n' ) ) );
	}
}
// phpcs:enable WordPress.Security.NonceVerification.Missing
