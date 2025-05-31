<?php
// File: admin/dashboard-page.php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the PlinkLy Statistics dashboard
 */
function plinkly_render_dashboard() {

	/* ───────── Security & Header ───────── */
	if ( ! plinkly_is_pro_active() ) {
		echo '<div class="notice notice-warning"><p>' .
			esc_html__( 'Statistics are only available in the Pro version.', 'plinkly-smart-cta-buttons' ) .
			' <a href="https://plink.ly/#price-box" class="button button-primary">' .
			esc_html__( 'Upgrade Now', 'plinkly-smart-cta-buttons' ) .
			'</a></p></div>';
		return;
	}

	plinkly_render_header( 'PlinkLy CTA – Statistics' );

	global $wpdb;
	$table_clicks      = $wpdb->prefix . 'plinkly_clicks';
	$table_impressions = $wpdb->prefix . 'plinkly_impressions';

	/* ───────── Input Filters ───────── */
	$today       = gmdate( 'Y-m-d' );
	$filter_from = sanitize_text_field( $_GET['filter_from'] ?? '' );
	$filter_to   = sanitize_text_field( $_GET['filter_to']   ?? '' );
	$top_from    = sanitize_text_field( $_GET['top_from']    ?? '' );
	$top_to      = sanitize_text_field( $_GET['top_to']      ?? '' );
	$top_paged   = max( 1, (int) ( $_GET['top_paged'] ?? 1 ) );
	$paged       = max( 1, (int) ( $_GET['paged']     ?? 1 ) );

	/* ───────── Queries ───────── */
	require_once __DIR__ . '/dashboard/dashboard-data-queries.php';

	// ── Map variables (يدعم الاسمين: القديم والجديد) ──
	$daily_counts           = $plinkly_daily_counts           ?? ($daily_counts           ?? []);
	$top_post_titles_short  = $plinkly_top_post_titles_short  ?? ($top_post_titles_short  ?? []);
	$top_post_titles_full   = $plinkly_top_post_titles_full   ?? ($top_post_titles_full   ?? []);
	$top_posts              = $plinkly_top_posts              ?? ($top_posts              ?? []);
	$platform_names_short   = $plinkly_platform_names_short   ?? ($platform_names_short   ?? []);
	$platform_names_full    = $plinkly_platform_names_full    ?? ($platform_names_full    ?? []);
	$platform_counts        = $plinkly_platform_counts        ?? ($platform_counts        ?? []);

	/* ───────── Output UI ───────── */
	include __DIR__ . '/dashboard/dashboard-summary.php';
	include __DIR__ . '/dashboard/dashboard-trend-charts.php';
	include __DIR__ . '/dashboard/dashboard-top-buttons.php';
	include __DIR__ . '/dashboard/dashboard-detailed-data.php';

	/* ───────── Pass data to JS ───────── */
	add_action( 'admin_footer', function() use(
		$daily_counts, $top_post_titles_short, $top_post_titles_full,
		$top_posts, $platform_names_short, $platform_names_full, $platform_counts
	) {

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || $screen->id !== 'toplevel_page_plinkly-cta-dashboard' ) {
			return;
		}
		?>
		<script>
		window.PlinklyDashboard = <?php echo wp_json_encode( [
			'daily_counts'           => $daily_counts,
			'top_post_titles_short'  => $top_post_titles_short,
			'top_post_titles_full'   => $top_post_titles_full,
			'top_posts'              => array_map(
				fn( $r ) => is_object( $r ) ? intval( $r->total ) : ( is_array( $r ) ? intval( $r['total'] ) : 0 ),
				$top_posts
			),
			'platform_names_short'   => $platform_names_short,
			'platform_names_full'    => $platform_names_full,
			'platform_counts'        => array_map(
				fn( $r ) => is_object( $r ) ? intval( $r->total ) : ( is_array( $r ) ? intval( $r['total'] ) : 0 ),
				$platform_counts
			),
		] ); ?>;
		</script>
		<?php
	}, 99 );
}


/* ───────── Helper functions (optional – used by other UI parts) ───────── */

/**
 * Get labels (dates) for daily line-chart
 */
function plinkly_get_chart_labels() {
	global $wpdb;

	return wp_list_pluck(
		$wpdb->get_results(
			"SELECT DATE(clicked_at) AS date
			 FROM {$wpdb->prefix}plinkly_clicks
			 GROUP BY DATE(clicked_at)
			 ORDER BY date ASC"
		),
		'date'
	);
}

/**
 * Get values (counts) for daily line-chart
 */
function plinkly_get_chart_values() {
	global $wpdb;

	return wp_list_pluck(
		$wpdb->get_results(
			"SELECT COUNT(*) AS total
			 FROM {$wpdb->prefix}plinkly_clicks
			 GROUP BY DATE(clicked_at)
			 ORDER BY DATE(clicked_at) ASC"
		),
		'total'
	);
}
