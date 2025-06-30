<?php
// File: admin/dashboard/sections/dashboard-ab-summary.php
// A/B-Test Summary – يجمع نقرات وانطباعات الزر لكل Variant مع دعم البيانات القديمة (بلا Variant).
// يُخفي الأزرار التي لا تملك أي بيانات للنسخة B (أي لم يُفعَّل فيها A/B).

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/* ──────────────────────────────
 * 1) Date filters (optional)
 * ─────────────────────────── */
$ab_from  = isset( $_GET['ab_from'] ) ? sanitize_text_field( $_GET['ab_from'] ) : '';
$ab_to    = isset( $_GET['ab_to']   ) ? sanitize_text_field( $_GET['ab_to']   ) : '';
$ab_nonce = wp_create_nonce( 'plinkly_export_csv' );

/* ──────────────────────────────
 * 2) Fetch aggregated data
 *      - COALESCE(variant,'A') يجعل السجلات القديمة تُحسب كنسخة A.
 * ─────────────────────────── */
global $wpdb;
$table_clicks      = $wpdb->prefix . 'plinkly_clicks';
$table_impressions = $wpdb->prefix . 'plinkly_impressions';

/* 2-a. WHERE clause for clicks */
$where_clicks = [];
if ( $ab_from && $ab_to ) {
	$where_clicks[] = $wpdb->prepare(
		'DATE(clicked_at) BETWEEN %s AND %s',
		$ab_from,
		$ab_to
	);
}
$where_sql_clicks = $where_clicks ? 'WHERE ' . implode( ' AND ', $where_clicks ) : '';

/* 2-b. Fetch clicks grouped by post + text + link
 *      – نحسب A/B بنموذج COALESCE لضمان شمول السجلات القديمة
 */
$rows = $wpdb->get_results( "
	SELECT  post_id,
	        button_text,
	        button_link,
	        SUM( COALESCE(variant,'A') = 'A' ) AS a_clicks,
	        SUM( COALESCE(variant,'A') = 'B' ) AS b_clicks
	FROM    {$table_clicks}
	{$where_sql_clicks}
	GROUP BY post_id, button_text, button_link
	ORDER BY post_id DESC
", ARRAY_A );

/* 2-c. Calculate impressions, CTR, and build summary
 *      – نستبعد الأزرار التي لا تحتوي أي Impression للنسخة B.
 */
$ab_summary = [];
foreach ( $rows as $r ) {
	$link_sql = $wpdb->prepare( 'button_link = %s', $r['button_link'] );

	// WHERE clause المشتركة للنسختين
	$where_impr = [];
	if ( $ab_from && $ab_to ) {
		$where_impr[] = $wpdb->prepare(
			'DATE(showed_at) BETWEEN %s AND %s',
			$ab_from,
			$ab_to
		);
	}
	$where_impr[] = $link_sql; // link match

	$where_impr_a = array_merge( $where_impr, [ "(variant = 'A' OR variant IS NULL OR variant = '')" ] );
	$where_impr_b = array_merge( $where_impr, [ "variant = 'B'" ] );

	$cond_a = implode( ' AND ', $where_impr_a );
	$cond_b = implode( ' AND ', $where_impr_b );

	$a_impr = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_impressions} WHERE {$cond_a}" );
	$b_impr = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_impressions} WHERE {$cond_b}" );

	/* ── MOD: تجاهل الزر إذا لم يُسجَّل له أي Impression للنسخة B ── */
	if ( $b_impr === 0 ) {
		continue; // لم يفعَّل A/B لهذا الزر
	}

	$a_ctr  = $a_impr ? round( ( $r['a_clicks'] / $a_impr ) * 100, 2 ) : 0;
	$b_ctr  = $b_impr ? round( ( $r['b_clicks'] / $b_impr ) * 100, 2 ) : 0;

	$ab_summary[] = (object) [
		'post_id'  => $r['post_id'],
		'text'     => $r['button_text'],
		'link'     => $r['button_link'],
		'a_clicks' => $r['a_clicks'],
		'b_clicks' => $r['b_clicks'],
		'a_ctr'    => $a_ctr,
		'b_ctr'    => $b_ctr,
		'winner'   => ( $a_ctr == $b_ctr ? '–' : ( $a_ctr > $b_ctr ? 'A' : 'B' ) ),
	];
}

/* ──────────────────────────────
 * 3) Pagination – 10 rows per page
 * ─────────────────────────── */
$per_page    = 10;
$ab_paged    = isset( $_GET['ab_paged'] ) ? max( 1, (int) $_GET['ab_paged'] ) : 1;
$total_rows  = count( $ab_summary );
$total_pages = ( $total_rows > 0 ) ? ceil( $total_rows / $per_page ) : 1;

/* Slice the array for the current page */
$ab_summary_page = array_slice( $ab_summary, ( $ab_paged - 1 ) * $per_page, $per_page );

/* ──────────────────────────────
 * 4) Output table
 * ─────────────────────────── */
/* Hide the whole card if A/B-Testing is disabled */
if ( ! get_option( 'plinkly_enable_ab_test', 0 ) ) {
	return; // stop loading this template
}
?>
<div id="ab-summary-section" class="card top-clicked-buttons-card detailed-data-card">

	<!-- ─── Header (with ⋮ menu button) ─── -->
	<div class="top-clicked-buttons-header">
		<h3>
			<?php esc_html_e( 'A/B-Test Summary', 'plinkly-smart-cta-buttons' ); ?>
			<span class="plinkly-beta-label">Beta</span>
		</h3>

		<div class="top-buttons-menu-wrapper">
			<button type="button" class="button">⋮</button>
			<div id="abSummaryMenu" class="top-buttons-dropdown">
				<!-- Export CSV -->
				<a class="button export-button" href="<?php
					echo esc_url(
						admin_url(
							'admin-ajax.php?action=plinkly_export_ab_summary_csv' .
							( $ab_from ? '&ab_from=' . rawurlencode( $ab_from ) : '' ) .
							( $ab_to   ? '&ab_to='   . rawurlencode( $ab_to   ) : '' ) .
							'&nonce=' . $ab_nonce
						)
					);
				?>">
					<?php esc_html_e( 'Export as CSV', 'plinkly-smart-cta-buttons' ); ?>
				</a>
				<!-- Reset -->
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=plinkly-cta-dashboard' ) ); ?>#ab-summary-section" class="button">
					<?php esc_html_e( 'Reset Filters', 'plinkly-smart-cta-buttons' ); ?>
				</a>
			</div>
		</div>
	</div><!-- /header -->

	<!-- ─── Filters (date) ─── -->
	<div class="detailed-date-filter">
		<div class="detailed-date-filter-controls">
			<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>#ab-summary-section" class="detailed-date-form">
				<input type="hidden" name="page" value="plinkly-cta-dashboard" />
				<label class="date-label">
					<span class="label-text"><?php esc_html_e( 'From:', 'plinkly-smart-cta-buttons' ); ?></span>
					<input type="date" name="ab_from" value="<?php echo esc_attr( $ab_from ); ?>" />
				</label>
				<label class="date-label">
					<span class="label-text"><?php esc_html_e( 'To:', 'plinkly-smart-cta-buttons' ); ?></span>
					<input type="date" name="ab_to" value="<?php echo esc_attr( $ab_to ?: gmdate( 'Y-m-d' ) ); ?>" />
				</label>
				<input type="submit" class="button" value="<?php esc_attr_e( 'Filter', 'plinkly-smart-cta-buttons' ); ?>" />
			</form>
		</div>
	</div><!-- /filters -->

	<!-- ─── Table أو رسالة “لا توجد بيانات” ─── -->
	<?php if ( ! empty( $ab_summary_page ) ) : ?>
	<table class="widefat fixed striped sortable">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Post',        'plinkly-smart-cta-buttons' ); ?></th>
				<th><?php esc_html_e( 'Button Text', 'plinkly-smart-cta-buttons' ); ?></th>
				<th><?php esc_html_e( 'A Clicks',    'plinkly-smart-cta-buttons' ); ?></th>
				<th><?php esc_html_e( 'A CTR %',     'plinkly-smart-cta-buttons' ); ?></th>
				<th><?php esc_html_e( 'B Clicks',    'plinkly-smart-cta-buttons' ); ?></th>
				<th><?php esc_html_e( 'B CTR %',     'plinkly-smart-cta-buttons' ); ?></th>
				<th><?php esc_html_e( 'Winner',      'plinkly-smart-cta-buttons' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $ab_summary_page as $row ) : ?>
			<tr>
				<td><?php echo esc_html( get_the_title( $row->post_id ) ); ?></td>
				<td><?php echo esc_html( $row->text ); ?></td>
				<td><?php echo esc_html( (int) $row->a_clicks ); ?></td>
				<td><?php echo esc_html( number_format_i18n( $row->a_ctr, 2 ) ); ?>%</td>
				<td><?php echo esc_html( (int) $row->b_clicks ); ?></td>
				<td><?php echo esc_html( number_format_i18n( $row->b_ctr, 2 ) ); ?>%</td>
				<td><strong><?php echo esc_html( $row->winner ); ?></strong></td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<!-- ─── Pagination ─── -->
	<?php if ( $total_pages > 1 ) : ?>
	<div class="tab-pagination">
		<?php
		$range = 2;
		$start = max( 1, $ab_paged - $range );
		$end   = min( $total_pages, $ab_paged + $range );

		if ( $ab_paged > 1 ) {
			echo '<a class="button" href="' . esc_url( add_query_arg( [ 'ab_paged' => 1 ] ) ) . '#ab-summary-section">&laquo; ' . esc_html__( 'First', 'plinkly-smart-cta-buttons' ) . '</a> ';
		}

		if ( $start > 1 ) {
			echo '<a class="button" href="' . esc_url( add_query_arg( [ 'ab_paged' => 1 ] ) ) . '#ab-summary-section">1</a> ';
			if ( $start > 2 ) echo '<span class="dots">…</span> ';
		}

		for ( $i = $start; $i <= $end; $i++ ) {
			if ( $i === $ab_paged ) {
				echo '<span class="current">' . esc_html( $i ) . '</span> ';
			} else {
				echo '<a class="button" href="' . esc_url( add_query_arg( [ 'ab_paged' => $i ] ) ) . '#ab-summary-section">' . esc_html( $i ) . '</a> ';
			}
		}

		if ( $end < $total_pages ) {
			if ( $end < $total_pages - 1 ) echo '<span class="dots">…</span> ';
			echo '<a class="button" href="' . esc_url( add_query_arg( [ 'ab_paged' => $total_pages ] ) ) . '#ab-summary-section">' . esc_html( $total_pages ) . '</a> ';
		}

		if ( $ab_paged < $total_pages ) {
			echo '<a class="button" href="' . esc_url( add_query_arg( [ 'ab_paged' => $total_pages ] ) ) . '#ab-summary-section">' . esc_html__( 'Last', 'plinkly-smart-cta-buttons' ) . ' &raquo;</a>';
		}
		?>
	</div>
	<?php endif; ?>

	<?php else : ?>
	<p><?php esc_html_e( 'No A/B-test data found.', 'plinkly-smart-cta-buttons' ); ?></p>
	<?php endif; ?>

</div><!-- /card -->
