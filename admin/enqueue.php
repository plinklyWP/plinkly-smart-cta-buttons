<?php
// File: admin/enqueue.php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue all PlinkLy admin assets.
 *
 * @param string $hook Current admin screen ID.
 */
function plinkly_admin_enqueue( $hook ) {

	/* =====================================================================
	 * 1) Dashboard + Add-ons (Link-Sentinel, Placement-Tracker)
	 * ===================================================================*/
	$dash_hooks = [
		'toplevel_page_plinkly-cta-dashboard',
		'plinkly-cta_page_plinkly-cta-dashboard',
		'plinkly-cta_page_plinkly-link-sentinel',
		'plinkly-cta_page_plinkly-placement-tracker',
		'plinkly-cta_page_plinkly-easylink-put',
	];

	if ( in_array( $hook, $dash_hooks, true ) ) {

		/* ---------- Chart.js ---------- */
		$chart_path = plugin_dir_path( __DIR__ ) . 'admin/dashboard/assets/js/chart.umd.js';
		wp_enqueue_script(
			'plinkly-chartjs',
			plugin_dir_url( __DIR__ ) . 'admin/dashboard/assets/js/chart.umd.js',
			[],
			file_exists( $chart_path ) ? filemtime( $chart_path ) : false,
			false // load in <head>
		);

		/* ---------- Dashboard core (charts + tables) ---------- */
		$dash_js_path = plugin_dir_path( __DIR__ ) . 'admin/dashboard/assets/js/dashboard.js';
		wp_enqueue_script(
			'plinkly-dashboard-js',
			plugin_dir_url( __DIR__ ) . 'admin/dashboard/assets/js/dashboard.js',
			[ 'jquery', 'plinkly-chartjs' ],
			file_exists( $dash_js_path ) ? filemtime( $dash_js_path ) : false,
			true // footer
		);

		/* ---------- AI dashboard JS ---------- */
		$ai_js_path = plugin_dir_path( __DIR__ ) . 'admin/dashboard/assets/js/ai-dashboard-analytics.js';
		wp_enqueue_script(
			'plinkly-ai-dashboard-js',
			plugin_dir_url( __DIR__ ) . 'admin/dashboard/assets/js/ai-dashboard-analytics.js',
			[ 'jquery', 'plinkly-dashboard-js' ],
			file_exists( $ai_js_path ) ? filemtime( $ai_js_path ) : false,
			true
		);

		wp_localize_script(
			'plinkly-dashboard-js',
			'PlinklyDashboard',
			[
				'nonce'   => wp_create_nonce( 'plinkly_admin' ),
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
			]
		);

		/* ---------- Dashboard / Sentinel / Placement CSS ---------- */
		$dash_css_path = plugin_dir_path( __DIR__ ) . 'admin/dashboard/assets/css/admin-dashboard.css';
		wp_enqueue_style(
			'plinkly-stats-css',
			plugin_dir_url( __DIR__ ) . 'admin/dashboard/assets/css/admin-dashboard.css',
			[],
			file_exists( $dash_css_path ) ? filemtime( $dash_css_path ) : false
		);

		/* ---------- Promo JS & CSS ---------- */
		$promo_js_path  = plugin_dir_path( __DIR__ ) . 'admin/dashboard/assets/js/promo.js';
		$promo_css_path = plugin_dir_path( __DIR__ ) . 'admin/dashboard/assets/css/plinkly-promo.css';

		wp_enqueue_script(
			'plinkly-promo-js',
			plugin_dir_url( __DIR__ ) . 'admin/dashboard/assets/js/promo.js',
			[ 'jquery', 'plinkly-chartjs' ],
			file_exists( $promo_js_path ) ? filemtime( $promo_js_path ) : false,
			true
		);
		wp_enqueue_style(
			'plinkly-promo-css',
			plugin_dir_url( __DIR__ ) . 'admin/dashboard/assets/css/plinkly-promo.css',
			[],
			file_exists( $promo_css_path ) ? filemtime( $promo_css_path ) : false
		);
	}

	/* =====================================================================
	 * 2) Settings / Licensing pages (all plinkly-cta-… screens)
	 * ===================================================================*/
	if (
		strpos( $hook, 'plinkly-smart-cta-buttons' ) !== false ||
		strpos( $hook, 'plinkly-cta-' )            !== false ||
		strpos( $hook, 'plinkly-cta_' )            !== false   // ← NEW: يشمل placement-tracker
	) {

		wp_enqueue_script( 'jquery-ui-tabs' );

		$ui_css_path = plugin_dir_path( __DIR__ ) . 'assets/css/jquery-ui.css';
		wp_enqueue_style(
			'plinkly-jquery-ui-css',
			plugin_dir_url( __DIR__ ) . 'assets/css/jquery-ui.css',
			[],
			file_exists( $ui_css_path ) ? filemtime( $ui_css_path ) : false
		);

		$settings_css_path = plugin_dir_path( __DIR__ ) . 'assets/css/settings-style.css';
		wp_enqueue_style(
			'plinkly-settings-css',
			plugin_dir_url( __DIR__ ) . 'assets/css/settings-style.css',
			[],
			file_exists( $settings_css_path ) ? filemtime( $settings_css_path ) : false
		);

		wp_add_inline_script(
			'jquery-ui-tabs',
			"jQuery(function($){ $('#plinkly-tabs').tabs(); });"
		);
	}
}
add_action( 'admin_enqueue_scripts', 'plinkly_admin_enqueue' );
