<?php
/**
 * Taxonomy lifecycle hooks.
 *
 * @since 1.0.0
 * @package FlowBridge_N8N
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FlowBridge_N8N_Taxonomy_Hooks
 *
 * Listens for term create/update/delete events.
 *
 * @since 1.0.0
 */
class FlowBridge_N8N_Taxonomy_Hooks {

	/**
	 * Constructor. Registers WordPress hooks.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'created_term', array( $this, 'handle_created_term' ), 10, 3 );
		add_action( 'edited_term', array( $this, 'handle_edited_term' ), 10, 3 );
		add_action( 'delete_term', array( $this, 'handle_delete_term' ), 10, 5 );
	}

	/**
	 * Handle term creation.
	 *
	 * @since 1.0.0
	 * @param int    $term_id  Term ID.
	 * @param int    $tt_id    Term taxonomy ID.
	 * @param string $taxonomy Taxonomy slug.
	 * @return void
	 */
	public function handle_created_term( $term_id, $tt_id, $taxonomy ) {
		$this->dispatch( 'term.created', $term_id, $taxonomy );
	}

	/**
	 * Handle term update.
	 *
	 * @since 1.0.0
	 * @param int    $term_id  Term ID.
	 * @param int    $tt_id    Term taxonomy ID.
	 * @param string $taxonomy Taxonomy slug.
	 * @return void
	 */
	public function handle_edited_term( $term_id, $tt_id, $taxonomy ) {
		$this->dispatch( 'term.updated', $term_id, $taxonomy );
	}

	/**
	 * Handle term deletion.
	 *
	 * @since 1.0.0
	 * @param int    $term_id      Term ID.
	 * @param int    $tt_id        Term taxonomy ID.
	 * @param string $taxonomy     Taxonomy slug.
	 * @param mixed  $deleted_term The deleted term object or WP_Error.
	 * @param array  $object_ids   Object IDs associated with the term.
	 * @return void
	 */
	public function handle_delete_term( $term_id, $tt_id, $taxonomy, $deleted_term, $object_ids ) {
		if ( is_wp_error( $deleted_term ) ) {
			return;
		}

		$config = get_option( 'flowbridge_n8n_taxonomy_config', array() );

		if ( empty( $config[ $taxonomy ]['enabled'] ) ) {
			return;
		}

		$events = isset( $config[ $taxonomy ]['events'] ) ? $config[ $taxonomy ]['events'] : array();

		if ( ! in_array( 'term.deleted', $events, true ) ) {
			return;
		}

		$fields = isset( $config[ $taxonomy ]['fields'] ) ? $config[ $taxonomy ]['fields'] : array();

		$raw_data = array(
			'term_id'          => $deleted_term->term_id,
			'name'             => $deleted_term->name,
			'slug'             => $deleted_term->slug,
			'term_group'       => $deleted_term->term_group,
			'term_taxonomy_id' => $deleted_term->term_taxonomy_id,
			'taxonomy'         => $deleted_term->taxonomy,
			'description'      => $deleted_term->description,
			'parent'           => $deleted_term->parent,
			'count'            => $deleted_term->count,
		);

		$mapped  = FlowBridge_N8N_Payload_Builder::map_fields( $raw_data, $fields );
		$payload = FlowBridge_N8N_Payload_Builder::build( 'term.deleted', 'term', $taxonomy, $term_id, $mapped );

		FlowBridge_N8N_Webhook_Sender::send( $payload );
	}

	/**
	 * Dispatch a term event.
	 *
	 * @since 1.0.0
	 * @param string $event    Event name.
	 * @param int    $term_id  Term ID.
	 * @param string $taxonomy Taxonomy slug.
	 * @return void
	 */
	private function dispatch( $event, $term_id, $taxonomy ) {
		$config = get_option( 'flowbridge_n8n_taxonomy_config', array() );

		if ( empty( $config[ $taxonomy ]['enabled'] ) ) {
			return;
		}

		$events = isset( $config[ $taxonomy ]['events'] ) ? $config[ $taxonomy ]['events'] : array();

		if ( ! in_array( $event, $events, true ) ) {
			return;
		}

		$term = get_term( $term_id, $taxonomy );

		if ( is_wp_error( $term ) || ! $term ) {
			return;
		}

		$fields   = isset( $config[ $taxonomy ]['fields'] ) ? $config[ $taxonomy ]['fields'] : array();
		$raw_data = FlowBridge_N8N_Payload_Builder::get_term_data( $term );
		$mapped   = FlowBridge_N8N_Payload_Builder::map_fields( $raw_data, $fields );
		$payload  = FlowBridge_N8N_Payload_Builder::build( $event, 'term', $taxonomy, $term_id, $mapped );

		FlowBridge_N8N_Webhook_Sender::send( $payload );
	}
}
