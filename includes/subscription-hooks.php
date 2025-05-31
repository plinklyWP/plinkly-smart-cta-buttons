<?php
// File: includes/subscription-hooks.php
// Description: Generate/renew license keys – now signed with HMAC.

/* ───────── منع الوصول المباشر ───────── */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! defined( 'PLINKLY_API_SECRET' ) || PLINKLY_API_SECRET === '' ) {
	return; // يعمل فقط على متجر التراخيص
}

/* ───────── WooCommerce hooks ───────── */
add_action( 'woocommerce_subscription_status_active',            'plinkly_generate_license_on_subscription', 10, 1 );
add_action( 'woocommerce_subscription_renewal_payment_complete', 'plinkly_generate_license_on_subscription', 10, 1 );

/**
 * Generate a license key via the remote API and store it in user meta.
 *
 * @param WC_Subscription $subscription
 */
function plinkly_generate_license_on_subscription( $subscription ) {

	if ( ! $subscription instanceof WC_Subscription ) {
		return;
	}

	$user_id         = $subscription->get_user_id();
	$subscription_id = $subscription->get_id();
	$meta_key        = 'plinkly_license_' . $subscription_id;

	/* — منع التكرار — */
	if ( get_user_meta( $user_id, $meta_key, true ) ) {
		return;
	}

	/* — البريد الإلكترونى — */
	$email = $subscription->get_billing_email();
	if ( ! is_email( $email ) ) {
		error_log( "PlinkLy: invalid email for subscription #{$subscription_id}" );
		return;
	}

	/* — خريطة الخطط — */
	$plan_map = [
		247 => 'free',         // Free plan
		317 => 'pro_yearly',   // Pro yearly plan
		789 => 'lifetime',     // Lifetime plan
	];

	$items = $subscription->get_items();
	if ( empty( $items ) ) {
		error_log( "PlinkLy: no items in subscription #{$subscription_id}" );
		return;
	}

	$first_item = reset( $items );
	$product_id = $first_item->get_product_id();
	if ( ! isset( $plan_map[ $product_id ] ) ) {
		error_log( "PlinkLy: unmapped product ID {$product_id} for subscription #{$subscription_id}" );
		return;
	}
	$plan = $plan_map[ $product_id ];

	/* — حساب expires_at — */
	if ( 'lifetime' === $plan ) {
		$expires_at = '9999-12-31 23:59:59';
	} else {
		$next_timestamp = $subscription->get_time( 'next_payment' );
		$expires_at     = $next_timestamp
			? date( 'Y-m-d H:i:s', $next_timestamp )
			: date( 'Y-m-d H:i:s', strtotime( '+1 year' ) );
	}

	/* — بناء الحمولة — */
	$payload = [
		'user_email'      => $email,
		'plan'            => $plan,
		'subscription_id' => (string) $subscription_id,
		'expires_at'      => $expires_at,
		'site_url'        => home_url(),
	];

	/* ─────────ــ التوقيع HMAC + Timestamp ــ───────── */
	$timestamp = time();
	$body_json = wp_json_encode( $payload );
	$signature = hash_hmac( 'sha256', $timestamp . $body_json, PLINKLY_API_SECRET );

	$args = [
		'headers' => [
			'Content-Type'       => 'application/json; charset=utf-8',
			'X-G2PICK-Timestamp' => $timestamp,
			'X-G2PICK-Signature' => $signature,
		],
		'body'    => $body_json,
		'timeout' => 15,
	];

	/* — استدعاء create-key.php — */
	$response = wp_remote_post( PLINKLY_LICENSE_CREATE_ENDPOINT, $args );
	$status   = is_wp_error( $response )
		? 0
		: wp_remote_retrieve_response_code( $response );

	if ( is_wp_error( $response ) || 200 !== $status ) {
		$error_message = is_wp_error( $response )
			? $response->get_error_message()
			: "HTTP {$status}";
		error_log( "PlinkLy License API HTTP error: {$error_message}" );
		return;
	}

	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );
	if ( json_last_error() !== JSON_ERROR_NONE || empty( $data['license_key'] ) ) {
		error_log( "PlinkLy License API invalid JSON or missing key: {$body}" );
		return;
	}

	/* — التخزين والإشعار — */
	$license_key = sanitize_text_field( $data['license_key'] );
	update_user_meta( $user_id, $meta_key, $license_key );

	do_action(
    'plinkly/send_license_email',
    $license_key,                                   // المتغير 1
    ucfirst( str_replace( '_', ' ', $plan ) ),      // المتغير 2
    $expires_at,                                    // المتغير 3
    $email                                          // المتغير 4
);
}
