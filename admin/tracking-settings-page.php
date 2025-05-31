<?php
/**
 * PlinkLy – Tracking Settings Page
 * --------------------------------------------------------------------------
 * Lets the site-owner enable / disable click-tracking analytics.
 *
 * File: admin/tracking-settings-page.php
 * --------------------------------------------------------------------------
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* -------------------------------------------------------------------------
 * 1) Register option (sanitised as boolean)
 * ---------------------------------------------------------------------- */
add_action( 'admin_init', function () {

	register_setting(
		'plinkly-tracking',              // settings-group
		'plinkly_enable_tracking',       // option name
		[
			'type'              => 'boolean',
			'sanitize_callback' => 'rest_sanitize_boolean',
			'default'           => 1,
		]
	);
} );

/* -------------------------------------------------------------------------
 * 2) Render settings page  (called from admin-menus)
 * ---------------------------------------------------------------------- */
function plinkly_render_tracking_settings_page() {

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Permission denied.', 'plinkly-smart-cta-buttons' ) );
	}

	plinkly_render_header( 'PlinkLy CTA – ' . esc_html__( 'Tracking Settings', 'plinkly-smart-cta-buttons' ) );

	$enabled = (bool) get_option( 'plinkly_enable_tracking', 1 );
	?>
	<div id="plinkly-settings" class="plinkly-settings-container"><!-- ← id مضاف لالتقاط CSS -->
		<form method="post" action="options.php">
			<?php settings_fields( 'plinkly-tracking' ); ?>

			<div class="postbox" style="margin-top:20px;">
				<h2 class="hndle"><?php esc_html_e( 'Tracking Options', 'plinkly-smart-cta-buttons' ); ?></h2>
				<div class="inside">
					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Enable click tracking?', 'plinkly-smart-cta-buttons' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="plinkly_enable_tracking" value="1" <?php checked( $enabled ); ?> />
									<?php esc_html_e( 'Track button clicks for analytics.', 'plinkly-smart-cta-buttons' ); ?>
								</label>
								<p class="description" style="margin-top:8px;">
									<?php esc_html_e( 'When enabled, each CTA click is stored for statistics and CSV export.', 'plinkly-smart-cta-buttons' ); ?>
								</p>
							</td>
						</tr>
					</table>
				</div>
			</div>

			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
