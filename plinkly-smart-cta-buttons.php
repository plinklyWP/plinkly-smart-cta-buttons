<?php
/**
 * Plugin Name: Plinkly – Smart CTA Buttons
 * Plugin URI:  https://plink.ly
 * Description: AI-driven CTA button generator with analytics.
 * Version:     1.0.1
 * Author:      PlinkLy Team
 * Author URI:  
 * License:     GPLv2 or later
 * Text Domain: plinkly-smart-cta-buttons
 */

// -----------------------------------------------------------------------------
// Prevent direct access
// -----------------------------------------------------------------------------
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// -----------------------------------------------------------------------------
// Common constants
// -----------------------------------------------------------------------------
define( 'PLYCTA_PATH', plugin_dir_path( __FILE__ ) );
define( 'PLYCTA_URL',  plugin_dir_url(  __FILE__ ) );

// ➡️  ثابت إضافى لتسهيل مسار القوالب داخل كلاس البريد
if ( ! defined( 'PLINKLY_PATH' ) ) {
	define( 'PLINKLY_PATH', PLYCTA_PATH );
}

/**
 * Simple loader helper that logs any error to debug.log without stopping execution.
 *
 * @param string $relative_path Relative path to the plugin folder
 */
function plycta_safe_require( string $relative_path ) {
	$path = PLYCTA_PATH . ltrim( $relative_path, '/' );
	if ( file_exists( $path ) ) {
		require_once $path;
	} else {
		error_log( '[PlinkLy] Missing include: ' . $relative_path );
	}
}

// -----------------------------------------------------------------------------
// Load order (constants ← license ← other files)
// -----------------------------------------------------------------------------
plycta_safe_require( 'includes/common-functions.php' );
// 1) Constants and general settings
plycta_safe_require( 'includes/constants.php' );

// 2) Activation/Deactivation – must be loaded before register_*_hook
plycta_safe_require( 'includes/activation.php' );


// 3) Licensing system (defines plinkly_is_pro_active() and others)
plycta_safe_require( 'includes/license.php' );

// 4) Files dependent on license or already loaded constants
plycta_safe_require( 'includes/render-block.php' );
plycta_safe_require( 'includes/register-blocks.php' );

// 5) General functions
plycta_safe_require( 'includes/company-data.php' );
plycta_safe_require( 'includes/ajax-track.php' );
plycta_safe_require( 'includes/ajax-click-handler.php' );
plycta_safe_require( 'includes/scripts.php' );
plycta_safe_require( 'includes/affiliate-params.php' );
plycta_safe_require( 'includes/register-elementor.php' );

// -----------------------------------------------------------------------------
// Admin Area (Dashboard)
// -----------------------------------------------------------------------------
if ( is_admin() ) {
	plycta_safe_require( 'admin/admin-menus.php' );
	plycta_safe_require( 'admin/license-page.php' );
	plycta_safe_require( 'admin/tracking-settings-page.php' );
	plycta_safe_require( 'admin/general-settings-page.php' );
	plycta_safe_require( 'admin/general-settings-extra.php' );
	plycta_safe_require( 'admin/admin-assets.php' );
	plycta_safe_require( 'admin/dashboard-page.php' );

	// AJAX handlers for the admin dashboard
	plycta_safe_require( 'admin/ajax-handlers.php' );
}

// -----------------------------------------------------------------------------
// Cache support: clear on changing any option starting with plinkly_
// -----------------------------------------------------------------------------
add_action(
	'updated_option',
	function ( $option, $old, $new ) {
		if ( 0 === strpos( $option, 'plinkly_' ) ) {
			plinkly_clear_all_caches();
		}
	},
	100,
	3
);

/**
 * Clear popular cache plugins if present
 */
function plinkly_clear_all_caches() {
	if ( class_exists( 'LiteSpeed_Cache_API' ) ) {
		LiteSpeed_Cache_API::purge_all();
	}
	if ( function_exists( 'rocket_clean_domain' ) ) {
		rocket_clean_domain();
	}
	if ( function_exists( 'w3tc_flush_all' ) ) {
		w3tc_flush_all();
	}
	if ( function_exists( 'wp_cache_clear_cache' ) ) {
		wp_cache_clear_cache();
	}
	wp_cache_flush();
}


add_action( 'plugins_loaded', function() {
    load_plugin_textdomain( 'plinkly-smart-cta-buttons', false, dirname( plugin_basename(__FILE__) ) . '/languages/' );
});
// -----------------------------------------------------------------------------
// Activation/Deactivation Hooks
// -----------------------------------------------------------------------------
register_activation_hook(   __FILE__, 'plinkly_activate_plugin'    );
register_deactivation_hook( __FILE__, 'plinkly_deactivate_plugin'  );