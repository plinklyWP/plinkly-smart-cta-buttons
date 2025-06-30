<?php
/**
 * Plinkly – AJAX proxy for “AI Dashboard Insights”
 *
 * Collects CTA metrics from the browser, signs them with HMAC, then
 * forwards the payload to the remote endpoint (ai-dashboard-insights-api.php).
 * Returns Gemini’s summary back to JavaScript.
 *
 * File location: /admin/ajax/ai-dashboard-insights.php
 * Loaded only in wp-admin; not meant for direct front-end access.
 *
 * PHP ≥ 7.4 — WordPress ≥ 6.3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Prevent direct access.
}

/* --------------------------------------------------------------------------
 * (0) Register the handler for logged-in admins
 * ----------------------------------------------------------------------- */
add_action( 'wp_ajax_plinkly_ai_dashboard_insights', 'plinkly_ajax_ai_dashboard_insights' );

/* --------------------------------------------------------------------------
 * (1) Main callback
 * ----------------------------------------------------------------------- */
function plinkly_ajax_ai_dashboard_insights() {

	/* ─── 1. Basic nonce / capability ─── */
if ( ! current_user_can( 'manage_options' ) ) {
	wp_send_json_error( __( 'Permission denied', 'plinkly' ), 403 );
}
check_ajax_referer( 'plinkly_admin', 'nonce' );

/* ─── 2. Parse metrics JSON ─── */
$metrics_json = isset( $_POST['metrics'] ) ? wp_unslash( $_POST['metrics'] ) : '';
$metrics      = json_decode( $metrics_json, true );

if ( empty( $metrics ) || ! is_array( $metrics ) ) {
	wp_send_json_error( __( 'Malformed metrics payload', 'plinkly' ), 400 );
}

	/* ——— 3. Resolve licence + secrets ——— */
	$license_key = get_option( 'plinkly_license_key', '' );
	$hmac_secret = get_option( 'plinkly_hmac_secret', '' );

	if ( empty( $license_key ) || empty( $hmac_secret ) ) {
		wp_send_json_error( __( 'Licence or HMAC secret not configured', 'plinkly-smart-cta-buttons' ), 500 );
	}

	/* ——— 4. Compute signature ——— */
	$signature = hash_hmac(
		'sha256',
		wp_json_encode( $metrics, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ),
		$hmac_secret
	);

	/* ——— 5. Resolve proxy token (constant OR option) ——— */
	$proxy_token = ( defined( 'PLYCTA_PROXY_API_KEY' ) && PLYCTA_PROXY_API_KEY !== '' )
		? PLYCTA_PROXY_API_KEY
		: get_option( 'plinkly_proxy_key', '' );

	if ( empty( $proxy_token ) ) {
		wp_send_json_error( __( 'Proxy key not configured', 'plinkly-smart-cta-buttons' ), 500 );
	}

	/* ——— 6. Remote endpoint URL ———
	 *    We pass licence_key as a query-param so it is available
	 *    during CORS pre-flight on the server.
	 */
	$ai_endpoint = add_query_arg(
		'license_key',
		rawurlencode( $license_key ),
		'https://api.plink.ly/api/ai-dashboard-insights-api.php'
	);

	/* ——— 7. Build the request body ——— */
	$payload = [
		'license_key' => $license_key,
		'metrics'     => $metrics,
		'signature'   => $signature,
	];

	$args = [
		'timeout' => 20,
		'headers' => [
			'Content-Type'    => 'application/json',
			'X-PlinkLy-Token' => $proxy_token,
		],
		'body'    => wp_json_encode( $payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ),
	];

	/* ——— 8. Dispatch & handle response ——— */
	$response = wp_remote_post( $ai_endpoint, $args );

	if ( is_wp_error( $response ) ) {
		wp_send_json_error( $response->get_error_message(), 500 );
	}

	$code = wp_remote_retrieve_response_code( $response );
	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );

	if ( $code !== 200 ) {
		$error_msg = isset( $data['error'] ) ? $data['error'] : __( 'AI server error', 'plinkly-smart-cta-buttons' );
		wp_send_json_error( $error_msg, $code );
	}

	/* ——— 9. Success ——— */
	wp_send_json_success( $data );
}

