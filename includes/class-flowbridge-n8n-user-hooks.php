<?php
/**
 * User lifecycle hooks.
 *
 * @since 1.0.0
 * @package FlowBridge_N8N
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FlowBridge_N8N_User_Hooks
 *
 * Listens for user register/update/delete events.
 *
 * @since 1.0.0
 */
class FlowBridge_N8N_User_Hooks {

	/**
	 * Constructor. Registers WordPress hooks.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'user_register', array( $this, 'handle_user_register' ), 10, 2 );
		add_action( 'profile_update', array( $this, 'handle_profile_update' ), 10, 3 );
		add_action( 'delete_user', array( $this, 'handle_delete_user' ), 10, 3 );
	}

	/**
	 * Handle user registration.
	 *
	 * @since 1.0.0
	 * @param int   $user_id  User ID.
	 * @param array $userdata User data array.
	 * @return void
	 */
	public function handle_user_register( $user_id, $userdata = array() ) {
		$this->dispatch( 'user.registered', $user_id );
	}

	/**
	 * Handle profile update.
	 *
	 * @since 1.0.0
	 * @param int     $user_id       User ID.
	 * @param WP_User $old_user_data Old user object.
	 * @param array   $userdata      New user data.
	 * @return void
	 */
	public function handle_profile_update( $user_id, $old_user_data = null, $userdata = array() ) {
		$this->dispatch( 'user.updated', $user_id );
	}

	/**
	 * Handle user deletion.
	 *
	 * @since 1.0.0
	 * @param int      $user_id  User ID being deleted.
	 * @param int|null $reassign Reassign posts to this user ID, or null.
	 * @param WP_User  $user     User object being deleted.
	 * @return void
	 */
	public function handle_delete_user( $user_id, $reassign = null, $user = null ) {
		if ( null === $user ) {
			$user = get_userdata( $user_id );
		}

		if ( ! $user ) {
			return;
		}

		$this->dispatch( 'user.deleted', $user_id, $user );
	}

	/**
	 * Dispatch a user event.
	 *
	 * @since 1.0.0
	 * @param string       $event   Event name.
	 * @param int          $user_id User ID.
	 * @param WP_User|null $user    Optional pre-fetched user object.
	 * @return void
	 */
	private function dispatch( $event, $user_id, $user = null ) {
		$config = get_option( 'flowbridge_n8n_user_config', array() );

		if ( empty( $config['enabled'] ) ) {
			return;
		}

		$events = isset( $config['events'] ) ? $config['events'] : array();

		if ( ! in_array( $event, $events, true ) ) {
			return;
		}

		if ( null === $user ) {
			$user = get_userdata( $user_id );
		}

		if ( ! $user ) {
			return;
		}

		$fields   = isset( $config['fields'] ) ? $config['fields'] : array();
		$raw_data = FlowBridge_N8N_Payload_Builder::get_user_data( $user );
		$mapped   = FlowBridge_N8N_Payload_Builder::map_fields( $raw_data, $fields );
		$payload  = FlowBridge_N8N_Payload_Builder::build( $event, 'user', 'user', $user_id, $mapped );

		FlowBridge_N8N_Webhook_Sender::send( $payload );
	}
}
