<?php
/**
 * PlinkLy – CSV export (AJAX)
 * --------------------------------------------------------------------------
 * Two admin-AJAX endpoints:
 *   • plinkly_export_csv             → كل النقرات المفصّلة
 *   • plinkly_export_top_buttons_csv → أكثر الأزرار نقرًا
 *
 * File: includes/ajax-export.php
 * --------------------------------------------------------------------------
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* -------------------------------------------------------------------------
 * Helpers
 * ---------------------------------------------------------------------- */

/**
 * Common security gate for all export handlers.
 * – Requires logged-in user + capability + (optionally) PRO licence.
 *
 * @throws WP_Error on failure.
 */
function plinkly_export_check_permission() {

	if ( ! is_user_logged_in() ) {
		wp_send_json_error( __( 'Authentication required.', 'plinkly-smart-cta-buttons' ), 401 );
	}

	if ( ! current_user_can( 'view_plinkly_pro_stats' ) ) {
		wp_send_json_error( __( 'Insufficient capability.', 'plinkly-smart-cta-buttons' ), 403 );
	}

	if ( function_exists( 'plinkly_is_pro_active' ) && ! plinkly_is_pro_active() ) {
		wp_send_json_error( __( 'Upgrade required.', 'plinkly-smart-cta-buttons' ), 403 );
	}

	check_ajax_referer( 'plinkly_export_csv', 'nonce' ); // expects &nonce=…
}

/**
 * Sends CSV headers and opens php://output, returns the pointer.
 */
function plinkly_csv_start( $filename ) {
	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=' . $filename );
	return fopen( 'php://output', 'w' );
}

/* -------------------------------------------------------------------------
 * 1. Detailed Export
 * ---------------------------------------------------------------------- */
add_action( 'wp_ajax_plinkly_export_csv', function () {

	plinkly_export_check_permission();

	global $wpdb;
	$table = $wpdb->prefix . 'plinkly_clicks';

	$sql   = "SELECT button_text, button_link, platform, post_id, clicked_at 
	          FROM {$table}";
	$where = [];

	if ( ! empty( $_GET['filter_from'] ) && ! empty( $_GET['filter_to'] ) ) {
		$where[] = $wpdb->prepare(
			'DATE(clicked_at) BETWEEN %s AND %s',
			sanitize_text_field( $_GET['filter_from'] ),
			sanitize_text_field( $_GET['filter_to'] )
		);
	}

	if ( $where ) {
		$sql .= ' WHERE ' . implode( ' AND ', $where );
	}
	$sql .= ' ORDER BY clicked_at DESC';

	$rows = $wpdb->get_results( $sql, ARRAY_A );

	$fh = plinkly_csv_start( 'detailed-clicks.csv' );
	fputcsv( $fh, [ 'Text', 'Link', 'Platform', 'Post ID', 'Time' ] );

	foreach ( $rows as $row ) {
		fputcsv( $fh, $row );
	}
	fclose( $fh );
	exit;
} );

/* -------------------------------------------------------------------------
 * 2. Top-buttons Export
 * ---------------------------------------------------------------------- */
add_action( 'wp_ajax_plinkly_export_top_buttons_csv', function () {

	plinkly_export_check_permission();

	global $wpdb;
	$table = $wpdb->prefix . 'plinkly_clicks';

	$sql   = "SELECT button_text, button_link, COUNT(*) AS total_clicks
	          FROM {$table}";
	$where = [];

	if ( ! empty( $_GET['top_from'] ) && ! empty( $_GET['top_to'] ) ) {
		$where[] = $wpdb->prepare(
			'DATE(clicked_at) BETWEEN %s AND %s',
			sanitize_text_field( $_GET['top_from'] ),
			sanitize_text_field( $_GET['top_to'] )
		);
	}

	if ( $where ) {
		$sql .= ' WHERE ' . implode( ' AND ', $where );
	}
	$sql .= ' GROUP BY button_text, button_link
	          ORDER BY total_clicks DESC';

	$rows = $wpdb->get_results( $sql, ARRAY_A );

	$fh = plinkly_csv_start( 'top-buttons.csv' );
	fputcsv( $fh, [ 'Text', 'Link', 'Clicks' ] );

	foreach ( $rows as $row ) {
		fputcsv( $fh, $row );
	}
	fclose( $fh );
	exit;
} );
