<?php
/**
 * PlinkLy – Core constants
 * -------------------------------------------------------------
 * This file declares every constant used across the plugin.
 * All values are overridable from wp-config.php or a µ-plugin
 * simply by `define()`-ing them first, or via WordPress filters
 * where indicated.
 *
 * File: includes/constants.php
 * -------------------------------------------------------------
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* -----------------------------------------------------------------
 * 0. Helpers
 * ----------------------------------------------------------------- */
/**
 * Convenience wrapper – mirrors `getenv()` then `constant()`.
 *
 * @param string $name    constant/env name (e.g. PLINKLY_API_SECRET)
 * @param mixed  $default fallback value if nothing is set
 */
function plinkly_env( $name, $default = '' ) {
	// phpcs:ignore WordPress.PHP.DiscouragedFunctions
	if ( defined( $name ) )           return constant( $name );
	if ( false !== getenv( $name ) )  return getenv( $name );
	return $default;
}

/* -----------------------------------------------------------------
 * 1. Plugin meta
 * ----------------------------------------------------------------- */
if ( ! defined( 'PLINKLY_PLUGIN_VERSION' ) ) {
	define( 'PLINKLY_PLUGIN_VERSION', '1.0.1' );           // bumped on every release
}

if ( ! defined( 'PLINKLY_PLUGIN_FILE' ) ) {               // used by register_*_hook()
	// ❗️ Adjust path if you move main file elsewhere
	define( 'PLINKLY_PLUGIN_FILE', dirname( __DIR__ ) . '/plinkly.php' );
}

/* -----------------------------------------------------------------
 * 2. Secrets & keys  (NEVER commit real values)
 * ----------------------------------------------------------------- */
if ( ! defined( 'PLINKLY_API_SECRET' ) ) {
	define(
		'PLINKLY_API_SECRET',
		plinkly_env( 'PLINKLY_API_SECRET', '' )             // override via env or wp-config
	);
}

if ( ! defined( 'PLYCTA_PROXY_API_KEY' ) ) {
	define(
		'PLYCTA_PROXY_API_KEY',
		plinkly_env( 'PLYCTA_PROXY_API_KEY', 'e3a1f5c7d9b2a4e6f8c1b3d5a7e9f0a2' )                  // kept empty in repo
	);
}

/* -----------------------------------------------------------------
 * 3. Remote endpoints (filterable)
 * ----------------------------------------------------------------- */
if ( ! defined( 'PLINKLY_LICENSE_VALIDATE_ENDPOINT' ) ) {
	define(
		'PLINKLY_LICENSE_VALIDATE_ENDPOINT',
		apply_filters(
			'plinkly_validate_endpoint',
			'https://api.plink.ly/license/validate.php'
		)
	);
}

if ( ! defined( 'PLINKLY_LICENSE_CREATE_ENDPOINT' ) ) {
	define(
		'PLINKLY_LICENSE_CREATE_ENDPOINT',
		apply_filters(
			'plinkly_create_key_endpoint',
			'https://api.plink.ly/api/create-key.php'
		)
	);
}

if ( ! defined( 'PLINKLY_COMPANY_ENDPOINT' ) ) {
	define(
		'PLINKLY_COMPANY_ENDPOINT',
		apply_filters(
			'plinkly_company_endpoint',
			'https://api.plink.ly/buttons.php'
		)
	);
}

if ( ! defined( 'PLINKLY_AI_PROXY_ENDPOINT' ) ) {
	define(
		'PLINKLY_AI_PROXY_ENDPOINT',
		apply_filters(
			'plinkly_ai_proxy_endpoint',
			'https://api.plink.ly/api/ai-proxy.php'
		)
	);
}

/* -----------------------------------------------------------------
 * 4. Cron hook name
 * ----------------------------------------------------------------- */
if ( ! defined( 'PLINKLY_CRON_HOOK' ) ) {
	define( 'PLINKLY_CRON_HOOK', 'plinkly_daily_license_validation' );
}
