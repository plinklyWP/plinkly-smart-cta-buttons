<?php
/**
 * PlinkLy – Elementor widget loader
 * -------------------------------------------------------------
 * Registers the “CTA Button” widget and loads its JS helpers
 * داخل محرّر Elementor فقط.
 *
 * File: includes/register-elementor.php
 * -------------------------------------------------------------
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* -----------------------------------------------------------------
 * 0. Resolve plugin root once
 * ----------------------------------------------------------------- */
$plinkly_root_path = plugin_dir_path( __DIR__ );      // …/plinkly/
$plinkly_root_url  = plugins_url( '/',  __DIR__ );

/* -----------------------------------------------------------------
 * 1. Register the widget class
 * ----------------------------------------------------------------- */
add_action( 'elementor/widgets/register', function ( $widgets_manager ) use ( $plinkly_root_path ) {

	$widget_file = $plinkly_root_path . 'elementor/plinkly-cta-widget.php';

	if ( file_exists( $widget_file ) ) {
		require_once $widget_file;
		$widgets_manager->register( new \PlinkLy_CTA_Elementor_Widget() );
	} else {
		error_log( '[PlinkLy] Elementor widget PHP file missing: ' . $widget_file );
	}
}, 10, 1 );

/* -----------------------------------------------------------------
 * 2. Enqueue editor-only JS helper
 * ----------------------------------------------------------------- */
add_action( 'elementor/editor/after_enqueue_scripts', function () use ( $plinkly_root_path, $plinkly_root_url ) {

	$js_rel  = 'elementor/plinkly-cta-widget.js';
	$js_path = $plinkly_root_path . $js_rel;
	$js_url  = $plinkly_root_url  . $js_rel;

	if ( ! file_exists( $js_path ) ) {
		error_log( '[PlinkLy] Elementor widget JS missing: ' . $js_path );
		return;
	}

	wp_enqueue_script(
		'plinkly-cta-widget',
		$js_url,
		[ 'jquery', 'elementor-editor' ],
		filemtime( $js_path ),
		true
	);

	/* ---- Localised runtime settings ---- */
	$license_key = get_option( 'plinkly_pro_license_key', '' );
	$hmac_secret = get_option( 'plinkly_hmac_secret', '' );

	wp_localize_script( 'plinkly-cta-widget', 'PlinkLyConfig', [
		'aiProxyUrl' => apply_filters( 'plinkly_ai_proxy_endpoint', PLINKLY_AI_PROXY_ENDPOINT ),
		'proxyToken' => defined( 'PLYCTA_PROXY_API_KEY' ) ? PLYCTA_PROXY_API_KEY : '',
		'licenseKey' => $license_key,
		'hmacSecret' => $hmac_secret,
		'nonce'      => wp_create_nonce( 'plinkly_nonce' ),
	] );
} );
