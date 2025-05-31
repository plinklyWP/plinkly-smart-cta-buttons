<?php
/**
 * PlinkLy – Assets Loader
 * -------------------------------------------------------------
 * • Loads the shared stylesheet (front-end + block editor)
 * • Loads tracker.js on the public site if tracking is enabled
 *
 * File location: /includes/scripts.php
 * -------------------------------------------------------------
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 1) Front-end:            wp_enqueue_scripts           (priority 20)
 * 2) Block-editor canvas:  enqueue_block_editor_assets  (priority 20)
 *
 * Both hooks call the same function; WordPress handles duplicates.
 */
add_action( 'wp_enqueue_scripts',        'plinkly_enqueue_assets', 20 );
add_action( 'enqueue_block_editor_assets','plinkly_enqueue_assets', 20 );

/**
 * Enqueue CSS for everyone, JS only for visitors.
 */
function plinkly_enqueue_assets() {

	/* -----------------------------------------------------------------
	 * A. Shared stylesheet  (style.css)
	 * ----------------------------------------------------------------- */
	$css_rel  = '../assets/css/style.css';                       // relative to this file
	$css_path = plugin_dir_path( __FILE__ ) . $css_rel;          // absolute path on disk
	$css_url  = plugins_url(        $css_rel, __FILE__ );        // public URL

	if ( file_exists( $css_path ) ) {
		wp_enqueue_style(
			'plinkly-style',
			$css_url,
			[],
			filemtime( $css_path )             // cache-busting on file change
		);

		/* optional: RTL support */
		wp_style_add_data( 'plinkly-style', 'rtl', 'replace' );
	} else {
		// يظهر فى debug.log إذا كان WP_DEBUG_LOG مفعَّلًا
		error_log( '[PlinkLy] style.css not found: ' . $css_path );
	}

	/* -----------------------------------------------------------------
	 * B. Click-tracking script – front-end only
	 * ----------------------------------------------------------------- */
	if ( current_filter() === 'wp_enqueue_scripts' && ! is_admin() && get_option( 'plinkly_enable_tracking' ) ) {

		$js_rel  = '../assets/js/tracker.js';
		$js_path = plugin_dir_path( __FILE__ ) . $js_rel;
		$js_url  = plugins_url(        $js_rel, __FILE__ );

		if ( file_exists( $js_path ) ) {

			wp_enqueue_script(
				'plinkly-tracker',
				$js_url,
				[ 'jquery' ],
				filemtime( $js_path ),
				true                                  // load in footer
			);

			wp_localize_script(
				'plinkly-tracker',
				'PlinkLyTracker',
				[
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'post_id'  => is_singular() ? get_the_ID() : 0,
					'nonce'    => wp_create_nonce( 'plinkly_click_nonce' ),
				]
			);

		} else {
			error_log( '[PlinkLy] tracker.js not found: ' . $js_path );
		}
	}

}
