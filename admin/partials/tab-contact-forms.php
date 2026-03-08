<?php
/**
 * Contact Forms tab — list CF7 forms with enable/configure.
 *
 * @since 1.0.0
 * @package FlowBridge_N8N
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WPCF7_ContactForm' ) ) : ?>
	<div class="flowbridge-card">
		<h2><?php esc_html_e( 'Contact Forms', 'flowbridge-for-n8n' ); ?></h2>
		<div class="flowbridge-notice flowbridge-notice-info">
			<p>
				<?php esc_html_e( 'Contact Form 7 is not installed or active. Install and activate CF7 to configure form submission webhooks.', 'flowbridge-for-n8n' ); ?>
			</p>
		</div>
	</div>
	<?php
	return;
endif;

$flowbridge_n8n_cf7_forms  = WPCF7_ContactForm::find();
$flowbridge_n8n_cf7_config = get_option( 'flowbridge_n8n_cf7_config', array() );
?>
<div class="flowbridge-card">
	<h2><?php esc_html_e( 'Contact Forms', 'flowbridge-for-n8n' ); ?></h2>
	<p class="flowbridge-description">
		<?php esc_html_e( 'Enable webhooks for Contact Form 7 submissions and configure field mappings.', 'flowbridge-for-n8n' ); ?>
	</p>

	<?php if ( empty( $flowbridge_n8n_cf7_forms ) ) : ?>
		<p class="flowbridge-empty">
			<?php esc_html_e( 'No Contact Form 7 forms found.', 'flowbridge-for-n8n' ); ?>
		</p>
	<?php else : ?>
		<?php foreach ( $flowbridge_n8n_cf7_forms as $flowbridge_n8n_form ) : ?>
			<?php
			$flowbridge_n8n_form_id    = $flowbridge_n8n_form->id();
			$flowbridge_n8n_config     = isset( $flowbridge_n8n_cf7_config[ $flowbridge_n8n_form_id ] ) ? $flowbridge_n8n_cf7_config[ $flowbridge_n8n_form_id ] : array();
			$flowbridge_n8n_is_enabled = ! empty( $flowbridge_n8n_config['enabled'] );
			$flowbridge_n8n_fields     = isset( $flowbridge_n8n_config['fields'] ) ? $flowbridge_n8n_config['fields'] : array();
			$flowbridge_n8n_has_config = ! empty( $flowbridge_n8n_fields );
			?>
			<div class="flowbridge-entity-row" data-entity-type="cf7" data-entity-key="<?php echo esc_attr( $flowbridge_n8n_form_id ); ?>">
				<div class="flowbridge-entity-header">
					<label class="flowbridge-toggle">
						<input type="checkbox"
							   class="flowbridge-entity-toggle"
							   <?php checked( $flowbridge_n8n_is_enabled ); ?>
							   data-entity-type="cf7"
							   data-entity-key="<?php echo esc_attr( $flowbridge_n8n_form_id ); ?>" />
						<span class="flowbridge-toggle-slider"></span>
					</label>
					<span class="flowbridge-entity-name"><?php echo esc_html( $flowbridge_n8n_form->title() ); ?></span>
					<code class="flowbridge-entity-slug"><?php echo esc_html( 'ID: ' . $flowbridge_n8n_form_id ); ?></code>
					<button type="button"
							class="button button-small flowbridge-configure-btn"
							data-entity-type="cf7"
							data-entity-key="<?php echo esc_attr( $flowbridge_n8n_form_id ); ?>"
							data-entity-label="<?php echo esc_attr( $flowbridge_n8n_form->title() ); ?>">
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
								/* translators: %d: number of enabled fields */
								esc_html__( '%d fields configured', 'flowbridge-for-n8n' ),
								absint( $flowbridge_n8n_enabled_count )
							);
							?>
							<span class="dashicons dashicons-arrow-down-alt2"></span>
						</button>
						<div class="flowbridge-collapsible-content" style="display: none;">
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
						</div>
					</div>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	<?php endif; ?>
</div>

<?php include FLOWBRIDGE_N8N_PLUGIN_DIR . 'admin/partials/modal-cf7-config.php'; ?>
