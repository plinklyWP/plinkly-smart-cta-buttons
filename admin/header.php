<?php
// File: admin/header.php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Get the URL to the PlinkLy logo.
 *
 * @return string
 */
function plinkly_get_logo_url() {
    return plugin_dir_url( __DIR__ ) . 'assets/img/plinkly-logo.png';
}

/**
 * Get the URL to upgrade to PRO.
 *
 * @return string
 */
function plinkly_get_upgrade_url() {
    return 'https://plink.ly/#price-box';
}

/**
 * Get the URL to support page.
 *
 * @return string
 */
function plinkly_get_support_url() {
    return 'https://plink.ly/support/';
}

/**
 * Render the plugin header for admin pages.
 *
 * @param string $page_title The title to display.
 */
function plinkly_render_header( $page_title = 'PlinkLy CTA' ) {
    ?>
    <div class="plinkly-header">
        <div class="plinkly-header-top">
            <div class="plinkly-header-left">
                <img src="<?php echo esc_url( plinkly_get_logo_url() ); ?>"
                     alt="<?php esc_attr_e( 'PlinkLy Logo', 'plinkly-smart-cta-buttons' ); ?>"
                     class="plinkly-logo">
            </div>
            <div class="plinkly-header-right">
                <?php if ( ! plinkly_is_pro_active() ) : ?>
                    <div class="plinkly-upgrade-wrapper">
                        <a href="<?php echo esc_url( plinkly_get_upgrade_url() ); ?>"
                           class="plinkly-upgrade-button"
                           target="_blank">
                            🚀 <?php esc_html_e( 'Upgrade to PRO', 'plinkly-smart-cta-buttons' ); ?>
                        </a>
                        <span class="plinkly-badge">20% OFF</span>
                    </div>
                <?php endif; ?>
                <a href="<?php echo esc_url( plinkly_get_support_url() ); ?>"
                   class="plinkly-support-link pulse"
                   target="_blank"
                   title="<?php esc_attr_e( 'Support', 'plinkly-smart-cta-buttons' ); ?>">
                    🛟
                </a>
            </div>
        </div>
        <div class="plinkly-header-bottom">
            <h2 class="plinkly-page-title"><?php echo esc_html( $page_title ); ?></h2>
        </div>
    </div>
    <?php
}
