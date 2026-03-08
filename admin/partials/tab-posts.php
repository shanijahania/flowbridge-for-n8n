<?php
/**
 * Posts tab — list all post types with enable/configure.
 *
 * @since 1.0.0
 * @package FlowBridge_N8N
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$flowbridge_n8n_post_types       = get_post_types( array( 'show_ui' => true ), 'objects' );
$flowbridge_n8n_primary_types    = array();
$flowbridge_n8n_wp_default_types = array();

foreach ( $flowbridge_n8n_post_types as $flowbridge_n8n_pt ) {
	$flowbridge_n8n_hidden_slugs = array( 'e-floating-buttons', 'elementor_library' );
	if ( ( $flowbridge_n8n_pt->_builtin && ! in_array( $flowbridge_n8n_pt->name, array( 'post', 'page' ), true ) ) || in_array( $flowbridge_n8n_pt->name, $flowbridge_n8n_hidden_slugs, true ) ) {
		$flowbridge_n8n_wp_default_types[] = $flowbridge_n8n_pt;
	} else {
		$flowbridge_n8n_primary_types[] = $flowbridge_n8n_pt;
	}
}

$flowbridge_n8n_post_config = get_option( 'flowbridge_n8n_post_config', array() );
$flowbridge_n8n_events_list = array(
	'post.created'        => __( 'Created', 'flowbridge-for-n8n' ),
	'post.updated'        => __( 'Updated', 'flowbridge-for-n8n' ),
	'post.deleted'        => __( 'Deleted', 'flowbridge-for-n8n' ),
	'post.status_changed' => __( 'Status Changed', 'flowbridge-for-n8n' ),
);
?>
<div class="flowbridge-card">
	<h2><?php esc_html_e( 'Post Types', 'flowbridge-for-n8n' ); ?></h2>
	<p class="flowbridge-description">
		<?php esc_html_e( 'Enable webhooks for specific post types and configure which fields to send.', 'flowbridge-for-n8n' ); ?>
	</p>

	<?php foreach ( $flowbridge_n8n_primary_types as $flowbridge_n8n_pt ) : ?>
		<?php
		$flowbridge_n8n_slug       = $flowbridge_n8n_pt->name;
		$flowbridge_n8n_config     = isset( $flowbridge_n8n_post_config[ $flowbridge_n8n_slug ] ) ? $flowbridge_n8n_post_config[ $flowbridge_n8n_slug ] : array();
		$flowbridge_n8n_is_enabled = ! empty( $flowbridge_n8n_config['enabled'] );
		$flowbridge_n8n_events     = isset( $flowbridge_n8n_config['events'] ) ? $flowbridge_n8n_config['events'] : array();
		$flowbridge_n8n_fields     = isset( $flowbridge_n8n_config['fields'] ) ? $flowbridge_n8n_config['fields'] : array();
		$flowbridge_n8n_has_config = ! empty( $flowbridge_n8n_fields );
		?>
		<div class="flowbridge-entity-row" data-entity-type="post" data-entity-key="<?php echo esc_attr( $flowbridge_n8n_slug ); ?>">
			<div class="flowbridge-entity-header">
				<label class="flowbridge-toggle">
					<input type="checkbox"
						   class="flowbridge-entity-toggle"
						   <?php checked( $flowbridge_n8n_is_enabled ); ?>
						   data-entity-type="post"
						   data-entity-key="<?php echo esc_attr( $flowbridge_n8n_slug ); ?>" />
					<span class="flowbridge-toggle-slider"></span>
				</label>
				<span class="flowbridge-entity-name"><?php echo esc_html( $flowbridge_n8n_pt->labels->singular_name ); ?></span>
				<code class="flowbridge-entity-slug"><?php echo esc_html( $flowbridge_n8n_slug ); ?></code>
				<button type="button"
						class="button button-small flowbridge-configure-btn"
						data-entity-type="post"
						data-entity-key="<?php echo esc_attr( $flowbridge_n8n_slug ); ?>"
						data-entity-label="<?php echo esc_attr( $flowbridge_n8n_pt->labels->singular_name ); ?>">
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
							/* translators: 1: number of fields, 2: number of events */
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

	<?php if ( ! empty( $flowbridge_n8n_wp_default_types ) ) : ?>
		<button type="button" class="flowbridge-toggle-wp-defaults-btn">
			<?php esc_html_e( 'Show WordPress Built-in Types', 'flowbridge-for-n8n' ); ?>
		</button>

		<div class="flowbridge-wp-default-types" style="display: none;">
			<?php foreach ( $flowbridge_n8n_wp_default_types as $flowbridge_n8n_pt ) : ?>
				<?php
				$flowbridge_n8n_slug       = $flowbridge_n8n_pt->name;
				$flowbridge_n8n_config     = isset( $flowbridge_n8n_post_config[ $flowbridge_n8n_slug ] ) ? $flowbridge_n8n_post_config[ $flowbridge_n8n_slug ] : array();
				$flowbridge_n8n_is_enabled = ! empty( $flowbridge_n8n_config['enabled'] );
				$flowbridge_n8n_events     = isset( $flowbridge_n8n_config['events'] ) ? $flowbridge_n8n_config['events'] : array();
				$flowbridge_n8n_fields     = isset( $flowbridge_n8n_config['fields'] ) ? $flowbridge_n8n_config['fields'] : array();
				$flowbridge_n8n_has_config = ! empty( $flowbridge_n8n_fields );
				?>
				<div class="flowbridge-entity-row" data-entity-type="post" data-entity-key="<?php echo esc_attr( $flowbridge_n8n_slug ); ?>">
					<div class="flowbridge-entity-header">
						<label class="flowbridge-toggle">
							<input type="checkbox"
								   class="flowbridge-entity-toggle"
								   <?php checked( $flowbridge_n8n_is_enabled ); ?>
								   data-entity-type="post"
								   data-entity-key="<?php echo esc_attr( $flowbridge_n8n_slug ); ?>" />
							<span class="flowbridge-toggle-slider"></span>
						</label>
						<span class="flowbridge-entity-name"><?php echo esc_html( $flowbridge_n8n_pt->labels->singular_name ); ?></span>
						<code class="flowbridge-entity-slug"><?php echo esc_html( $flowbridge_n8n_slug ); ?></code>
						<button type="button"
								class="button button-small flowbridge-configure-btn"
								data-entity-type="post"
								data-entity-key="<?php echo esc_attr( $flowbridge_n8n_slug ); ?>"
								data-entity-label="<?php echo esc_attr( $flowbridge_n8n_pt->labels->singular_name ); ?>">
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
									/* translators: 1: number of fields, 2: number of events */
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
	<?php endif; ?>
</div>

<?php include FLOWBRIDGE_N8N_PLUGIN_DIR . 'admin/partials/modal-post-config.php'; ?>
