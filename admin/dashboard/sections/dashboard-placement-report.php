<?php
// File: admin/dashboard/sections/dashboard-placement-report.php
// CTA Placement Performance – يعرض أفضل «المناطق» (Zone) أداءً مع فلترة زمنية، تصدير CSV، وترقيم.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ──────────────────────────────
 * 1) Date filters (optional)
 * ─────────────────────────── */
$pos_from  = isset( $_GET['pos_from'] ) ? sanitize_text_field( $_GET['pos_from'] ) : '';
$pos_to    = isset( $_GET['pos_to']   ) ? sanitize_text_field( $_GET['pos_to']   ) : '';
$pos_nonce = wp_create_nonce( 'plinkly_export_csv' );

/* ──────────────────────────────
 * 2) Fetch aggregated data (by zone)
 * ─────────────────────────── */
global $wpdb;
$table_clicks = $wpdb->prefix . 'plinkly_clicks';

$where = [ "zone <> ''" ];
if ( $pos_from && $pos_to ) {
	$where[] = $wpdb->prepare( 'DATE(clicked_at) BETWEEN %s AND %s', $pos_from, $pos_to );
}
$where_sql = $where ? 'WHERE ' . implode( ' AND ', $where ) : '';

$rows = $wpdb->get_results( "
	SELECT  post_id,
	        button_text,
	        zone,
	        COUNT(*) AS clicks
	FROM    {$table_clicks}
	{$where_sql}
	GROUP BY post_id, button_text, zone
	ORDER BY clicks DESC
", ARRAY_A );

/* ──────────────────────────────
 * 3) Pagination – 10 per page
 * ─────────────────────────── */
$per_page    = 10;
$pos_paged   = isset( $_GET['pos_paged'] ) ? max( 1, (int) $_GET['pos_paged'] ) : 1;
$total_rows  = count( $rows );
$total_pages = $total_rows ? ceil( $total_rows / $per_page ) : 1;

$rows_page = array_slice( $rows, ( $pos_paged - 1 ) * $per_page, $per_page );

/* ──────────────────────────────
 * 4) Output card
 * ─────────────────────────── */
?>
<div id="placement-report-section" class="card top-clicked-buttons-card detailed-data-card">

	<!-- ─── Header (⋮ menu) ─── -->
	<div class="top-clicked-buttons-header">
		<h3>
			<?php esc_html_e( 'CTA Placement Performance', 'plinkly-smart-cta-buttons' ); ?>
			<span class="plinkly-beta-label">Beta</span>
		</h3>

		<div class="top-buttons-menu-wrapper">
			<button type="button" class="button">⋮</button>
			<div id="placementReportMenu" class="top-buttons-dropdown">
				<!-- Export CSV -->
				<a class="button export-button" href="<?php
					echo esc_url(
						admin_url(
							'admin-ajax.php?action=plinkly_export_position_csv' .
							( $pos_from ? '&pos_from=' . rawurlencode( $pos_from ) : '' ) .
							( $pos_to   ? '&pos_to='   . rawurlencode( $pos_to   ) : '' ) .
							'&nonce=' . $pos_nonce
						)
					);
				?>"><?php esc_html_e( 'Export as CSV', 'plinkly-smart-cta-buttons' ); ?></a>

				<!-- Reset filters -->
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=plinkly-cta-dashboard' ) ); ?>#placement-report-section" class="button">
					<?php esc_html_e( 'Reset Filters', 'plinkly-smart-cta-buttons' ); ?>
				</a>
			</div>
		</div>
	</div><!-- /header -->

	<!-- ─── Date filters ─── -->
	<div class="detailed-date-filter">
		<div class="detailed-date-filter-controls">
			<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>#placement-report-section" class="detailed-date-form">
				<input type="hidden" name="page" value="plinkly-cta-dashboard" />
				<label class="date-label">
					<span class="label-text"><?php esc_html_e( 'From:', 'plinkly-smart-cta-buttons' ); ?></span>
					<input type="date" name="pos_from" value="<?php echo esc_attr( $pos_from ); ?>" />
				</label>
				<label class="date-label">
					<span class="label-text"><?php esc_html_e( 'To:', 'plinkly-smart-cta-buttons' ); ?></span>
					<input type="date" name="pos_to" value="<?php echo esc_attr( $pos_to ?: gmdate( 'Y-m-d' ) ); ?>" />
				</label>
				<input type="submit" class="button" value="<?php esc_attr_e( 'Filter', 'plinkly-smart-cta-buttons' ); ?>" />
			</form>
		</div>
	</div><!-- /filters -->

	<!-- ─── Table or “No data” ─── -->
	<?php if ( $rows_page ) : ?>
	<table class="widefat fixed striped sortable">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Post',   'plinkly-smart-cta-buttons' ); ?></th>
				<th><?php esc_html_e( 'Button', 'plinkly-smart-cta-buttons' ); ?></th>
				<th><?php esc_html_e( 'Zone',   'plinkly-smart-cta-buttons' ); ?></th>
				<th><?php esc_html_e( 'Clicks', 'plinkly-smart-cta-buttons' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $rows_page as $r ) : ?>
			<tr>
				<td>
					<a href="<?php echo esc_url( get_edit_post_link( $r['post_id'] ) ); ?>">
						<?php echo esc_html( get_the_title( $r['post_id'] ) ?: '#' ); ?>
					</a>
				</td>
				<td><?php echo esc_html( $r['button_text'] ); ?></td>
				<td><strong><?php echo esc_html( $r['zone'] ); ?></strong></td>
				<td><?php echo esc_html( (int) $r['clicks'] ); ?></td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<!-- ─── Pagination ─── -->
	<?php if ( $total_pages > 1 ) : ?>
	<div class="tab-pagination">
		<?php
		$range = 2;
		$start = max( 1, $pos_paged - $range );
		$end   = min( $total_pages, $pos_paged + $range );

		if ( $pos_paged > 1 ) {
			echo '<a class="button" href="' . esc_url( add_query_arg( [ 'pos_paged' => 1 ] ) ) . '#placement-report-section">&laquo; ' . esc_html__( 'First', 'plinkly-smart-cta-buttons' ) . '</a> ';
		}

		if ( $start > 1 ) {
			echo '<a class="button" href="' . esc_url( add_query_arg( [ 'pos_paged' => 1 ] ) ) . '#placement-report-section">1</a> ';
			if ( $start > 2 ) echo '<span class="dots">…</span> ';
		}

		for ( $i = $start; $i <= $end; $i++ ) {
			if ( $i === $pos_paged ) {
				echo '<span class="current">' . esc_html( $i ) . '</span> ';
			} else {
				echo '<a class="button" href="' . esc_url( add_query_arg( [ 'pos_paged' => $i ] ) ) . '#placement-report-section">' . esc_html( $i ) . '</a> ';
			}
		}

		if ( $end < $total_pages ) {
			if ( $end < $total_pages - 1 ) echo '<span class="dots">…</span> ';
			echo '<a class="button" href="' . esc_url( add_query_arg( [ 'pos_paged' => $total_pages ] ) ) . '#placement-report-section">' . esc_html( $total_pages ) . '</a> ';
		}

		if ( $pos_paged < $total_pages ) {
			echo '<a class="button" href="' . esc_url( add_query_arg( [ 'pos_paged' => $total_pages ] ) ) . '#placement-report-section">' . esc_html__( 'Last', 'plinkly-smart-cta-buttons' ) . ' &raquo;</a>';
		}
		?>
	</div>
	

	<?php endif; ?>

	<?php else : ?>
	<p><?php esc_html_e( 'No placement data recorded yet.', 'plinkly-smart-cta-buttons' ); ?></p>
	<?php endif; ?>

</div><!-- /card -->

