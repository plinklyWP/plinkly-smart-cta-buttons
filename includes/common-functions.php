<?php
// File: includes/common-functions.php

if ( ! function_exists( 'plinkly_is_pro_active' ) ) {
	/**
	 * Returns TRUE only if the license is valid *and* the plan is not Free.
	 */
	function plinkly_is_pro_active(): bool {
    $status = get_option( 'plinkly_license_status', 'invalid' );
    $plan   = get_option( 'plinkly_license_plan', 'free' );

    // Treat both "success" and "valid" as a valid activation
    $is_active = in_array( $status, [ 'success', 'valid' ], true )
                 && ! in_array( $plan, [ '', 'free' ], true );

    //error_log( "[plinkly] status=$status, plan=$plan, active=" . ( $is_active ? '1' : '0' ) );
    return $is_active;
}
}

