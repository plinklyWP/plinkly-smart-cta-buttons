<?php
// File: admin/dashboard/sections/dashboard-summary.php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<div class="wrap">
	<div class="plinkly-summary">
		<div class="plinkly-summary-card-1">
			<div class="plinkly-summary-item plinkly-item-total-clicks">
				<h3><?php echo esc_html( number_format_i18n( (int) $total_clicks ) ); ?></h3>
				<p><?php esc_html_e( 'Total Clicks', 'plinkly-smart-cta-buttons' ); ?></p>
			</div>
			<div class="plinkly-summary-item plinkly-item-today-clicks">
				<h3><?php echo esc_html( number_format_i18n( (int) $period_counts[ 'today' ] ) ); ?></h3>
				<p><?php esc_html_e( 'Today Clicks', 'plinkly-smart-cta-buttons' ); ?></p>
			</div>
		</div>

		<div class="plinkly-summary-card-2">
			<div class="plinkly-summary-item plinkly-item-avg-clicks">
				<h3><?php echo esc_html( number_format_i18n( (int) $avg_clicks ) ); ?></h3>
				<p><?php esc_html_e( 'Average Clicks per Day', 'plinkly-smart-cta-buttons' ); ?></p>
			</div>

			<div class="plinkly-summary-item plinkly-item-top-link">
				<h3>
					<?php
					if ( $top_link && $top_link->button_link ) {
						$full   = $top_link->button_link;
						$parsed = wp_parse_url( $full );
						$host   = $parsed['host'] ?? '';
						$path   = $parsed['path'] ?? '';
						$short  = $host . ( $path && $path !== '/' ? '…' : '' );

						echo '<a href="' . esc_url( $full ) . '" target="_blank" title="' . esc_attr( $full ) . '" style="text-decoration: underline dotted; cursor: pointer; color: #003F91; word-break: break-all;">' . esc_html( $short ) . '</a>';
					} else {
						esc_html_e( 'N/A', 'plinkly-smart-cta-buttons' );
					}
					?>
				</h3>
				<p><?php esc_html_e( 'Top Clicked Link', 'plinkly-smart-cta-buttons' ); ?></p>
			</div>

			<div class="plinkly-summary-item plinkly-item-top-platform">
				<h3>
					<?php
					if ( $top_platform ) {
						echo esc_html( ucfirst( $top_platform ) );
					} else {
						echo esc_html__( 'N/A', 'plinkly-smart-cta-buttons' );
					}
					?>
				</h3>
				<p><?php esc_html_e( 'Top Platform', 'plinkly-smart-cta-buttons' ); ?></p>
			</div>
		</div>
	</div>
</div>
