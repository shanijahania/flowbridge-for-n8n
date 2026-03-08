<?php
/**
 * Taxonomies tab — list all taxonomies with enable/configure.
 *
 * @since 1.0.0
 * @package FlowBridge_N8N
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$flowbridge_n8n_taxonomies  = get_taxonomies( array( 'public' => true ), 'objects' );
$flowbridge_n8n_tax_config  = get_option( 'flowbridge_n8n_taxonomy_config', array() );
$flowbridge_n8n_events_list = array(
	'term.created' => __( 'Created', 'flowbridge-for-n8n' ),
	'term.updated' => __( 'Updated', 'flowbridge-for-n8n' ),
	'term.deleted' => __( 'Deleted', 'flowbridge-for-n8n' ),
);
?>
<div class="flowbridge-card">
	<h2><?php esc_html_e( 'Taxonomies', 'flowbridge-for-n8n' ); ?></h2>
	<p class="flowbridge-description">
		<?php esc_html_e( 'Enable webhooks for specific taxonomies and configure which fields to send.', 'flowbridge-for-n8n' ); ?>
	</p>

	<?php foreach ( $flowbridge_n8n_taxonomies as $tax ) : ?>
		<?php
		$flowbridge_n8n_slug       = $tax->name;
		$flowbridge_n8n_config     = isset( $flowbridge_n8n_tax_config[ $flowbridge_n8n_slug ] ) ? $flowbridge_n8n_tax_config[ $flowbridge_n8n_slug ] : array();
		$flowbridge_n8n_is_enabled = ! empty( $flowbridge_n8n_config['enabled'] );
		$flowbridge_n8n_events     = isset( $flowbridge_n8n_config['events'] ) ? $flowbridge_n8n_config['events'] : array();
		$flowbridge_n8n_fields     = isset( $flowbridge_n8n_config['fields'] ) ? $flowbridge_n8n_config['fields'] : array();
		$flowbridge_n8n_has_config = ! empty( $flowbridge_n8n_fields );
		?>
		<div class="flowbridge-entity-row" data-entity-type="taxonomy" data-entity-key="<?php echo esc_attr( $flowbridge_n8n_slug ); ?>">
			<div class="flowbridge-entity-header">
				<label class="flowbridge-toggle">
					<input type="checkbox"
						   class="flowbridge-entity-toggle"
						   <?php checked( $flowbridge_n8n_is_enabled ); ?>
						   data-entity-type="taxonomy"
						   data-entity-key="<?php echo esc_attr( $flowbridge_n8n_slug ); ?>" />
					<span class="flowbridge-toggle-slider"></span>
				</label>
				<span class="flowbridge-entity-name"><?php echo esc_html( $tax->labels->singular_name ); ?></span>
				<code class="flowbridge-entity-slug"><?php echo esc_html( $flowbridge_n8n_slug ); ?></code>
				<button type="button"
						class="button button-small flowbridge-configure-btn"
						data-entity-type="taxonomy"
						data-entity-key="<?php echo esc_attr( $flowbridge_n8n_slug ); ?>"
						data-entity-label="<?php echo esc_attr( $tax->labels->singular_name ); ?>">
					<?php esc_html_e( 'Configure', 'flowbridge-for-n8n' ); ?>
				</button>
			</div>

			<?php if ( $flowbridge_n8n_has_config ) : ?>
				<div class="flowbridge-collapsible">
					<button type="button" class="flowbridge-collapsible-toggle">
						<?php
						$flowbridge_n8n_enabled_count = count( array_filter( $flowbridge_n8n_fields, function( $f ) {
							return ! empty( $f['enabled'] );
						} ) );
						printf(
							/* translators: 1: number of enabled fields, 2: number of events */
							esc_html__( '%1$d fields, %2$d events configured', 'flowbridge-for-n8n' ),
							absint( $flowbridge_n8n_enabled_count ),
							count( $flowbridge_n8n_events )
						);
						?>
						<span class="dashicons dashicons-arrow-down-alt2"></span>
					</button>
					<div class="flowbridge-collapsible-content" style="display: none;">
						<?php if ( ! empty( $flowbridge_n8n_events ) ) : ?>
							<p><strong><?php esc_html_e( 'Events:', 'flowbridge-for-n8n' ); ?></strong>
								<?php
								$flowbridge_n8n_event_labels = array();
								foreach ( $flowbridge_n8n_events as $flowbridge_n8n_ev ) {
									if ( isset( $flowbridge_n8n_events_list[ $flowbridge_n8n_ev ] ) ) {
										$flowbridge_n8n_event_labels[] = $flowbridge_n8n_events_list[ $flowbridge_n8n_ev ];
									}
								}
								echo esc_html( implode( ', ', $flowbridge_n8n_event_labels ) );
								?>
							</p>
						<?php endif; ?>
						<?php if ( ! empty( $flowbridge_n8n_fields ) ) : ?>
							<table class="flowbridge-mini-table">
								<thead>
									<tr>
										<th><?php esc_html_e( 'Field', 'flowbridge-for-n8n' ); ?></th>
										<th><?php esc_html_e( 'Send As', 'flowbridge-for-n8n' ); ?></th>
										<th><?php esc_html_e( 'Type', 'flowbridge-for-n8n' ); ?></th>
										<th><?php esc_html_e( 'Enabled', 'flowbridge-for-n8n' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $flowbridge_n8n_fields as $flowbridge_n8n_field ) : ?>
										<tr>
											<td><?php echo esc_html( $flowbridge_n8n_field['source'] ); ?></td>
											<td><?php echo esc_html( $flowbridge_n8n_field['send_as'] ); ?></td>
											<td><?php echo esc_html( $flowbridge_n8n_field['type'] ); ?></td>
											<td><?php echo esc_html( $flowbridge_n8n_field['enabled'] ? "\xE2\x9C\x93" : "\xE2\x9C\x97" ); ?></td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						<?php endif; ?>
					</div>
				</div>
			<?php endif; ?>
		</div>
	<?php endforeach; ?>
</div>

<?php include FLOWBRIDGE_N8N_PLUGIN_DIR . 'admin/partials/modal-taxonomy-config.php'; ?>
