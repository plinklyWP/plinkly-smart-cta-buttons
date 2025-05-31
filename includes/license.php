<?php
/**
 * PlinkLy – License manager
 * --------------------------------------------------------------------------
 * • Validates the licence key when it is saved
 * • Performs an automatic (silent) check once every 24 h
 * • Stores status, plan, quota, and expiry locally
 *
 * File: includes/license.php
 * --------------------------------------------------------------------------
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* -------------------------------------------------------------------------
 * 0. Constants
 * ---------------------------------------------------------------------- */
if ( ! defined( 'PLINKLY_LICENSE_CACHE_HOURS' ) ) {
	// skip remote call if the previous successful check is newer than X hours
	define( 'PLINKLY_LICENSE_CACHE_HOURS', 12 );
}

/* -------------------------------------------------------------------------
 * 1. Validate immediately when user saves a key
 * ---------------------------------------------------------------------- */
add_action( 'update_option_plinkly_pro_license_key', 'plinkly_validate_license_on_save', 10, 2 );

function plinkly_validate_license_on_save( $old_value, $new_value ) {
	plinkly_remote_validate_license( sanitize_text_field( $new_value ), true ); // show admin-notice
}

/* -------------------------------------------------------------------------
 * 2. Daily silent verification via WP-Cron
 * ---------------------------------------------------------------------- */
if ( ! wp_next_scheduled( PLINKLY_CRON_HOOK ) ) {
	wp_schedule_event( time() + HOUR_IN_SECONDS, 'hourly', PLINKLY_CRON_HOOK );
}

add_action( PLINKLY_CRON_HOOK, function () {

	$key = get_option( 'plinkly_pro_license_key', '' );
	if ( ! $key ) {
		return;
	}

	/* mini-cache: avoid double hits if another plugin fires cron early */
	$last = (int) get_option( 'plinkly_last_license_check', 0 );
	if ( $last && $last > time() - PLINKLY_LICENSE_CACHE_HOURS * HOUR_IN_SECONDS ) {
		return;
	}

	plinkly_remote_validate_license( $key, false );
	update_option( 'plinkly_last_license_check', time() );
} );

/* -------------------------------------------------------------------------
 * 3. Core remote validation routine
 * ---------------------------------------------------------------------- */
function plinkly_remote_validate_license( string $license_key, bool $show_notice = false ) {

	$license_key = trim( $license_key );

	/* —— Empty key = reset local status —— */
	if ( '' === $license_key ) {
		delete_option( 'plinkly_license_status'  );
		delete_option( 'plinkly_license_expires' );
		delete_option( 'plinkly_license_quota'   );
		delete_option( 'plinkly_license_plan'   );
		return;
	}

	/* —— Build headers —— */
	$headers = [
		'Content-Type' => 'application/json; charset=utf-8',
	];

	if ( defined( 'PLINKLY_API_SECRET' ) && PLINKLY_API_SECRET !== '' ) {
		// Secret exists only on the licence-store site
		$headers['Authorization'] = 'Bearer ' . PLINKLY_API_SECRET;
	}

	/* —— Payload (filterable) —— */
	$payload = apply_filters( 'plinkly_license_payload', [
		'key'      => $license_key,
		'site_url' => home_url(),
	] );

	/* —— Remote POST —— */
	$response = wp_remote_post(
		apply_filters( 'plinkly_validate_endpoint', PLINKLY_LICENSE_VALIDATE_ENDPOINT ),
		[
			'headers' => $headers,
			'timeout' => 15,
			'body'    => wp_json_encode( $payload ),
		]
	);

	/* —— Error handling —— */
	if ( is_wp_error( $response ) ) {
		$message = $response->get_error_message();
		plinkly_handle_validation_result( [ 'status' => 'error', 'message' => $message ], $show_notice );
		error_log( '[PlinkLy] Licence validation WP_Error: ' . $message );
		return;
	}

	$code = wp_remote_retrieve_response_code( $response );
	$body = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( 200 !== $code || ! is_array( $body ) ) {
		$msg = 'Unexpected response (' . $code . ')';
		plinkly_handle_validation_result( [ 'status' => 'error', 'message' => $msg ], $show_notice );
		error_log( '[PlinkLy] Licence validation: ' . $msg );
		return;
	}

	/* —— Allow 3rd-party filters —— */
	$body = apply_filters( 'plinkly_validation_result', $body, $license_key );

	plinkly_handle_validation_result( $body, $show_notice );
}

/* -------------------------------------------------------------------------
 * 4. Store result + (optionally) show admin-notice
 * ---------------------------------------------------------------------- */
function plinkly_handle_validation_result( array $result, bool $show_notice ) {

	$status  = sanitize_key( $result['status']  ?? 'error' );
	$message = sanitize_textarea_field( $result['message'] ?? __( 'Unknown error', 'plinkly-smart-cta-buttons' ) );

	if ( 'success' === $status ) {

		update_option( 'plinkly_license_status',  'valid' );
		update_option( 'plinkly_license_expires', sanitize_text_field( $result['expires_at']  ?? '' ) );
		update_option( 'plinkly_license_quota',   (int) ( $result['quota_daily'] ?? 0 ) );
		update_option( 'plinkly_license_plan',    sanitize_key( $result['plan'] ?? 'free' ) );

		if ( $show_notice ) {
			add_action( 'admin_notices', function () {
				echo '<div class="notice notice-success is-dismissible"><p>' .
				     esc_html__( 'License activated successfully.', 'plinkly-smart-cta-buttons' ) .
				     '</p></div>';
			} );
		}

	} else {

		update_option( 'plinkly_license_status', 'invalid' );
		update_option( 'plinkly_license_plan',   'free' );

		if ( $show_notice ) {
			add_action( 'admin_notices', function () use ( $message ) {
				echo '<div class="notice notice-error is-dismissible"><p>' .
				     esc_html( $message ) .
				     '</p></div>';
			} );
		}
	}
}
