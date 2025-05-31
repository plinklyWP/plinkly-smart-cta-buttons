<?php
// File: uninstall.php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Remove all options
delete_option( 'plinkly_enable_tracking' );
delete_option( 'plinkly_pro_license_key' );
delete_option( 'plinkly_license_status' );
delete_option( 'plinkly_license_expiry' );
delete_option( 'plinkly_license_quota' );
delete_option( 'plinkly_last_license_check' );
delete_option( 'plinkly_default_color' );
delete_option( 'plinkly_force_update_old_buttons' );
delete_option( 'plinkly_force_change_always' );

// Remove transient cache (if any)
delete_transient( 'plinkly_company_cache' );

// Remove plugin tables
global $wpdb;
$imp_table = $wpdb->prefix . 'plinkly_impressions';
$clicks_table = $wpdb->prefix . 'plinkly_clicks';

$wpdb->query( "DROP TABLE IF EXISTS $imp_table" );
$wpdb->query( "DROP TABLE IF EXISTS $clicks_table" );

// لو أضفت بيانات meta مستقبلاً أضف الحذف هنا
