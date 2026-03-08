<?php
/**
 * Logs page — displays paginated webhook event logs.
 *
 * @since 1.2.0
 * @package FlowBridge_N8N
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only filters, no data modification.
$flowbridge_n8n_log_status = isset( $_GET['log_status'] ) ? sanitize_key( $_GET['log_status'] ) : '';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$flowbridge_n8n_log_event  = isset( $_GET['log_event'] ) ? sanitize_text_field( wp_unslash( $_GET['log_event'] ) ) : '';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$flowbridge_n8n_log_search = isset( $_GET['log_search'] ) ? sanitize_text_field( wp_unslash( $_GET['log_search'] ) ) : '';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$flowbridge_n8n_log_paged  = isset( $_GET['log_paged'] ) ? absint( $_GET['log_paged'] ) : 1;

if ( $flowbridge_n8n_log_paged < 1 ) {
	$flowbridge_n8n_log_paged = 1;
}

$per_page = 20;

$flowbridge_n8n_result = FlowBridge_N8N_Logger::get_logs( array(
	'per_page' => $per_page,
	'page'     => $flowbridge_n8n_log_paged,
	'status'   => $flowbridge_n8n_log_status,
	'event'    => $flowbridge_n8n_log_event,
	'search'   => $flowbridge_n8n_log_search,
) );

$flowbridge_n8n_items       = $flowbridge_n8n_result['items'];
$flowbridge_n8n_total       = $flowbridge_n8n_result['total'];
$flowbridge_n8n_total_pages = ceil( $flowbridge_n8n_total / $per_page );

$flowbridge_n8n_base_url = admin_url( 'admin.php?page=flowbridge-n8n-logs' );
?>

<div class="wrap flowbridge-wrap">
	<h1><?php esc_html_e( 'Webhook Event Logs', 'flowbridge-for-n8n' ); ?></h1>
	<p class="flowbridge-description"><?php esc_html_e( 'View all dispatched webhook events, their status, and response details.', 'flowbridge-for-n8n' ); ?></p>

<div class="flowbridge-card" style="border-radius: var(--fb-radius-lg);">

	<!-- Filter Bar -->
	<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="flowbridge-logs-filter-bar">
		<input type="hidden" name="page" value="flowbridge-n8n-logs" />

		<select name="log_status" class="flowbridge-logs-filter-select">
			<option value=""><?php esc_html_e( 'All Statuses', 'flowbridge-for-n8n' ); ?></option>
			<option value="success" <?php selected( $flowbridge_n8n_log_status, 'success' ); ?>><?php esc_html_e( 'Success', 'flowbridge-for-n8n' ); ?></option>
			<option value="failed" <?php selected( $flowbridge_n8n_log_status, 'failed' ); ?>><?php esc_html_e( 'Failed', 'flowbridge-for-n8n' ); ?></option>
			<option value="filtered" <?php selected( $flowbridge_n8n_log_status, 'filtered' ); ?>><?php esc_html_e( 'Filtered', 'flowbridge-for-n8n' ); ?></option>
		</select>

		<input type="text" name="log_event" value="<?php echo esc_attr( $flowbridge_n8n_log_event ); ?>" placeholder="<?php esc_attr_e( 'Event type...', 'flowbridge-for-n8n' ); ?>" class="flowbridge-logs-filter-input" />

		<input type="text" name="log_search" value="<?php echo esc_attr( $flowbridge_n8n_log_search ); ?>" placeholder="<?php esc_attr_e( 'Search...', 'flowbridge-for-n8n' ); ?>" class="flowbridge-logs-filter-input" />

		<button type="submit" class="button button-secondary"><?php esc_html_e( 'Filter', 'flowbridge-for-n8n' ); ?></button>

		<button type="button" id="flowbridge-clear-logs-btn" class="button flowbridge-logs-clear-btn"><?php esc_html_e( 'Clear All Logs', 'flowbridge-for-n8n' ); ?></button>
	</form>

	<!-- Logs Table -->
	<div class="flowbridge-logs-table-wrap">
		<table class="flowbridge-logs-table">
			<thead>
				<tr>
					<th class="flowbridge-logs-col-status"><?php esc_html_e( 'Status', 'flowbridge-for-n8n' ); ?></th>
					<th><?php esc_html_e( 'Event', 'flowbridge-for-n8n' ); ?></th>
					<th><?php esc_html_e( 'Entity', 'flowbridge-for-n8n' ); ?></th>
					<th><?php esc_html_e( 'HTTP', 'flowbridge-for-n8n' ); ?></th>
					<th><?php esc_html_e( 'Duration', 'flowbridge-for-n8n' ); ?></th>
					<th><?php esc_html_e( 'Date', 'flowbridge-for-n8n' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'flowbridge-for-n8n' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $flowbridge_n8n_items ) ) : ?>
					<tr>
						<td colspan="7" class="flowbridge-logs-empty">
							<?php esc_html_e( 'No log entries found.', 'flowbridge-for-n8n' ); ?>
						</td>
					</tr>
				<?php else : ?>
					<?php foreach ( $flowbridge_n8n_items as $flowbridge_n8n_log ) : ?>
						<tr>
							<td class="flowbridge-logs-col-status">
								<?php
								$flowbridge_n8n_status_class = 'flowbridge-log-badge--' . esc_attr( $flowbridge_n8n_log->status );
								$flowbridge_n8n_status_label = ucfirst( $flowbridge_n8n_log->status );
								?>
								<span class="flowbridge-log-badge <?php echo esc_attr( $flowbridge_n8n_status_class ); ?>">
									<?php echo esc_html( $flowbridge_n8n_status_label ); ?>
								</span>
							</td>
							<td>
								<code class="flowbridge-log-event"><?php echo esc_html( $flowbridge_n8n_log->event ); ?></code>
							</td>
							<td>
								<?php
								$flowbridge_n8n_entity_display = esc_html( $flowbridge_n8n_log->entity_type );
								if ( ! empty( $flowbridge_n8n_log->entity_key ) ) {
									$flowbridge_n8n_entity_display .= ' <span class="flowbridge-log-entity-key">' . esc_html( $flowbridge_n8n_log->entity_key ) . '</span>';
								}
								if ( ! empty( $flowbridge_n8n_log->entity_id ) ) {
									$flowbridge_n8n_entity_display .= ' <span class="flowbridge-log-entity-id">#' . esc_html( $flowbridge_n8n_log->entity_id ) . '</span>';
								}
								echo wp_kses(
									$flowbridge_n8n_entity_display,
									array( 'span' => array( 'class' => array() ) )
								);
								?>
							</td>
							<td>
								<?php if ( null !== $flowbridge_n8n_log->http_code && '' !== $flowbridge_n8n_log->http_code ) : ?>
									<?php
									$flowbridge_n8n_code_class = 'flowbridge-http-code';
									$flowbridge_n8n_http_code  = absint( $flowbridge_n8n_log->http_code );
									if ( $flowbridge_n8n_http_code >= 200 && $flowbridge_n8n_http_code < 300 ) {
										$flowbridge_n8n_code_class .= ' flowbridge-http-code--success';
									} elseif ( $flowbridge_n8n_http_code >= 400 ) {
										$flowbridge_n8n_code_class .= ' flowbridge-http-code--error';
									}
									?>
									<span class="<?php echo esc_attr( $flowbridge_n8n_code_class ); ?>"><?php echo esc_html( $flowbridge_n8n_http_code ); ?></span>
								<?php else : ?>
									<span class="flowbridge-log-na">&mdash;</span>
								<?php endif; ?>
							</td>
							<td>
								<?php if ( null !== $flowbridge_n8n_log->duration_ms && '' !== $flowbridge_n8n_log->duration_ms ) : ?>
									<?php echo esc_html( $flowbridge_n8n_log->duration_ms ); ?>ms
								<?php else : ?>
									<span class="flowbridge-log-na">&mdash;</span>
								<?php endif; ?>
							</td>
							<td class="flowbridge-log-date">
								<?php echo esc_html( $flowbridge_n8n_log->created_at ); ?>
							</td>
							<td>
								<button type="button" class="button button-small flowbridge-view-payload-btn"
									data-payload="<?php echo esc_attr( $flowbridge_n8n_log->payload ? $flowbridge_n8n_log->payload : '{}' ); ?>"
									data-event="<?php echo esc_attr( $flowbridge_n8n_log->event ); ?>"
									data-response="<?php echo esc_attr( $flowbridge_n8n_log->response_message ? $flowbridge_n8n_log->response_message : '' ); ?>">
									<?php esc_html_e( 'View', 'flowbridge-for-n8n' ); ?>
								</button>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</div>

	<!-- Pagination -->
	<?php if ( $flowbridge_n8n_total_pages > 1 ) : ?>
		<div class="flowbridge-logs-pagination">
			<?php
			$flowbridge_n8n_pagination_args = array();
			if ( ! empty( $flowbridge_n8n_log_status ) ) {
				$flowbridge_n8n_pagination_args['log_status'] = $flowbridge_n8n_log_status;
			}
			if ( ! empty( $flowbridge_n8n_log_event ) ) {
				$flowbridge_n8n_pagination_args['log_event'] = $flowbridge_n8n_log_event;
			}
			if ( ! empty( $flowbridge_n8n_log_search ) ) {
				$flowbridge_n8n_pagination_args['log_search'] = $flowbridge_n8n_log_search;
			}
			?>

			<span class="flowbridge-logs-pagination-info">
				<?php
				printf(
					/* translators: 1: current page, 2: total pages, 3: total items */
					esc_html__( 'Page %1$d of %2$d (%3$d total)', 'flowbridge-for-n8n' ),
					absint( $flowbridge_n8n_log_paged ),
					absint( $flowbridge_n8n_total_pages ),
					absint( $flowbridge_n8n_total )
				);
				?>
			</span>

			<span class="flowbridge-logs-pagination-links">
				<?php if ( $flowbridge_n8n_log_paged > 1 ) : ?>
					<a href="<?php echo esc_url( add_query_arg( array_merge( $flowbridge_n8n_pagination_args, array( 'log_paged' => $flowbridge_n8n_log_paged - 1 ) ), $flowbridge_n8n_base_url ) ); ?>" class="button button-small">&laquo; <?php esc_html_e( 'Previous', 'flowbridge-for-n8n' ); ?></a>
				<?php endif; ?>

				<?php if ( $flowbridge_n8n_log_paged < $flowbridge_n8n_total_pages ) : ?>
					<a href="<?php echo esc_url( add_query_arg( array_merge( $flowbridge_n8n_pagination_args, array( 'log_paged' => $flowbridge_n8n_log_paged + 1 ) ), $flowbridge_n8n_base_url ) ); ?>" class="button button-small"><?php esc_html_e( 'Next', 'flowbridge-for-n8n' ); ?> &raquo;</a>
				<?php endif; ?>
			</span>
		</div>
	<?php endif; ?>
</div><!-- .flowbridge-card -->

<!-- Payload Modal -->
<div id="flowbridge-modal-payload" class="flowbridge-modal" style="display:none;">
	<div class="flowbridge-modal-overlay"></div>
	<div class="flowbridge-modal-card" style="max-width: 700px;">
		<div class="flowbridge-modal-header">
			<h3><?php esc_html_e( 'Log Details', 'flowbridge-for-n8n' ); ?> — <span class="flowbridge-modal-entity-label"></span></h3>
			<button type="button" class="flowbridge-modal-close">&times;</button>
		</div>
		<div class="flowbridge-modal-body">
			<div class="flowbridge-modal-section">
				<h4><?php esc_html_e( 'Payload', 'flowbridge-for-n8n' ); ?></h4>
				<pre class="flowbridge-payload-json"></pre>
			</div>
			<div class="flowbridge-modal-section flowbridge-response-section" style="display:none;">
				<h4><?php esc_html_e( 'Response', 'flowbridge-for-n8n' ); ?></h4>
				<p class="flowbridge-response-message"></p>
			</div>
		</div>
		<div class="flowbridge-modal-footer">
			<button type="button" class="button flowbridge-modal-cancel"><?php esc_html_e( 'Close', 'flowbridge-for-n8n' ); ?></button>
		</div>
	</div>
</div>
</div><!-- .wrap -->
