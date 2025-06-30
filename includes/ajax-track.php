<?php
// File: includes/ajax-track.php
// Track button clicks via AJAX (supports A/B variant + zone/position tracking).

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handle "plinkly_track_click" AJAX.
 */
function plinkly_track_click() {

	/* 1) Nonce check */
	check_ajax_referer( 'plinkly_click_nonce', 'nonce' );

	/* 2) Basic param check */
	if (
		! isset(
			$_POST['post_id'],
			$_POST['text'],
			$_POST['link'],
			$_POST['platform']
		)
	) {
		wp_send_json_error( 'Missing parameters' );
	}

	global $wpdb;
	$table = $wpdb->prefix . 'plinkly_clicks';

	/* 3) Sanitize & prepare data */
	$post_id  = intval( $_POST['post_id'] );
	$text     = sanitize_text_field( wp_unslash( $_POST['text'] ) );
	$link     = esc_url_raw( wp_unslash( $_POST['link'] ) );
	$platform = sanitize_text_field( wp_unslash( $_POST['platform'] ) );

	/* Variant (A/B) */
	$variant = isset( $_POST['variant'] )
		? sanitize_key( wp_unslash( $_POST['variant'] ) )
		: 'A';
	$variant = in_array( strtoupper( $variant ), [ 'A', 'B' ], true ) ? strtoupper( $variant ) : 'A';

	/* ───── Placement Tracker data (optional) ───── */
	$zone = isset( $_POST['zone'] )
		? sanitize_text_field( wp_unslash( $_POST['zone'] ) )
		: '';

	$position = isset( $_POST['position'] )
		? sanitize_text_field( wp_unslash( $_POST['position'] ) )
		: '';

	/* 4) INSERT */
	$wpdb->insert(
		$table,
		[
			'post_id'     => $post_id,
			'button_text' => $text,
			'button_link' => $link,
			'platform'    => $platform,
			'clicked_at'  => current_time( 'mysql' ),
			'variant'     => $variant,
			'zone'        => $zone,      // ⬅️ جديد
			'position'    => $position,  // احتياطي
		],
		[
			'%d',  // post_id
			'%s',  // text
			'%s',  // link
			'%s',  // platform
			'%s',  // clicked_at
			'%s',  // variant
			'%s',  // zone
			'%s',  // position
		]
	);

	/* 5) Response */
	wp_send_json_success(
		[
			'message' => 'Click tracked',
			'variant' => $variant,
			'zone'    => $zone,
		]
	);
}

/* -------------------------------------------------------------------------
 * Register AJAX hooks
 * ---------------------------------------------------------------------- */
add_action( 'wp_ajax_plinkly_track_click',        'plinkly_track_click' );
add_action( 'wp_ajax_nopriv_plinkly_track_click', 'plinkly_track_click' );
