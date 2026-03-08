<?php
/**
 * Main admin page wrapper with vertical tabs navigation.
 *
 * @since 1.0.0
 * @package FlowBridge_N8N
 *
 * @var string $flowbridge_n8n_current_tab The active tab slug.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap flowbridge-wrap">
	<h1><?php esc_html_e( 'FlowBridge Settings', 'flowbridge-for-n8n' ); ?></h1>
	<p class="flowbridge-description"><?php esc_html_e( 'Connect your WordPress site with n8n workflow automation.', 'flowbridge-for-n8n' ); ?></p>

	<div class="flowbridge-layout">
		<nav class="flowbridge-tabs-nav">
			<?php foreach ( $this->get_tabs() as $flowbridge_n8n_slug => $flowbridge_n8n_label ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=flowbridge-for-n8n&tab=' . $flowbridge_n8n_slug ) ); ?>"
				   class="flowbridge-tab-link <?php echo ( $flowbridge_n8n_current_tab === $flowbridge_n8n_slug ) ? 'active' : ''; ?>">
					<?php echo esc_html( $flowbridge_n8n_label ); ?>
				</a>
			<?php endforeach; ?>
		</nav>

		<div class="flowbridge-tab-content">
			<?php
			$flowbridge_n8n_tab_file = FLOWBRIDGE_N8N_PLUGIN_DIR . 'admin/partials/tab-' . $flowbridge_n8n_current_tab . '.php';
			if ( file_exists( $flowbridge_n8n_tab_file ) ) {
				include $flowbridge_n8n_tab_file;
			}
			?>
		</div>
	</div>
</div>
