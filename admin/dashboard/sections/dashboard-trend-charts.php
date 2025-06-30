<?php
// File: admin/dashboard/sections/dashboard-trend-charts.php
// Renders summary cards + charts (supports A/B variant filter)

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ── read active variant filter (if any) ──────────────────*/
$filter_variant = isset( $_GET['filter_variant'] ) ? strtoupper( sanitize_key( $_GET['filter_variant'] ) ) : '';
if ( ! in_array( $filter_variant, [ 'A', 'B' ], true ) ) {
	$filter_variant = '';
}

/* ── titles for charts (already variant-aware counts) ─────*/
$top_post_titles_full  = array_map( fn ( $r ) => get_the_title( $r->post_id ), $top_posts );
$top_post_titles_short = array_map( function ( $r ) {
	$t = get_the_title( $r->post_id );
	return mb_strlen( $t ) > 18 ? mb_substr( $t, 0, 18 ) . '…' : $t;
}, $top_posts );

$platform_names_full  = array_map( fn ( $r ) => ucfirst( $r->platform ), $platform_counts );
$platform_names_short = array_map( function ( $r ) {
	$n = ucfirst( $r->platform );
	return mb_strlen( $n ) > 12 ? mb_substr( $n, 0, 12 ) . '…' : $n;
}, $platform_counts );

/* helper to keep variant param in links */
$variant_param = $filter_variant ? '&filter_variant=' . rawurlencode( $filter_variant ) : '';
?>

<?php
/* ---------- Smart Insight Notice ---------- */
$data = array_merge(
	[
		'weekly_change' => $weekly_change,
	],
	$plinkly_smart_insight
);

Plinkly_Insights::render_notice( $data );
/* ---------- end Smart Insight Notice ---------- */
?>

<div class="additional-cards-row">
	<div class="dashboard-column dashboard-column-left">

		<!-- Click summary table -->
		<div class="card click-summary-card">
			<h3>
				<?php
				esc_html_e( 'Click Summary', 'plinkly-smart-cta-buttons' );
				if ( $filter_variant ) {
					/* translators: %s is the variant name (e.g., 'A', 'B') */
					echo ' – ' . esc_html( sprintf( __( 'Variant %s', 'plinkly' ), $filter_variant ) );
				}
				?>
			</h3>

			<table class="widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Time', 'plinkly-smart-cta-buttons' ); ?></th>
						<th><?php esc_html_e( 'Clicks', 'plinkly-smart-cta-buttons' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					$periods = [
						esc_html__( 'Today', 'plinkly-smart-cta-buttons' )      => [ gmdate( 'Y-m-d' ), gmdate( 'Y-m-d' ), $period_counts['today'] ],
						esc_html__( 'Yesterday', 'plinkly-smart-cta-buttons' )  => [ gmdate( 'Y-m-d', strtotime( '-1 day' ) ), gmdate( 'Y-m-d', strtotime( '-1 day' ) ), $period_counts['yesterday'] ],
						esc_html__( 'This Month', 'plinkly-smart-cta-buttons' ) => [ gmdate( 'Y-m-01' ), gmdate( 'Y-m-t' ), $period_counts['this_month'] ],
						esc_html__( 'Last Month', 'plinkly-smart-cta-buttons' ) => [
							gmdate( 'Y-m-01', strtotime( 'first day of last month' ) ),
							gmdate( 'Y-m-t', strtotime( 'last day of last month' ) ),
							$period_counts['last_month'],
						],
						esc_html__( 'Last 7 Days', 'plinkly-smart-cta-buttons' )  => [ gmdate( 'Y-m-d', strtotime( '-7 day' ) ), gmdate( 'Y-m-d' ), $period_counts['last_7'] ],
						esc_html__( 'Last 30 Days', 'plinkly-smart-cta-buttons' ) => [ gmdate( 'Y-m-d', strtotime( '-30 day' ) ), gmdate( 'Y-m-d' ), $period_counts['last_30'] ],
						esc_html__( 'Last 60 Days', 'plinkly-smart-cta-buttons' ) => [ gmdate( 'Y-m-d', strtotime( '-60 day' ) ), gmdate( 'Y-m-d' ), $period_counts['last_60'] ],
						esc_html__( 'Last 90 Days', 'plinkly-smart-cta-buttons' ) => [ gmdate( 'Y-m-d', strtotime( '-90 day' ) ), gmdate( 'Y-m-d' ), $period_counts['last_90'] ],
						esc_html__( 'This Year', 'plinkly-smart-cta-buttons' )   => [ gmdate( 'Y-01-01' ), gmdate( 'Y-m-d' ), $period_counts['this_year'] ],
						esc_html__( 'Last Year', 'plinkly-smart-cta-buttons' )   => [
							gmdate( 'Y-01-01', strtotime( 'last year' ) ),
							gmdate( 'Y-12-31', strtotime( 'last year' ) ),
							$period_counts['last_year'],
						],
						esc_html__( 'Total', 'plinkly-smart-cta-buttons' )       => [ '', '', $total_clicks ],
					];

					foreach ( $periods as $label => $data ) {
						[ $from, $to, $count ] = $data;

						echo '<tr><td>' . esc_html( $label ) . '</td><td>';

						if ( $from && $to ) {
							$url = admin_url(
								'admin.php?page=plinkly-cta-dashboard' .
								'&filter_from=' . rawurlencode( $from ) .
								'&filter_to='   . rawurlencode( $to ) .
								$variant_param .
								'#detailed-data-section'
							);
							echo '<a href="' . esc_url( $url ) . '">' . esc_html( number_format_i18n( $count ) ) . '</a>';
						} else {
							echo esc_html( number_format_i18n( $count ) );
						}

						echo '</td></tr>';
					}
					?>
				</tbody>
			</table>
		</div><!-- /summary card -->
	</div><!-- /left column -->

	<div class="dashboard-column dashboard-column-right">

		<!-- Daily trend -->
		<div class="card daily-click-trend-card">
			<div class="daily-click-trend-header">
				<h3><?php esc_html_e( 'Daily Click Trend', 'plinkly-smart-cta-buttons' ); ?></h3>
				<select id="chartPeriodSelect">
					<option value="daily"><?php esc_html_e( 'Daily', 'plinkly-smart-cta-buttons' ); ?></option>
					<option value="weekly"><?php esc_html_e( 'Weekly', 'plinkly-smart-cta-buttons' ); ?></option>
					<option value="monthly"><?php esc_html_e( 'Monthly', 'plinkly-smart-cta-buttons' ); ?></option>
				</select>
			</div>

			<?php if ( empty( $daily_counts ) ) : ?>
				<p class="description"><?php esc_html_e( 'No trend data available yet.', 'plinkly-smart-cta-buttons' ); ?></p>
			<?php else : ?>
				<canvas id="dailyClickTrendChart" style="max-height:350px;"></canvas>
			<?php endif; ?>
		</div>

		<!-- Doughnuts -->
		<div class="charts-card">
			<div class="top-posts card">
				<h3><?php esc_html_e( 'Top Posts by Clicks', 'plinkly-smart-cta-buttons' ); ?></h3>

				<?php if ( empty( $top_posts ) ) : ?>
					<p class="description"><?php esc_html_e( 'No post data available yet.', 'plinkly-smart-cta-buttons' ); ?></p>
				<?php else : ?>
					<canvas id="plinkly_top_posts_chart" style="max-height:350px;"></canvas>
				<?php endif; ?>
			</div>

			<div class="top-platform card">
				<h3><?php esc_html_e( 'Top Platforms', 'plinkly-smart-cta-buttons' ); ?></h3>

				<?php if ( empty( $platform_counts ) ) : ?>
					<p class="description"><?php esc_html_e( 'No platform data available yet.', 'plinkly-smart-cta-buttons' ); ?></p>
				<?php else : ?>
					<canvas id="plinkly_platform_chart" style="max-height:350px;"></canvas>
				<?php endif; ?>
			</div>
		</div><!-- /charts-card -->
	</div><!-- /right column -->
</div><!-- /row -->
