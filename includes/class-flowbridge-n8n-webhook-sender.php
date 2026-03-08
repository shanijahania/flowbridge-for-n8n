<?php
/**
 * Webhook sender — dispatches HTTP requests to n8n.
 *
 * @since 1.0.0
 * @package FlowBridge_N8N
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FlowBridge_N8N_Webhook_Sender
 *
 * Sends JSON payloads to the configured n8n webhook URL.
 *
 * @since 1.0.0
 */
class FlowBridge_N8N_Webhook_Sender {

	/**
	 * Send a payload to the n8n webhook.
	 *
	 * @since 1.0.0
	 * @param array $payload The payload to send.
	 * @param bool  $blocking Whether to wait for a response. Default false.
	 * @return array|WP_Error|null Response array, WP_Error on failure, or null if filtered out.
	 */
	public static function send( $payload, $blocking = false ) {
		$settings = get_option( 'flowbridge_n8n_settings', array() );
		$url      = isset( $settings['webhook_url'] ) ? $settings['webhook_url'] : '';

		if ( empty( $url ) ) {
			return new WP_Error( 'flowbridge_n8n_no_url', __( 'No webhook URL configured.', 'flowbridge-for-n8n' ) );
		}

		$payload_original = $payload;

		/**
		 * Filter the payload before sending.
		 *
		 * Return false to cancel the webhook dispatch.
		 *
		 * @since 1.0.0
		 * @param array  $payload The payload data.
		 * @param string $url     The webhook URL.
		 */
		$payload = apply_filters( 'flowbridge_n8n_before_send', $payload, $url );

		if ( false === $payload ) {
			FlowBridge_N8N_Logger::log( array(
				'event'       => isset( $payload_original['event'] ) ? $payload_original['event'] : 'unknown',
				'entity_type' => isset( $payload_original['entity_type'] ) ? $payload_original['entity_type'] : '',
				'entity_key'  => isset( $payload_original['entity_key'] ) ? $payload_original['entity_key'] : '',
				'entity_id'   => isset( $payload_original['entity_id'] ) ? $payload_original['entity_id'] : 0,
				'webhook_url' => $url,
				'status'      => 'filtered',
				'payload'     => wp_json_encode( $payload_original ),
			) );
			return null;
		}

		$timeout = isset( $settings['timeout'] ) ? absint( $settings['timeout'] ) : 30;
		$secret  = isset( $settings['secret_key'] ) ? $settings['secret_key'] : '';

		$headers = array(
			'Content-Type' => 'application/json',
		);

		if ( ! empty( $secret ) ) {
			$headers['X-FlowBridge-Secret'] = $secret;
		}

		$args = array(
			'body'     => wp_json_encode( $payload ),
			'headers'  => $headers,
			'timeout'  => $timeout,
			'blocking' => $blocking,
		);

		$start_time = microtime( true );
		$response   = wp_remote_post( $url, $args );
		$duration_ms = round( ( microtime( true ) - $start_time ) * 1000 );

		$log_status       = 'success';
		$http_code        = null;
		$response_message = null;

		if ( is_wp_error( $response ) ) {
			$log_status       = 'failed';
			$response_message = $response->get_error_message();
		} else {
			$http_code        = wp_remote_retrieve_response_code( $response );
			$response_message = wp_remote_retrieve_response_message( $response );
			if ( $http_code < 200 || $http_code >= 300 ) {
				$log_status = 'failed';
			}
		}

		FlowBridge_N8N_Logger::log( array(
			'event'            => isset( $payload['event'] ) ? $payload['event'] : 'unknown',
			'entity_type'      => isset( $payload['entity_type'] ) ? $payload['entity_type'] : '',
			'entity_key'       => isset( $payload['entity_key'] ) ? $payload['entity_key'] : '',
			'entity_id'        => isset( $payload['entity_id'] ) ? $payload['entity_id'] : 0,
			'webhook_url'      => $url,
			'status'           => $log_status,
			'http_code'        => $http_code,
			'response_message' => $response_message,
			'payload'          => wp_json_encode( $payload ),
			'duration_ms'      => $duration_ms,
		) );

		/**
		 * Fires after a webhook has been dispatched.
		 *
		 * @since 1.0.0
		 * @param array|WP_Error $response The response or error.
		 * @param array          $payload  The payload that was sent.
		 * @param string         $url      The webhook URL.
		 */
		do_action( 'flowbridge_n8n_after_send', $response, $payload, $url );

		return $response;
	}

	/**
	 * Send a payload to the test webhook URL.
	 *
	 * @since 1.1.0
	 * @param array $payload The payload to send.
	 * @return array|WP_Error Response array or WP_Error on failure.
	 */
	public static function send_to_test( $payload ) {
		$settings = get_option( 'flowbridge_n8n_settings', array() );
		$url      = isset( $settings['test_webhook_url'] ) ? $settings['test_webhook_url'] : '';

		if ( empty( $url ) ) {
			return new WP_Error( 'flowbridge_n8n_no_test_url', __( 'No test webhook URL configured.', 'flowbridge-for-n8n' ) );
		}

		$timeout = isset( $settings['timeout'] ) ? absint( $settings['timeout'] ) : 30;
		$secret  = isset( $settings['secret_key'] ) ? $settings['secret_key'] : '';

		$headers = array(
			'Content-Type' => 'application/json',
		);

		if ( ! empty( $secret ) ) {
			$headers['X-FlowBridge-Secret'] = $secret;
		}

		$args = array(
			'body'     => wp_json_encode( $payload ),
			'headers'  => $headers,
			'timeout'  => $timeout,
			'blocking' => true,
		);

		$start_time = microtime( true );
		$response   = wp_remote_post( $url, $args );
		$duration_ms = round( ( microtime( true ) - $start_time ) * 1000 );

		$log_status       = 'success';
		$http_code        = null;
		$response_message = null;

		if ( is_wp_error( $response ) ) {
			$log_status       = 'failed';
			$response_message = $response->get_error_message();
		} else {
			$http_code        = wp_remote_retrieve_response_code( $response );
			$response_message = wp_remote_retrieve_response_message( $response );
			if ( $http_code < 200 || $http_code >= 300 ) {
				$log_status = 'failed';
			}
		}

		$event_name = isset( $payload['event'] ) ? $payload['event'] : 'unknown';
		if ( 0 !== strpos( $event_name, 'test.' ) ) {
			$event_name = 'test.' . $event_name;
		}

		FlowBridge_N8N_Logger::log( array(
			'event'            => $event_name,
			'entity_type'      => isset( $payload['entity_type'] ) ? $payload['entity_type'] : '',
			'entity_key'       => isset( $payload['entity_key'] ) ? $payload['entity_key'] : '',
			'entity_id'        => isset( $payload['entity_id'] ) ? $payload['entity_id'] : 0,
			'webhook_url'      => $url,
			'status'           => $log_status,
			'http_code'        => $http_code,
			'response_message' => $response_message,
			'payload'          => wp_json_encode( $payload ),
			'duration_ms'      => $duration_ms,
		) );

		return $response;
	}

	/**
	 * Send a test webhook with sample data.
	 *
	 * @since 1.0.0
	 * @return array|WP_Error Response array or WP_Error on failure.
	 */
	public static function send_test() {
		$payload = array(
			'event'       => 'test.ping',
			'site_url'    => home_url(),
			'timestamp'   => current_time( 'c' ),
			'entity_type' => 'test',
			'entity_id'   => 0,
			'data'        => array(
				'message' => __( 'FlowBridge test webhook — connection successful!', 'flowbridge-for-n8n' ),
			),
		);

		return self::send( $payload, true );
	}
}
