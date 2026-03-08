<?php
/**
 * Webhook settings tab.
 *
 * @since 1.0.0
 * @package FlowBridge_N8N
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$flowbridge_n8n_settings = FlowBridge_N8N_Settings::get_settings();
?>
<div class="flowbridge-card">
	<h2><?php esc_html_e( 'Webhook Configuration', 'flowbridge-for-n8n' ); ?></h2>
	<p class="flowbridge-description">
		<?php esc_html_e( 'Configure the n8n webhook URL and global settings for FlowBridge.', 'flowbridge-for-n8n' ); ?>
	</p>

	<form method="post" action="">
		<?php wp_nonce_field( 'flowbridge_n8n_save_settings' ); ?>
		<input type="hidden" name="flowbridge_n8n_save_settings" value="1" />

		<table class="form-table flowbridge-form-table">
			<tr>
				<th scope="row">
					<label for="flowbridge-global-enabled">
						<?php esc_html_e( 'Enable Webhooks', 'flowbridge-for-n8n' ); ?>
					</label>
				</th>
				<td>
					<label class="flowbridge-toggle">
						<input type="checkbox"
							   id="flowbridge-global-enabled"
							   name="global_enabled"
							   value="1"
							   <?php checked( $flowbridge_n8n_settings['global_enabled'] ); ?> />
						<span class="flowbridge-toggle-slider"></span>
					</label>
					<p class="description">
						<?php esc_html_e( 'Master switch — when disabled, no webhooks will be sent.', 'flowbridge-for-n8n' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="flowbridge-webhook-url">
						<?php esc_html_e( 'Webhook URL', 'flowbridge-for-n8n' ); ?>
					</label>
				</th>
				<td>
					<input type="url"
						   id="flowbridge-webhook-url"
						   name="webhook_url"
						   value="<?php echo esc_attr( $flowbridge_n8n_settings['webhook_url'] ); ?>"
						   class="regular-text"
						   placeholder="https://your-n8n-instance.com/webhook/..." />
					<p class="description">
						<?php esc_html_e( 'The n8n webhook URL to receive events.', 'flowbridge-for-n8n' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="flowbridge-test-webhook-url">
						<?php esc_html_e( 'Test Webhook URL', 'flowbridge-for-n8n' ); ?>
					</label>
				</th>
				<td>
					<input type="url"
						   id="flowbridge-test-webhook-url"
						   name="test_webhook_url"
						   value="<?php echo esc_attr( $flowbridge_n8n_settings['test_webhook_url'] ); ?>"
						   class="regular-text"
						   placeholder="https://your-n8n-instance.com/webhook-test/..." />
					<p class="description">
						<?php esc_html_e( 'Optional n8n test webhook URL. Used by "Send Test Event" buttons in entity configuration modals.', 'flowbridge-for-n8n' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="flowbridge-secret-key">
						<?php esc_html_e( 'Secret Key', 'flowbridge-for-n8n' ); ?>
					</label>
				</th>
				<td>
					<input type="text"
						   id="flowbridge-secret-key"
						   name="secret_key"
						   value="<?php echo esc_attr( $flowbridge_n8n_settings['secret_key'] ); ?>"
						   class="regular-text"
						   placeholder="<?php esc_attr_e( 'Optional — sent as X-FlowBridge-Secret header', 'flowbridge-for-n8n' ); ?>" />
					<p class="description">
						<?php esc_html_e( 'Optional secret for webhook authentication. Sent as X-FlowBridge-Secret header.', 'flowbridge-for-n8n' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="flowbridge-timeout">
						<?php esc_html_e( 'Timeout (seconds)', 'flowbridge-for-n8n' ); ?>
					</label>
				</th>
				<td>
					<input type="number"
						   id="flowbridge-timeout"
						   name="timeout"
						   value="<?php echo esc_attr( $flowbridge_n8n_settings['timeout'] ); ?>"
						   class="small-text"
						   min="1"
						   max="120" />
					<p class="description">
						<?php esc_html_e( 'HTTP request timeout in seconds (1-120).', 'flowbridge-for-n8n' ); ?>
					</p>
				</td>
			</tr>
		</table>

		<p class="submit">
			<?php submit_button( __( 'Save Settings', 'flowbridge-for-n8n' ), 'primary', 'submit', false ); ?>
			<button type="button" id="flowbridge-test-webhook" class="button button-secondary" style="margin-left: 8px;">
				<?php esc_html_e( 'Test Webhook', 'flowbridge-for-n8n' ); ?>
			</button>
			<span id="flowbridge-test-result" class="flowbridge-inline-notice" style="display: none;"></span>
		</p>
	</form>
</div>
