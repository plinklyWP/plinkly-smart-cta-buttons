<?php
/**
 * PlinkLy – Admin-side menus
 * -------------------------------------------------------------
 * Creates the Dashboard + sub-pages in wp-admin.
 *
 * File: admin/admin-menus.php
 * -------------------------------------------------------------
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* -----------------------------------------------------------------
 * 1. Hook (priority 12 to appear after Settings / WooCommerce)
 * ----------------------------------------------------------------- */
add_action( 'admin_menu', 'plinkly_register_admin_menus', 12 );

/**
 * Builds the PlinkLy CTA menu tree.
 *
 * ┌  PlinkLy CTA  (top-level)
 * │   ├ Dashboard         – capability: view_plinkly_pro_stats
 * │   ├ License           – capability: manage_options
 * │   ├ Tracking Settings – capability: manage_options
 * │   └ General Settings  – capability: manage_options
 * └───────────────────────────────────────────────────────────────
 */
function plinkly_register_admin_menus() {

	$parent_slug    = 'plinkly-cta-dashboard';          // used by children
	$menu_icon      = 'dashicons-chart-bar';            // WP Dashicon
	$menu_position  = 25;                               // after Comments

	/* ─── Top-level: Dashboard ─── */
	add_menu_page(
		__( 'PlinkLy CTA', 'plinkly-smart-cta-buttons' ),              // page-title
		__( 'PlinkLy CTA', 'plinkly-smart-cta-buttons' ),              // menu-label
		'view_plinkly_pro_stats',                        // capability
		$parent_slug,                                    // slug
		'plinkly_render_dashboard',                      // callback
		$menu_icon,
		$menu_position
	);

	/* ─── Sub: License ─── */
	add_submenu_page(
		$parent_slug,
		__( 'License', 'plinkly-smart-cta-buttons' ),
		__( 'License', 'plinkly-smart-cta-buttons' ),
		'manage_options',
		'plinkly-cta-license',
		'plinkly_render_license_page'
	);

	/* ─── Sub: Tracking settings ─── */
	add_submenu_page(
		$parent_slug,
		__( 'Tracking Settings', 'plinkly-smart-cta-buttons' ),
		__( 'Tracking Settings', 'plinkly-smart-cta-buttons' ),
		'manage_options',
		'plinkly-cta-tracking',
		'plinkly_render_tracking_settings_page'
	);

	/* ─── Sub: General settings ─── */
	add_submenu_page(
		$parent_slug,
		__( 'General Settings', 'plinkly-smart-cta-buttons' ),
		__( 'General Settings', 'plinkly-smart-cta-buttons' ),
		'manage_options',
		'plinkly-cta-general',
		'plinkly_render_general_settings_page'
	);
}

/* -----------------------------------------------------------------
 * 2. Optional – make the top-level label highlight “Dashboard”
 *    (WordPress otherwise highlights first submenu)
 * ----------------------------------------------------------------- */
add_filter( 'parent_file', function ( $parent_file ) {

	$screen = get_current_screen();
	if ( isset( $screen->id ) && strpos( $screen->id, 'plinkly-smart-cta-buttons' ) === 0 ) {
		$parent_file = 'plinkly-cta-dashboard';
	}
	return $parent_file;
} );
