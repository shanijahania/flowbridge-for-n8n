<?php
/**
 * Contact Form 7 hooks.
 *
 * @since 1.0.0
 * @package FlowBridge_N8N
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FlowBridge_N8N_CF7_Hooks
 *
 * Listens for CF7 form submission events.
 *
 * @since 1.0.0
 */
class FlowBridge_N8N_CF7_Hooks {

	/**
	 * Constructor. Registers hooks if CF7 is active.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'wpcf7_mail_sent', array( $this, 'handle_mail_sent' ) );
	}

	/**
	 * Handle CF7 form submission.
	 *
	 * @since 1.0.0
	 * @param WPCF7_ContactForm $contact_form The CF7 form instance.
	 * @return void
	 */
	public function handle_mail_sent( $contact_form ) {
		$form_id = $contact_form->id();
		$config  = get_option( 'flowbridge_n8n_cf7_config', array() );

		if ( empty( $config[ $form_id ]['enabled'] ) ) {
			return;
		}

		$submission = WPCF7_Submission::get_instance();

		if ( ! $submission ) {
			return;
		}

		$posted_data = $submission->get_posted_data();
		$fields      = isset( $config[ $form_id ]['fields'] ) ? $config[ $form_id ]['fields'] : array();

		if ( ! empty( $fields ) ) {
			$mapped = FlowBridge_N8N_Payload_Builder::map_fields( $posted_data, $fields );
		} else {
			$mapped = array();
			foreach ( $posted_data as $key => $value ) {
				if ( 0 === strpos( $key, '_wpcf7' ) ) {
					continue;
				}
				$mapped[ $key ] = is_array( $value ) ? implode( ', ', $value ) : $value;
			}
		}

		$payload = FlowBridge_N8N_Payload_Builder::build(
			'cf7.submitted',
			'cf7',
			'form_' . $form_id,
			$form_id,
			$mapped
		);

		FlowBridge_N8N_Webhook_Sender::send( $payload );
	}
}
