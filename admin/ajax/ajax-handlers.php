<?php
/**
 * PlinkLy – CSV export (AJAX)
 * --------------------------------------------------------------------------
 * Admin-AJAX endpoints:
 *   • plinkly_export_csv               – Detailed clicks
 *   • plinkly_export_top_buttons_csv   – Top Buttons
 *   • plinkly_export_ab_summary_csv    – A/B-Test Summary
 *   • plinkly_export_position_csv      – CTA Placement Performance  ← جديد
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
 * الصلاحيات:
 *  – مستخدم مسجّل
 *  – يمتلك export_plinkly_data (أُضيفت للإداريين فى activation) أو manage_options
 *  – nonce صالح
 */
function plinkly_export_check_permission() {

	if ( ! is_user_logged_in() ) {
		wp_send_json_error( __( 'Authentication required.', 'plinkly-smart-cta-buttons' ), 401 );
	}

	if ( ! current_user_can( 'export_plinkly_data' ) && ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( __( 'Insufficient capability.', 'plinkly-smart-cta-buttons' ), 403 );
	}

	check_ajax_referer( 'plinkly_export_csv', 'nonce' );
}

/** إرسال رؤوس CSV وفتح php://output */
function plinkly_csv_start( $filename ) {
	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=' . $filename );
	return fopen( 'php://output', 'w' );
}

/* -------------------------------------------------------------------------
 * 1. Detailed clicks export
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
 * 2. Top-buttons export
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

/* -------------------------------------------------------------------------
 * 3. A/B-summary export
 * ---------------------------------------------------------------------- */
add_action( 'wp_ajax_plinkly_export_ab_summary_csv', function () {

	plinkly_export_check_permission();

	global $wpdb;
	$table = $wpdb->prefix . 'plinkly_clicks';

	$sql   = "SELECT post_id, button_text,
	                 SUM( variant='A' ) AS a_clicks,
	                 SUM( variant='B' ) AS b_clicks
	          FROM {$table}";
	$where = [];

	if ( ! empty( $_GET['ab_from'] ) && ! empty( $_GET['ab_to'] ) ) {
		$where[] = $wpdb->prepare(
			'DATE(clicked_at) BETWEEN %s AND %s',
			sanitize_text_field( $_GET['ab_from'] ),
			sanitize_text_field( $_GET['ab_to'] )
		);
	}

	if ( $where ) {
		$sql .= ' WHERE ' . implode( ' AND ', $where );
	}
	$sql .= ' GROUP BY post_id, button_text';

	$rows = $wpdb->get_results( $sql, ARRAY_A );

	$fh = plinkly_csv_start( 'ab-summary.csv' );
	fputcsv( $fh, [ 'Post', 'Button Text', 'A Clicks', 'B Clicks', 'Winner' ] );

	foreach ( $rows as $r ) {
		$winner = $r['a_clicks'] == $r['b_clicks'] ? '–' : ( $r['a_clicks'] > $r['b_clicks'] ? 'A' : 'B' );
		fputcsv( $fh, [
			get_the_title( $r['post_id'] ),
			$r['button_text'],
			$r['a_clicks'],
			$r['b_clicks'],
			$winner
		] );
	}
	fclose( $fh );
	exit;
} );

/* -------------------------------------------------------------------------
 * 4. Placement-report export  ← جديد
 * ---------------------------------------------------------------------- */
add_action( 'wp_ajax_plinkly_export_position_csv', function () {

	plinkly_export_check_permission();

	global $wpdb;
	$table = $wpdb->prefix . 'plinkly_clicks';

	$sql   = "SELECT post_id,
	                 button_text,
	                 position,
	                 COUNT(*) AS clicks
	          FROM   {$table}
	          WHERE  position <> ''";
	$where = [];

	if ( ! empty( $_GET['pos_from'] ) && ! empty( $_GET['pos_to'] ) ) {
		$where[] = $wpdb->prepare(
			'DATE(clicked_at) BETWEEN %s AND %s',
			sanitize_text_field( $_GET['pos_from'] ),
			sanitize_text_field( $_GET['pos_to'] )
		);
	}

	if ( $where ) {
		$sql .= ' AND ' . implode( ' AND ', $where );
	}
	$sql .= ' GROUP BY post_id, button_text, position
	          ORDER BY clicks DESC';

	$rows = $wpdb->get_results( $sql, ARRAY_A );

	$fh = plinkly_csv_start( 'placement-report.csv' );
	fputcsv( $fh, [ 'Post', 'Button Text', 'Position', 'Clicks' ] );

	foreach ( $rows as $r ) {
		fputcsv( $fh, [
			get_the_title( $r['post_id'] ),
			$r['button_text'],
			$r['position'],
			$r['clicks']
		] );
	}
	fclose( $fh );
	exit;
} );
/* -------------------------------------------------------------------------
 * 5. EasyLink clicks export
 * ---------------------------------------------------------------------- */
add_action( 'wp_ajax_plinkly_export_easylink_csv', function () {

	plinkly_export_check_permission();

	global $wpdb;
	$table = $wpdb->prefix . 'plinkly_easylink_clicks';

	$sql   = "SELECT keyword, link, COUNT(*) AS clicks FROM {$table}";
	$where = [];

	if ( ! empty( $_GET['el_from'] ) && ! empty( $_GET['el_to'] ) ) {
		$where[] = $wpdb->prepare(
			'DATE(clicked_at) BETWEEN %s AND %s',
			sanitize_text_field( $_GET['el_from'] ),
			sanitize_text_field( $_GET['el_to'] )
		);
	}

	if ( $where ) {
		$sql .= ' WHERE ' . implode( ' AND ', $where );
	}
	$sql .= ' GROUP BY keyword, link ORDER BY clicks DESC';

	$rows = $wpdb->get_results( $sql, ARRAY_A );

	$fh = plinkly_csv_start( 'easylink-clicks.csv' );
	fputcsv( $fh, [ 'Keyword', 'Link', 'Clicks' ] );
	foreach ( $rows as $r ) {
		fputcsv( $fh, $r );
	}
	fclose( $fh );
	exit;
} );

