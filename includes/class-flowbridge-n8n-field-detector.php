<?php
/**
 * Field detector — discovers table fields + meta for entities.
 *
 * @since 1.0.0
 * @package FlowBridge_N8N
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FlowBridge_N8N_Field_Detector
 *
 * Detects available fields and meta keys for posts, terms, and users.
 *
 * @since 1.0.0
 */
class FlowBridge_N8N_Field_Detector {

	/**
	 * Get standard post table columns.
	 *
	 * @since 1.0.0
	 * @return array Array of column name => label pairs.
	 */
	public static function get_post_columns() {
		return array(
			'ID'                    => 'ID',
			'post_author'           => 'Author ID',
			'post_date'             => 'Date',
			'post_date_gmt'         => 'Date (GMT)',
			'post_content'          => 'Content',
			'post_title'            => 'Title',
			'post_excerpt'          => 'Excerpt',
			'post_status'           => 'Status',
			'comment_status'        => 'Comment Status',
			'ping_status'           => 'Ping Status',
			'post_password'         => 'Password',
			'post_name'             => 'Slug',
			'to_ping'               => 'To Ping',
			'pinged'                => 'Pinged',
			'post_modified'         => 'Modified Date',
			'post_modified_gmt'     => 'Modified Date (GMT)',
			'post_content_filtered' => 'Filtered Content',
			'post_parent'           => 'Parent ID',
			'guid'                  => 'GUID',
			'menu_order'            => 'Menu Order',
			'post_type'             => 'Post Type',
			'post_mime_type'        => 'MIME Type',
			'comment_count'         => 'Comment Count',
		);
	}

	/**
	 * Get meta keys for a specific post.
	 *
	 * @since 1.0.0
	 * @param int $post_id The post ID to inspect.
	 * @return array Array of meta key => sample value pairs.
	 */
	public static function get_post_meta_keys( $post_id ) {
		$meta   = get_post_meta( $post_id );
		$result = array();

		if ( is_array( $meta ) ) {
			foreach ( $meta as $key => $values ) {
				$result[ $key ] = ( count( $values ) === 1 ) ? $values[0] : $values;
			}
		}

		return $result;
	}

	/**
	 * Get standard term table columns.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public static function get_term_columns() {
		return array(
			'term_id'          => 'Term ID',
			'name'             => 'Name',
			'slug'             => 'Slug',
			'term_group'       => 'Term Group',
			'term_taxonomy_id' => 'Term Taxonomy ID',
			'taxonomy'         => 'Taxonomy',
			'description'      => 'Description',
			'parent'           => 'Parent ID',
			'count'            => 'Count',
		);
	}

	/**
	 * Get meta keys for a specific term.
	 *
	 * @since 1.0.0
	 * @param int $term_id The term ID to inspect.
	 * @return array
	 */
	public static function get_term_meta_keys( $term_id ) {
		$meta   = get_term_meta( $term_id );
		$result = array();

		if ( is_array( $meta ) ) {
			foreach ( $meta as $key => $values ) {
				$result[ $key ] = ( count( $values ) === 1 ) ? $values[0] : $values;
			}
		}

		return $result;
	}

	/**
	 * Get standard user table columns.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public static function get_user_columns() {
		return array(
			'ID'              => 'ID',
			'user_login'      => 'Username',
			'user_nicename'   => 'Nicename',
			'user_email'      => 'Email',
			'user_url'        => 'Website',
			'user_registered' => 'Registration Date',
			'user_status'     => 'Status',
			'display_name'    => 'Display Name',
			'roles'           => 'Roles',
		);
	}

	/**
	 * Get meta keys for a specific user.
	 *
	 * @since 1.0.0
	 * @param int $user_id The user ID to inspect.
	 * @return array
	 */
	public static function get_user_meta_keys( $user_id ) {
		$meta   = get_user_meta( $user_id );
		$result = array();

		if ( is_array( $meta ) ) {
			foreach ( $meta as $key => $values ) {
				$result[ $key ] = ( count( $values ) === 1 ) ? $values[0] : $values;
			}
		}

		return $result;
	}

	/**
	 * Get all distinct meta keys for a given post type from the database.
	 *
	 * This queries the postmeta table directly so that meta keys are discovered
	 * even when the selected sample post does not have them.
	 *
	 * @since 1.1.0
	 * @param string $post_type The post type slug.
	 * @return array Flat array of meta key strings.
	 */
	public static function get_post_type_meta_keys( $post_type ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct meta key query, not cacheable.
		$keys = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT pm.meta_key
				 FROM {$wpdb->postmeta} pm
				 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
				 WHERE p.post_type = %s
				 ORDER BY pm.meta_key",
				$post_type
			)
		);

		return is_array( $keys ) ? $keys : array();
	}

	/**
	 * Get sample data for a CF7 form based on field types.
	 *
	 * Generates realistic placeholder values for each field type so that
	 * the preview payload contains meaningful sample data.
	 *
	 * @since 1.3.0
	 * @param int $form_id The CF7 form post ID.
	 * @return array Associative array of field_name => sample_value.
	 */
	public static function get_cf7_sample_data( $form_id ) {
		$fields = self::get_cf7_fields( $form_id );
		$data   = array();

		$type_samples = array(
			'text'       => 'Sample text',
			'email'      => 'user@example.com',
			'url'        => 'https://example.com',
			'tel'        => '+1-555-0100',
			'textarea'   => 'This is a sample message.',
			'number'     => '42',
			'date'       => '2026-03-08',
			'select'     => 'option-1',
			'radio'      => 'option-1',
			'checkbox'   => '1',
			'acceptance' => '1',
			'file'       => 'document.pdf',
		);

		foreach ( $fields as $name => $basetype ) {
			$data[ $name ] = isset( $type_samples[ $basetype ] ) ? $type_samples[ $basetype ] : 'sample_value';
		}

		return $data;
	}

	/**
	 * Get CF7 form fields by parsing form tags.
	 *
	 * @since 1.0.0
	 * @param int $form_id The CF7 form post ID.
	 * @return array Array of field name => field type pairs.
	 */
	public static function get_cf7_fields( $form_id ) {
		if ( ! class_exists( 'WPCF7_ContactForm' ) ) {
			return array();
		}

		$form = wpcf7_contact_form( $form_id );
		if ( ! $form ) {
			return array();
		}

		$tags   = $form->scan_form_tags();
		$fields = array();

		foreach ( $tags as $tag ) {
			if ( empty( $tag->name ) ) {
				continue;
			}
			$fields[ $tag->name ] = $tag->basetype;
		}

		return $fields;
	}
}
