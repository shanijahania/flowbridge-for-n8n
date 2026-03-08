<?php
/**
 * Payload builder — constructs JSON payloads from WP data + config.
 *
 * @since 1.0.0
 * @package FlowBridge_N8N
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FlowBridge_N8N_Payload_Builder
 *
 * Builds structured payloads using configured field mappings.
 *
 * @since 1.0.0
 */
class FlowBridge_N8N_Payload_Builder {

	/**
	 * Build the base envelope for a webhook payload.
	 *
	 * @since 1.0.0
	 * @param string $event          Event name (e.g. post.created).
	 * @param string $entity_type    Entity type (post, term, user, cf7).
	 * @param string $entity_subtype Subtype (post type slug, taxonomy slug, etc.).
	 * @param int    $entity_id      Entity ID.
	 * @param array  $data           Mapped field data.
	 * @return array
	 */
	public static function build( $event, $entity_type, $entity_subtype, $entity_id, $data ) {
		return array(
			'event'          => $event,
			'site_url'       => home_url(),
			'timestamp'      => current_time( 'c' ),
			'entity_type'    => $entity_type,
			'entity_subtype' => $entity_subtype,
			'entity_id'      => $entity_id,
			'data'           => $data,
		);
	}

	/**
	 * Map raw entity data through a fields configuration array.
	 *
	 * @since 1.0.0
	 * @param array $raw_data     Associative array of all available data.
	 * @param array $fields_config Array of field config entries.
	 * @return array Mapped data with renamed keys and cast types.
	 */
	public static function map_fields( $raw_data, $fields_config ) {
		$mapped = array();
		$meta   = array();

		foreach ( $fields_config as $field ) {
			$source = isset( $field['source'] ) ? $field['source'] : '';

			if ( empty( $field['enabled'] ) ) {
				continue;
			}

			if ( ! array_key_exists( $source, $raw_data ) ) {
				continue;
			}

			$value   = $raw_data[ $source ];
			$send_as = ! empty( $field['send_as'] ) ? $field['send_as'] : $source;
			$type    = isset( $field['type'] ) ? $field['type'] : 'string';

			if ( 0 === strpos( $source, 'meta:' ) ) {
				// Use send_as if customised, otherwise strip the meta: prefix.
				$meta_key          = ( $send_as !== $source ) ? $send_as : substr( $source, 5 );
				$meta[ $meta_key ] = self::cast_value( $value, $type );
			} else {
				$mapped[ $send_as ] = self::cast_value( $value, $type );
			}
		}

		if ( ! empty( $meta ) ) {
			$mapped['meta'] = $meta;
		}

		return $mapped;
	}

	/**
	 * Cast a value to the specified type.
	 *
	 * @since 1.0.0
	 * @param mixed  $value The value to cast.
	 * @param string $type  The target type (string, int, float, bool, json).
	 * @return mixed
	 */
	public static function cast_value( $value, $type ) {
		switch ( $type ) {
			case 'int':
			case 'integer':
				return intval( $value );
			case 'float':
			case 'number':
				return floatval( $value );
			case 'bool':
			case 'boolean':
				return (bool) $value;
			case 'json':
				if ( is_string( $value ) ) {
					$decoded = json_decode( $value, true );
					return ( null !== $decoded ) ? $decoded : $value;
				}
				return $value;
			case 'string':
			default:
				if ( is_array( $value ) ) {
					return wp_json_encode( $value );
				}
				return (string) $value;
		}
	}

	/**
	 * Get all raw data for a post, including meta.
	 *
	 * @since 1.0.0
	 * @param WP_Post $post The post object.
	 * @return array
	 */
	public static function get_post_data( $post ) {
		$data = array(
			'ID'                    => $post->ID,
			'post_author'           => $post->post_author,
			'post_date'             => $post->post_date,
			'post_date_gmt'         => $post->post_date_gmt,
			'post_content'          => $post->post_content,
			'post_title'            => $post->post_title,
			'post_excerpt'          => $post->post_excerpt,
			'post_status'           => $post->post_status,
			'comment_status'        => $post->comment_status,
			'ping_status'           => $post->ping_status,
			'post_password'         => $post->post_password,
			'post_name'             => $post->post_name,
			'to_ping'               => $post->to_ping,
			'pinged'                => $post->pinged,
			'post_modified'         => $post->post_modified,
			'post_modified_gmt'     => $post->post_modified_gmt,
			'post_content_filtered' => $post->post_content_filtered,
			'post_parent'           => $post->post_parent,
			'guid'                  => $post->guid,
			'menu_order'            => $post->menu_order,
			'post_type'             => $post->post_type,
			'post_mime_type'        => $post->post_mime_type,
			'comment_count'         => $post->comment_count,
		);

		$meta = get_post_meta( $post->ID );
		if ( is_array( $meta ) ) {
			foreach ( $meta as $key => $values ) {
				$data[ 'meta:' . $key ] = ( count( $values ) === 1 ) ? $values[0] : $values;
			}
		}

		return $data;
	}

	/**
	 * Get all raw data for a term, including meta.
	 *
	 * @since 1.0.0
	 * @param WP_Term $term The term object.
	 * @return array
	 */
	public static function get_term_data( $term ) {
		$data = array(
			'term_id'          => $term->term_id,
			'name'             => $term->name,
			'slug'             => $term->slug,
			'term_group'       => $term->term_group,
			'term_taxonomy_id' => $term->term_taxonomy_id,
			'taxonomy'         => $term->taxonomy,
			'description'      => $term->description,
			'parent'           => $term->parent,
			'count'            => $term->count,
		);

		$meta = get_term_meta( $term->term_id );
		if ( is_array( $meta ) ) {
			foreach ( $meta as $key => $values ) {
				$data[ 'meta:' . $key ] = ( count( $values ) === 1 ) ? $values[0] : $values;
			}
		}

		return $data;
	}

	/**
	 * Get all raw data for a user, including meta.
	 *
	 * @since 1.0.0
	 * @param WP_User $user The user object.
	 * @return array
	 */
	public static function get_user_data( $user ) {
		$data = array(
			'ID'              => $user->ID,
			'user_login'      => $user->user_login,
			'user_nicename'   => $user->user_nicename,
			'user_email'      => $user->user_email,
			'user_url'        => $user->user_url,
			'user_registered' => $user->user_registered,
			'user_status'     => $user->user_status,
			'display_name'    => $user->display_name,
			'roles'           => implode( ', ', $user->roles ),
		);

		$meta = get_user_meta( $user->ID );
		if ( is_array( $meta ) ) {
			foreach ( $meta as $key => $values ) {
				$data[ 'meta:' . $key ] = ( count( $values ) === 1 ) ? $values[0] : $values;
			}
		}

		return $data;
	}
}
