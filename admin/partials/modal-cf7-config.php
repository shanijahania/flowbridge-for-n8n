<?php
/**
 * Modal: CF7 field configuration.
 *
 * @since 1.0.0
 * @package FlowBridge_N8N
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div id="flowbridge-modal-cf7" class="flowbridge-modal" style="display: none;">
	<div class="flowbridge-modal-overlay"></div>
	<div class="flowbridge-modal-card">
		<div class="flowbridge-modal-header">
			<h3>
				<?php esc_html_e( 'Configure', 'flowbridge-for-n8n' ); ?>
				<span class="flowbridge-modal-entity-label"></span>
			</h3>
			<button type="button" class="flowbridge-modal-close">&times;</button>
		</div>
		<div class="flowbridge-modal-body">
			<div class="flowbridge-modal-section">
				<h4><?php esc_html_e( 'Fields', 'flowbridge-for-n8n' ); ?></h4>
				<p class="description">
					<?php esc_html_e( 'Fields are auto-detected from the form. Click "Load Fields" to refresh.', 'flowbridge-for-n8n' ); ?>
				</p>
				<button type="button" class="button button-small flowbridge-load-cf7-fields-btn">
					<?php esc_html_e( 'Load Fields', 'flowbridge-for-n8n' ); ?>
				</button>
				<div class="flowbridge-fields-table-wrap">
					<table class="flowbridge-fields-table">
						<thead>
							<tr>
								<th class="flowbridge-col-check"><input type="checkbox" class="flowbridge-check-all" /></th>
								<th><?php esc_html_e( 'Source Field', 'flowbridge-for-n8n' ); ?></th>
								<th><?php esc_html_e( 'Sample Value', 'flowbridge-for-n8n' ); ?></th>
								<th><?php esc_html_e( 'Send As', 'flowbridge-for-n8n' ); ?></th>
								<th><?php esc_html_e( 'Type', 'flowbridge-for-n8n' ); ?></th>
								<th><?php esc_html_e( 'Field Type', 'flowbridge-for-n8n' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<tr class="flowbridge-fields-empty">
								<td colspan="6"><?php esc_html_e( 'Click "Load Fields" to detect form fields.', 'flowbridge-for-n8n' ); ?></td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
			<div class="flowbridge-modal-section flowbridge-preview-section" style="display: none;">
				<h4><?php esc_html_e( 'JSON Output Preview', 'flowbridge-for-n8n' ); ?></h4>
				<pre class="flowbridge-preview-json"></pre>
			</div>
		</div>
		<div class="flowbridge-modal-footer">
			<button type="button" class="button button-primary flowbridge-save-config-btn">
				<?php esc_html_e( 'Save Configuration', 'flowbridge-for-n8n' ); ?>
			</button>
			<button type="button" class="button flowbridge-preview-output-btn">
				<?php esc_html_e( 'Preview Output', 'flowbridge-for-n8n' ); ?>
			</button>
			<button type="button" class="button flowbridge-send-test-event-btn">
				<?php esc_html_e( 'Send Test Event', 'flowbridge-for-n8n' ); ?>
			</button>
			<button type="button" class="button flowbridge-modal-cancel">
				<?php esc_html_e( 'Cancel', 'flowbridge-for-n8n' ); ?>
			</button>
			<span class="flowbridge-modal-status"></span>
		</div>
	</div>
</div>
