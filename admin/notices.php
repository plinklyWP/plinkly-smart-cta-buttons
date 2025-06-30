<?php
// File: admin/notices.php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Display admin notices for various Plinkly pages.
 */
function plinkly_admin_notices() {

    /* ---------- Success / warning messages after saving settings ---------- */
    if ( ! empty( $_GET['settings-updated'] ) ) {
        $page = sanitize_text_field( $_GET['page'] ?? '' );

        switch ( $page ) {
            case 'plinkly-cta-settings':
                printf(
                    '<div class="notice notice-success is-dismissible plinkly-fade-in"><p><strong>✔️ %s</strong></p></div>',
                    esc_html__( 'General settings saved successfully.', 'plinkly-smart-cta-buttons' )
                );
                break;

            case 'plinkly-cta-privacy':
                printf(
                    '<div class="notice notice-success is-dismissible plinkly-fade-in"><p><strong>✔️ %s</strong></p></div>',
                    esc_html__( 'Privacy & tracking settings saved successfully.', 'plinkly-smart-cta-buttons' )
                );
                break;

            case 'plinkly-cta-license':
                if ( plinkly_is_pro_active() ) {
                    printf(
                        '<div class="notice notice-success is-dismissible plinkly-fade-in"><p><strong>✔️ %s</strong></p></div>',
                        esc_html__( 'License activated successfully.', 'plinkly-smart-cta-buttons' )
                    );
                } else {
                    printf(
                        '<div class="notice notice-warning is-dismissible plinkly-fade-in"><p><strong>⚠️ %s</strong></p></div>',
                        esc_html__( 'License key saved, but activation failed. Please check your key.', 'plinkly-smart-cta-buttons' )
                    );
                }
                break;
        }
    }

    /* ---------- Permanent notice on the license page ---------- */
    if ( ( $_GET['page'] ?? '' ) === 'plinkly-cta-license' ) {
        if ( plinkly_is_pro_active() ) {
            printf(
                '<div class="notice notice-success is-dismissible plinkly-fade-in"><p><strong>✔️ %s</strong></p></div>',
                esc_html__( 'Your license is active. All features are unlocked!', 'plinkly-smart-cta-buttons' )
            );
        } else {
            printf(
                '<div class="notice notice-info notice-info-custom is-dismissible plinkly-fade-in"><p><strong>ℹ️ %s</strong></p></div>',
                esc_html__( 'License is not active. Please enter a valid license key.', 'plinkly-smart-cta-buttons' )
            );
        }
    }

    /* ---------- Dashboard notice when no license key is present ---------- */
    // Change the slug below if your main dashboard page uses a different slug.
    if ( ( $_GET['page'] ?? '' ) === 'plinkly-cta-dashboard' ) {

        // Retrieve the raw license key value (works for both free & Pro).
        $license_key = trim( get_option( 'plinkly_license_key', '' ) );

        if ( empty( $license_key ) ) {
    /* translators: %s is a link to the PLINK.LY website. */
    $message = sprintf(
        esc_html__(
            'You must use an activation key even in the free version to use AI generation. Go to %s.',
            'plinkly-smart-cta-buttons'
        ),
        '<a href="' . esc_url( 'https://plink.ly' ) . '" target="_blank" rel="noopener">PLINK.LY</a>'
    );

    printf(
        '<div class="notice notice-warning is-dismissible plinkly-fade-in"><p><strong>⚠️ %s</strong></p></div>',
        wp_kses_post( $message )
    );
}

    }
}
add_action( 'admin_notices', 'plinkly_admin_notices' );
