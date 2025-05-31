<?php
// File: admin/enqueue.php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Enqueue scripts & styles for Dashboard, Settings, Chart.js, jQuery UI Tabs.
 *
 * @param string $hook The current admin page.
 */
function plinkly_admin_enqueue( $hook ) {
    // Dashboard pages: load Chart.js, dashboard.js and dashboard CSS
    $dash_hooks = [
        'toplevel_page_plinkly-cta-dashboard',
        'plinkly-cta_page_plinkly-cta-dashboard',
    ];
    if ( in_array( $hook, $dash_hooks, true ) ) {
        // Chart.js
        wp_enqueue_script(
            'plinkly-chartjs',
            plugin_dir_url( __DIR__ ) . 'admin/dashboard/assets/js/chart.umd.js',
            [],
            file_exists( plugin_dir_path( __DIR__ ) . 'admin/dashboard/assets/js/chart.umd.js' ) ? filemtime( plugin_dir_path( __DIR__ ) . 'admin/dashboard/assets/js/chart.umd.js' ) : false,
            false 
            );

        // Dashboard JS
        $js_path = plugin_dir_path( __DIR__ ) . 'admin/dashboard/assets/js/dashboard.js';
        wp_enqueue_script(
            'plinkly-dashboard-js',
            plugin_dir_url( __DIR__ ) . 'admin/dashboard/assets/js/dashboard.js',
            ['jquery', 'plinkly-chartjs'],
            file_exists($js_path) ? filemtime($js_path) : false,
            true
        );


        // Dashboard styles
        $css_path = plugin_dir_path( __DIR__ ) . 'assets/css/admin-dashboard.css';
        wp_enqueue_style(
            'plinkly-stats-css',
            plugin_dir_url( __DIR__ ) . 'assets/css/admin-dashboard.css',
            [],
            file_exists( $css_path ) ? filemtime( $css_path ) : false
        );
    }

    // Any PlinkLy admin page: load jQuery UI Tabs + settings CSS
    if (strpos($hook, 'plinkly-smart-cta-buttons') !== false || strpos($hook, 'plinkly-cta-') !== false) {
        wp_enqueue_script( 'jquery-ui-tabs' );

        $jquery_ui_css_path = plugin_dir_path( __DIR__ ) . 'assets/css/jquery-ui.css';
        wp_enqueue_style(
            'plinkly-jquery-ui-css',
            plugin_dir_url( __DIR__ ) . 'assets/css/jquery-ui.css',
            [],
            file_exists($jquery_ui_css_path) ? filemtime($jquery_ui_css_path) : false
            );

        $settings_css_path = plugin_dir_path( __DIR__ ) . 'assets/css/settings-style.css';
        wp_enqueue_style(
            'plinkly-settings-css',
            plugin_dir_url( __DIR__ ) . 'assets/css/settings-style.css',
            [],
            file_exists( $settings_css_path ) ? filemtime( $settings_css_path ) : false
        );

        wp_add_inline_script( 'jquery-ui-tabs', "
        jQuery(function($){
            $('#plinkly-tabs').tabs();
        });
    " );
    }
}
add_action( 'admin_enqueue_scripts', 'plinkly_admin_enqueue' );
