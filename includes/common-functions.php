<?php
// File: includes/common-functions.php

if ( ! function_exists( 'plinkly_is_pro_active' ) ) {
	/**
	 * Returns TRUE only إذا كان المفتاح صالحًا والخطّة ليست Free.
	 */
	function plinkly_is_pro_active() : bool {

		$status = get_option( 'plinkly_license_status', 'invalid' );
		if ( ! in_array( $status, [ 'valid', 'success' ], true ) ) {
			return false;
		}

		$plan = get_option( 'plinkly_license_plan', 'free' );
		return ! in_array( $plan, [ '', 'free' ], true );
		error_log('[plinkly] status='. $status .', plan='. $plan );
	}
}
