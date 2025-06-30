<?php
// Summary cards – EasyLink clicks only.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;
$table = $wpdb->prefix . 'plinkly_easylink_clicks';

/* إجمالي النقرات */
$total_clicks = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );

/* نقرات اليوم */
$today_clicks = (int) $wpdb->get_var(
	$wpdb->prepare(
		"SELECT COUNT(*) FROM {$table} WHERE DATE(clicked_at) = %s",
		gmdate( 'Y-m-d' )
	)
);
?>
<div class="plinkly-summary" style="margin-top:24px">
	<div class="plinkly-summary">
		<div class="plinkly-summary-item plinkly-item-total-clicks">
			<h3><?php echo esc_html( number_format_i18n( $total_clicks ) ); ?></h3>
			<p><?php esc_html_e( 'Total Clicks', 'plinkly-smart-cta-buttons' ); ?></p>
		</div>
		<div class="plinkly-summary-item plinkly-item-today-clicks">
			<h3><?php echo esc_html( number_format_i18n( $today_clicks ) ); ?></h3>
			<p><?php esc_html_e( 'Today Clicks', 'plinkly-smart-cta-buttons' ); ?></p>
		</div>
	</div>
</div>
