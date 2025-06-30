<?php
/**
 * Plinkly – Smart Insights Helper
 *
 * Generates a single “smart notice” based on analytics data
 * and renders it as a reusable component.
 *
 * PHP ≥ 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Plinkly_Insights {

	/**
	 * Pick one notice from a weighted pool.
	 *
	 * @param array $data  Collected dashboard stats.
	 * @return array [ 'html' => string, 'prio' => int ]
	 */
	public static function generate_notice( array $data ): array {
		$candidates = [];

		/* --- 1) Weekly change ------------------------------------ */
		if ( isset( $data['weekly_change'] ) ) {
			$delta = (int) $data['weekly_change'];

			if ( $delta > 5 ) {
				$candidates[] = [
					'html' => sprintf(
						'📈 <strong>%s</strong> +%d%%',
						__( 'Clicks are up this week', 'plinkly-smart-cta-buttons' ),
						$delta
					),
					'prio' => 80,
				];
			} elseif ( $delta < -5 ) {
				$candidates[] = [
					'html' => sprintf(
						'📉 <strong>%s</strong> %d%%',
						__( 'Clicks dropped this week', 'plinkly-smart-cta-buttons' ),
						abs( $delta )
					),
					'prio' => 80,
				];
			}
		}

		/* --- 2) Top-performing post ------------------------------ */
		if ( ! empty( $data['top_post'] ) ) {
			$post = $data['top_post'];
			$candidates[] = [
				'html' => sprintf(
					'🔥 <strong>%s</strong> “%s” (%d %s)',
					__( 'Trending post:', 'plinkly-smart-cta-buttons' ),
					esc_html( $post['title'] ),
					(int) $post['clicks'],
					__( 'clicks', 'plinkly-smart-cta-buttons' )
				),
				'prio' => 60,
			];
		}

		/* --- 3) A/B winner -------------------------------------- */
		if ( ! empty( $data['ab_winner'] ) ) {
			$w = $data['ab_winner'];
			$candidates[] = [
				'html' => sprintf(
					'🏆 <strong>%s</strong> +%d%% CTR',
					sprintf(
						/* translators: %s is the name of the winning variant, e.g., 'A' or 'B' */
						__( 'Variant %s wins', 'plinkly-smart-cta-buttons' ),
						esc_html( $w['variant'] )
					),
					(int) $w['lift']
				),
				'prio' => 70,
			];
		}

		/* --- 4) Peak hour --------------------------------------- */
		if ( ! empty( $data['best_hour'] ) ) {
			$candidates[] = [
				'html' => sprintf(
					'⏰ <strong>%s</strong> %02d:00 — %s',
					__( 'Peak hour:', 'plinkly-smart-cta-buttons' ),
					(int) $data['best_hour'],
					__( 'schedule posts then!', 'plinkly-smart-cta-buttons' )
				),
				'prio' => 40,
			];
		}

		/* --- choose one (weighted random) ----------------------- */
		if ( ! $candidates ) {
			return [ 'html' => '', 'prio' => 0 ];
		}

		usort( $candidates, fn ( $a, $b ) => $b['prio'] <=> $a['prio'] );
		$weights = array_column( $candidates, 'prio' );

		$index = self::weighted_random( $weights );
		return $candidates[ $index ] ?? [ 'html' => '', 'prio' => 0 ];
	}

	/**
	 * Render the notice component.
	 *
	 * @param array $data  Same array you passed to generate_notice().
	 */
	public static function render_notice( array $data ): void {
		$notice = self::generate_notice( $data );
		if ( empty( $notice['html'] ) ) {
			return;
		}

		// let the template handle markup
		include PLINKLY_PATH . 'admin/components/notice-smart.php';
	}

	/* ───────────────────────────────────────────────────────── */

	private static function weighted_random( array $weights ): int {
		$sum  = array_sum( $weights );
		$rand = wp_rand( 1, (int) $sum );
		foreach ( $weights as $i => $w ) {
			$rand -= $w;
			if ( $rand <= 0 ) {
				return (int) $i;
			}
		}
		return 0;
	}
}
