<?php
// File: admin/notices.php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Display admin notices for various PlinkLy pages.
 */
function plinkly_admin_notices() {
    // After saving settings
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

    // Permanent notice on the license page
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
}
add_action( 'admin_notices', 'plinkly_admin_notices' );
