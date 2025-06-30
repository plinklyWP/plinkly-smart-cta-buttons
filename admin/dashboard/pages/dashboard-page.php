<?php
// File: admin/dashboard/pages/dashboard-page.php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the PlinkLy Statistics dashboard (classic + AI view)
 */
function plinkly_render_dashboard() {

	/* ───────── Security & Header ───────── */
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
	require_once __DIR__ . '/../logic/dashboard-data-queries.php';

	/* ───────── Map variable aliases ───────── */
	$daily_counts           = $plinkly_daily_counts           ?? ($daily_counts           ?? []);
	$hourly_counts          = $plinkly_hourly_counts          ?? ($hourly_counts          ?? []);
	$top_post_titles_short  = $plinkly_top_post_titles_short  ?? ($top_post_titles_short  ?? []);
	$top_post_titles_full   = $plinkly_top_post_titles_full   ?? ($top_post_titles_full   ?? []);
	$top_posts              = $plinkly_top_posts              ?? ($top_posts              ?? []);
	$platform_names_short   = $plinkly_platform_names_short   ?? ($platform_names_short   ?? []);
	$platform_names_full    = $plinkly_platform_names_full    ?? ($platform_names_full    ?? []);
	$platform_counts        = $plinkly_platform_counts        ?? ($platform_counts        ?? []);
	$device_labels          = $plinkly_device_labels          ?? ($device_labels          ?? []);
	$device_counts          = $plinkly_device_counts          ?? ($device_counts          ?? []);
	$ref_labels             = $plinkly_ref_labels             ?? ($ref_labels             ?? []);
	$ref_counts             = $plinkly_ref_counts             ?? ($ref_counts             ?? []);
	$unique_users           = $plinkly_unique_users           ?? ($unique_users           ?? 0);
	$ab_clicks              = $plinkly_ab_clicks              ?? ($ab_clicks              ?? []);
	$ab_views               = $plinkly_ab_views               ?? ($ab_views               ?? []);

	/* ───────── UI toggle (AI) ───────── */
	

	/* ───────── Classic dashboard ───────── */
include __DIR__ . '/../sections/dashboard-summary.php';
include __DIR__ . '/../sections/plinkly-ai-advice.php';
include __DIR__ . '/../sections/dashboard-trend-charts.php';
include __DIR__ . '/../sections/dashboard-top-buttons.php';
include __DIR__ . '/../sections/dashboard-ab-summary.php';
//include __DIR__ . '/../sections/dashboard-placement-report.php';
//include __DIR__ . '/../sections/dashboard-easylink-report.php';
include __DIR__ . '/../sections/dashboard-detailed-data.php';

	/* ───────── Inject dynamic JS data ───────── */
	$extra = [
		/* summary */
		'total_clicks'   => $total_clicks,
		'avg_clicks'     => $avg_clicks,
		'weekly_change'  => $weekly_change,
		'unique_users'   => $unique_users,

		/* trends */
		'daily_counts'   => array_values( $daily_counts ),
		'hourly_counts'  => array_values( $hourly_counts ),

		/* platforms */
		'platform_names_full'  => array_values( $platform_names_full ),
		'platform_names_short' => array_values( $platform_names_short ),
		'platform_counts'      => array_map(
			static fn ( $r ) => (int) ( is_object( $r ) ? $r->total : $r['total'] ),
			$platform_counts
		),

		/* devices */
		'device_labels' => $device_labels,
		'device_counts' => array_map(
			static fn ( $r ) => (int) ( is_object( $r ) ? $r->total : $r['total'] ),
			$device_counts
		),

		/* referrers */
		'ref_labels' => $ref_labels,
		'ref_counts' => array_map(
			static fn ( $r ) => (int) ( is_object( $r ) ? $r->total : $r['total'] ),
			$ref_counts
		),

		/* top posts */
		'top_post_titles_full'  => array_values( $top_post_titles_full ),
		'top_post_titles_short' => array_values( $top_post_titles_short ),
		'top_posts'             => array_map(
			static fn ( $r ) => (int) ( is_object( $r ) ? $r->total : $r['total'] ),
			$top_posts
		),

		/* A/B */
		'ab_clicks' => $ab_clicks,
		'ab_views'  => $ab_views,
	];

	wp_add_inline_script(
		'plinkly-dashboard-js',
		'Object.assign( window.PlinklyDashboard || {}, ' .
		wp_json_encode( $extra, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) .
		');',
		'after'
	);
}
?>
