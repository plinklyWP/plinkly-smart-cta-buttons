<?php
/**
 * PlinkLy – Company Data Helper
 * --------------------------------------------------------------------------
 * Returns an array of known companies with their brand-color + logo URL.
 * Data is fetched once a day (transient) from a remote JSON endpoint and
 * cached separately for the FREE / PRO user depending on licence key.
 *
 * File: includes/company-data.php
 * --------------------------------------------------------------------------
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'plinkly_get_company_data' ) ) :

	function plinkly_get_company_data( $force_refresh = false ) {

		/* ───── 1) Resolve licence + cache-key ───── */
		$key     = get_option( 'plinkly_pro_license_key', '' );
		$is_pro  = function_exists( 'plinkly_is_pro_active' ) && plinkly_is_pro_active();

		$cache_key = 'plinkly_company_' . md5( $is_pro && $key ? $key : 'free' );

		/* ───── 2) Try cached data unless forced ───── */
		if ( ! $force_refresh && ! get_option( 'plinkly_force_fetch_company_data' ) ) {
			$cached = get_transient( $cache_key );
			if ( is_array( $cached ) ) {
				return apply_filters( 'plinkly_company_data', $cached );
			}
		} else {
			delete_transient( $cache_key );
		}

		/* ───── 3) Build endpoint URL with all needed params ───── */
		$endpoint = defined( 'PLINKLY_COMPANY_ENDPOINT' )
			? PLINKLY_COMPANY_ENDPOINT
			: 'https://api.plink.ly/buttons.php'; // مسار سكربت buttons.php البعيد

		// إذا النسخة Pro ومفتاح موجود، ضم key + site_url + plugin_ver
		if ( $is_pro && $key ) {
			$endpoint = add_query_arg( [
				'key'        => rawurlencode( $key ),
				'site_url'   => rawurlencode( home_url() ),
				'plugin_ver' => rawurlencode( PLINKLY_PLUGIN_VERSION ), // عرفته في ملف plinkly.php
			], $endpoint );
		}

		/* ───── 4) Remote request ───── */
		$args = [
			'timeout'   => 8,
			'sslverify' => true,
		];
		$response = wp_remote_get( esc_url_raw( $endpoint ), $args );

		if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
			error_log( '[PlinkLy] Company-data fetch failed: ' . wp_remote_retrieve_response_message( $response ) );
			return [];
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		/* ───── 5) Validate + normalise ───── */
		if ( ! is_array( $data ) ) {
			error_log( '[PlinkLy] Company-data JSON malformed.' );
			return [];
		}

		$normalised = array_change_key_case( $data, CASE_LOWER );

		/* ───── 6) Cache for 24h & return ───── */
		set_transient( $cache_key, $normalised, DAY_IN_SECONDS );

		return apply_filters( 'plinkly_company_data', $normalised );
	}

endif;
