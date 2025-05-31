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

define( 'PRODUCT_ID_LIFETIME', 322 ); // ← غيّرها إلى ID منتج lifetime

add_action( 'woocommerce_order_status_completed', 'plinkly_generate_license_on_order', 10, 1 );

function plinkly_generate_license_on_order( $order_id ) {
    $order = wc_get_order( $order_id );
    if ( ! $order ) return;

    $user_id = $order->get_user_id();
    $email   = $order->get_billing_email();
    if ( ! is_email( $email ) ) return;

    foreach ( $order->get_items() as $item ) {
        $product_id = $item->get_product_id();

        if ( $product_id != PRODUCT_ID_LIFETIME ) continue;

        $meta_key = 'plinkly_license_' . $order_id;
        if ( get_user_meta( $user_id, $meta_key, true ) ) return; // منع التكرار

        // حمولة الطلب
        $payload = [
            'user_email'      => $email,
            'plan'            => 'lifetime',
            'subscription_id' => (string) $order_id,
            'expires_at'      => '2099-12-31 23:59:59',
            'site_url'        => home_url(),
        ];

        // توقيع HMAC
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

        $response = wp_remote_post( PLINKLY_LICENSE_CREATE_ENDPOINT, $args );
        $status   = is_wp_error( $response ) ? 0 : wp_remote_retrieve_response_code( $response );

        if ( $status !== 200 ) {
            error_log( "[Plinkly] API Error - Status: $status" );
            continue;
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( empty( $data['license_key'] ) ) {
            error_log( "[Plinkly] API returned no license: $body" );
            continue;
        }

        $license_key = sanitize_text_field( $data['license_key'] );
        update_user_meta( $user_id, $meta_key, $license_key );

        // إرسال البريد
        do_action(
    'plinkly/send_license_email',
    $license_key,                                   // المتغير 1
    ucfirst( str_replace( '_', ' ', $plan ) ),      // المتغير 2
    $expires_at,                                    // المتغير 3
    $email                                          // المتغير 4
);
    }
}
