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
require_once __DIR__ . '/defaults.php';

/* -------------------------------------------------------------------------
 * Hooks
 * ------------------------------------------------------------------------- */
register_activation_hook( PLINKLY_PLUGIN_FILE, 'plinkly_activate_plugin' );
register_deactivation_hook( PLINKLY_PLUGIN_FILE, 'plinkly_deactivate_plugin' );

/* -------------------------------------------------------------------------
 * 1. Plugin activation
 * ------------------------------------------------------------------------- */
function plinkly_activate_plugin() {

	global $wpdb;

	/* 1-a) Create / update tables ─────────────────────────*/
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$charset      = $wpdb->get_charset_collate();
	$imp_table    = $wpdb->prefix . 'plinkly_impressions';
	$clicks_table = $wpdb->prefix . 'plinkly_clicks';

	/* impressions */
	dbDelta( "
		CREATE TABLE {$imp_table} (
			id           BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			post_id      BIGINT(20) UNSIGNED NOT NULL,
			button_link  TEXT,
			showed_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			variant      CHAR(1)      NOT NULL DEFAULT 'A',
			PRIMARY KEY  (id),
			KEY post_id  (post_id),
			KEY var_post (post_id, variant)
		) {$charset};
	" );

	/* clicks */
	dbDelta( "
		CREATE TABLE {$clicks_table} (
			id           BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			post_id      BIGINT(20) UNSIGNED,
			button_text  VARCHAR(255),
			button_link  TEXT,
			platform     VARCHAR(100),
			clicked_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			variant      CHAR(1)      NOT NULL DEFAULT 'A',
			PRIMARY KEY  (id),
			KEY post_id  (post_id),
			KEY var_post (post_id, variant),
			KEY platform (platform(20))
		) {$charset};
	" );

	/* 1-b) Legacy installs (ALTER if column missing) ──────*/
	$imp_cols   = $wpdb->get_col( "DESC $imp_table",    0 );
	$click_cols = $wpdb->get_col( "DESC $clicks_table", 0 );

	if ( ! in_array( 'variant', $imp_cols, true ) ) {
		$wpdb->query( "ALTER TABLE $imp_table
		               ADD COLUMN variant CHAR(1) NOT NULL DEFAULT 'A' AFTER showed_at,
		               ADD KEY var_post (post_id, variant)" );
	}
	if ( ! in_array( 'variant', $click_cols, true ) ) {
		$wpdb->query( "ALTER TABLE $clicks_table
		               ADD COLUMN variant CHAR(1) NOT NULL DEFAULT 'A' AFTER clicked_at,
		               ADD KEY var_post (post_id, variant)" );
	}

	/* 1-c) Default options (autoload = yes) ───────────────*/
	foreach ( PLINKLY_DEFAULTS as $name => $val ) {
	    add_option( $name, $val, '', 'no' );  
	    }
	/* 1-d) Daily cron (license validation) ────────────────*/
	if ( ! wp_next_scheduled( PLINKLY_CRON_HOOK ) ) {
		wp_schedule_event( time() + 300, 'daily', PLINKLY_CRON_HOOK ); // +5 min safety
	}

	/* 1-e) Capability for Pro stats ───────────────────────*/
	$admin = get_role( 'administrator' );
	if ( $admin && ! $admin->has_cap( 'view_plinkly_pro_stats' ) ) {
		$admin->add_cap( 'view_plinkly_pro_stats' );
	}

	/* 1-f) Track DB schema version ────────────────────────*/
	update_option( 'plinkly_db_version', '1.1' );
}

/* -------------------------------------------------------------------------
 * 2. Plugin deactivation
 * ------------------------------------------------------------------------- */
function plinkly_deactivate_plugin() {

	/* 2-a) Clear cron */
	wp_clear_scheduled_hook( PLINKLY_CRON_HOOK );

	/* 2-b) Remove capability */
	$admin = get_role( 'administrator' );
	if ( $admin && $admin->has_cap( 'view_plinkly_pro_stats' ) ) {
		$admin->remove_cap( 'view_plinkly_pro_stats' );
	}
}

/* -------------------------------------------------------------------------
 * 3. Runtime DB upgrade (executes once per site if needed)
 * ------------------------------------------------------------------------- */
add_action( 'plugins_loaded', 'plinkly_maybe_upgrade_db', 11 );

function plinkly_maybe_upgrade_db() {

	global $wpdb;

	$clicks_table = $wpdb->prefix . 'plinkly_clicks';
	$impr_table   = $wpdb->prefix . 'plinkly_impressions';

	/* clicks: add column + index if missing */
	if ( $wpdb->get_var( "SHOW COLUMNS FROM $clicks_table LIKE 'variant'" ) !== 'variant' ) {
		$wpdb->query( "ALTER TABLE $clicks_table
		               ADD COLUMN variant CHAR(1) NOT NULL DEFAULT 'A' AFTER clicked_at,
		               ADD KEY var_post (post_id, variant)" );
	}

	/* impressions: add column + index if missing */
	if ( $wpdb->get_var( "SHOW COLUMNS FROM $impr_table LIKE 'variant'" ) !== 'variant' ) {
		$wpdb->query( "ALTER TABLE $impr_table
		               ADD COLUMN variant CHAR(1) NOT NULL DEFAULT 'A' AFTER showed_at,
		               ADD KEY var_post (post_id, variant)" );
	}
	
	if ( get_option( 'plinkly_enable_ab_test', null ) === null ) {
        add_option( 'plinkly_enable_ab_test', 0 );   // 0 = معطَّل افتراضيًا
    }
}
