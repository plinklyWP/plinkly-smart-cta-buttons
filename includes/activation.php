<?php
/**
 * PlinkLy – Activation / Deactivation
 * -------------------------------------------------------------
 * Creates DB tables, sets defaults, schedules cron,
 * and assigns required capabilities.
 *
 * File: includes/activation.php
 * -------------------------------------------------------------
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* -------------------------------------------------------------------------
 * Register hooks only once when the main plugin file is loaded
 * ------------------------------------------------------------------------- */
register_activation_hook( PLINKLY_PLUGIN_FILE, 'plinkly_activate_plugin' );
register_deactivation_hook( PLINKLY_PLUGIN_FILE, 'plinkly_deactivate_plugin' );

/* -------------------------------------------------------------------------
 * 1. Plugin activation
 * ------------------------------------------------------------------------- */
function plinkly_activate_plugin() {

	global $wpdb;

	/* 1-a) Create / update tables */
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$charset = $wpdb->get_charset_collate();

	$imp_table = $wpdb->prefix . 'plinkly_impressions';
	$clicks_table = $wpdb->prefix . 'plinkly_clicks';

	// Impressions
	dbDelta( "
		CREATE TABLE {$imp_table} (
			id           BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			post_id      BIGINT(20) UNSIGNED NOT NULL,
			button_link  TEXT,
			showed_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY post_id  (post_id)
		) {$charset};
	" );

	// Clicks
	dbDelta( "
		CREATE TABLE {$clicks_table} (
			id           BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			post_id      BIGINT(20) UNSIGNED,
			button_text  VARCHAR(255),
			button_link  TEXT,
			platform     VARCHAR(100),
			clicked_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY post_id  (post_id),
			KEY platform (platform(20))
		) {$charset};
	" );

	/* 1-b) Default options (autoload = yes) */
	add_option( 'plinkly_default_color',      '#3498db' );
	add_option( 'plinkly_enable_tracking',    1 );
	add_option( 'plinkly_license_status',     'invalid' );
	add_option( 'plinkly_last_license_check', 0 );

	/* 1-c) Schedule daily cron (license validation) */
	if ( ! wp_next_scheduled( PLINKLY_CRON_HOOK ) ) {
		wp_schedule_event( time() + 300, 'daily', PLINKLY_CRON_HOOK ); // +5min safety
	}

	/* 1-d) Capabilities */
	$admin = get_role( 'administrator' );
	if ( $admin && ! $admin->has_cap( 'view_plinkly_pro_stats' ) ) {
		$admin->add_cap( 'view_plinkly_pro_stats' );
	}
}

/* -------------------------------------------------------------------------
 * 2. Plugin deactivation
 * ------------------------------------------------------------------------- */
function plinkly_deactivate_plugin() {

	/* 2-a) Clear scheduled cron */
	wp_clear_scheduled_hook( PLINKLY_CRON_HOOK );

	/* 2-b) Remove capability */
	$admin = get_role( 'administrator' );
	if ( $admin && $admin->has_cap( 'view_plinkly_pro_stats' ) ) {
		$admin->remove_cap( 'view_plinkly_pro_stats' );
	}
}
