<?php
// File: includes/ajax-track.php


if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Track clicks via AJAX.
 */
function plinkly_track_click() {
    check_ajax_referer( 'plinkly_click_nonce', 'nonce' );

    if ( ! isset( $_POST['post_id'], $_POST['text'], $_POST['link'], $_POST['platform'] ) ) {
        wp_send_json_error( 'Missing parameters' );
    }

    global $wpdb;
    $table = $wpdb->prefix . 'plinkly_clicks';

    $wpdb->insert( $table, [
        'post_id'     => intval( $_POST['post_id'] ),
        'button_text' => sanitize_text_field( $_POST['text'] ),
        'button_link' => esc_url_raw( $_POST['link'] ),
        'platform'    => sanitize_text_field( $_POST['platform'] ),
        'clicked_at'  => current_time( 'mysql' ),
    ] );

    wp_send_json_success( 'Click tracked' );
}

/**
 * Register AJAX hooks.
 */
add_action( 'wp_ajax_plinkly_track_click', 'plinkly_track_click' );
add_action( 'wp_ajax_nopriv_plinkly_track_click', 'plinkly_track_click' );

