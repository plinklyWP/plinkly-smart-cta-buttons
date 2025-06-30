<?php
// EasyLink Performance – aggregated clicks per keyword/link.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* 1) Date filters */
$el_from  = isset( $_GET['el_from'] ) ? sanitize_text_field( $_GET['el_from'] ) : '';
$el_to    = isset( $_GET['el_to']   ) ? sanitize_text_field( $_GET['el_to']   ) : '';
$el_nonce = wp_create_nonce( 'plinkly_export_csv' );

/* 2) Data */
global $wpdb;
$table = $wpdb->prefix . 'plinkly_easylink_clicks';

$where = [];
if ( $el_from && $el_to ) {
	$where[] = $wpdb->prepare( 'DATE(clicked_at) BETWEEN %s AND %s', $el_from, $el_to );
}
$where_sql = $where ? 'WHERE ' . implode( ' AND ', $where ) : '';

$rows = $wpdb->get_results( "
	SELECT  keyword,
	        link,
	        COUNT(*) AS clicks
	FROM    {$table}
	{$where_sql}
	GROUP BY keyword, link
	ORDER BY clicks DESC
", ARRAY_A );

/* 3) Pagination */
$per_page    = 10;
$el_paged    = isset( $_GET['el_paged'] ) ? max( 1, (int) $_GET['el_paged'] ) : 1;
$total_rows  = count( $rows );
$total_pages = $total_rows ? ceil( $total_rows / $per_page ) : 1;

$rows_page = array_slice( $rows, ( $el_paged - 1 ) * $per_page, $per_page );

/* 4) Output */
?>
<div id="easylink-report-section" class="card top-clicked-buttons-card detailed-data-card">

	<div class="top-clicked-buttons-header">
		<h3><?php esc_html_e( 'EasyLink Performance', 'plinkly-smart-cta-buttons' ); ?></h3>
		<div class="top-buttons-menu-wrapper">
			<button type="button" class="button">⋮</button>
			<div class="top-buttons-dropdown">
				<a class="button export-button" href="<?php
					echo esc_url(
						admin_url(
							'admin-ajax.php?action=plinkly_export_easylink_csv' .
							( $el_from ? '&el_from=' . rawurlencode( $el_from ) : '' ) .
							( $el_to   ? '&el_to='   . rawurlencode( $el_to   ) : '' ) .
							'&nonce=' . $el_nonce
						)
					);
				?>"><?php esc_html_e( 'Export as CSV', 'plinkly-smart-cta-buttons' ); ?></a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=plinkly-cta-dashboard' ) ); ?>#easylink-report-section" class="button">
					<?php esc_html_e( 'Reset Filters', 'plinkly-smart-cta-buttons' ); ?>
				</a>
			</div>
		</div>
	</div>

	<div class="detailed-date-filter">
		<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>#easylink-report-section" class="detailed-date-form">
			<input type="hidden" name="page" value="plinkly-cta-dashboard" />
			<label class="date-label">
				<span><?php esc_html_e( 'From:', 'plinkly-smart-cta-buttons' ); ?></span>
				<input type="date" name="el_from" value="<?php echo esc_attr( $el_from ); ?>" />
			</label>
			<label class="date-label">
				<span><?php esc_html_e( 'To:', 'plinkly-smart-cta-buttons' ); ?></span>
				<input type="date" name="el_to" value="<?php echo esc_attr( $el_to ?: gmdate( 'Y-m-d' ) ); ?>" />
			</label>
			<input type="submit" class="button" value="<?php esc_attr_e( 'Filter', 'plinkly-smart-cta-buttons' ); ?>" />
		</form>
	</div>

	<?php if ( $rows_page ) : ?>
	<table class="widefat fixed striped sortable">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Keyword', 'plinkly-smart-cta-buttons' ); ?></th>
				<th><?php esc_html_e( 'Link',    'plinkly-smart-cta-buttons' ); ?></th>
				<th><?php esc_html_e( 'Clicks',  'plinkly-smart-cta-buttons' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $rows_page as $r ) : ?>
			<tr>
				<td><?php echo esc_html( $r['keyword'] ); ?></td>
				<td><a href="<?php echo esc_url( $r['link'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $r['link'] ); ?></a></td>
				<td><?php echo esc_html( (int) $r['clicks'] ); ?></td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<?php if ( $total_pages > 1 ) : ?>
	<div class="tab-pagination">
		<?php
		for ( $i = 1; $i <= $total_pages; $i++ ) {
			echo ( $i === $el_paged )
				? '<span class="current">' . esc_html( $i ) . '</span> '
				: '<a class="button" href="' . esc_url( add_query_arg( [ 'el_paged' => $i ] ) ) . '#easylink-report-section">' . esc_html( $i ) . '</a> ';
		}
		?>
	</div>
	<?php endif; ?>

	<?php else : ?>
	<p><?php esc_html_e( 'No EasyLink clicks recorded yet.', 'plinkly-smart-cta-buttons' ); ?></p>
	<?php endif; ?>

</div>
