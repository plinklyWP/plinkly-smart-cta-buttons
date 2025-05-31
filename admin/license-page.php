<?php
/**
 * File: admin/license-page.php
 * PlinkLy CTA Activation Interface
 *
 * • Sends the payload as JSON to comply with validate.php
 * • Relies on status values: success / invalid / expired
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ───────── 1. تسجيل الخيارات ───────── */
add_action( 'admin_init', function() {
	register_setting( 'plinkly-license-group', 'plinkly_pro_license_key', [
		'type' => 'string',
		'sanitize_callback' => 'sanitize_text_field',
	] );
	register_setting( 'plinkly-license-group', 'plinkly_license_key', [
		'type' => 'string',
		'sanitize_callback' => 'sanitize_text_field',
	] );
	register_setting( 'plinkly-license-group', 'plinkly_hmac_secret', [
		'type' => 'string',
		'sanitize_callback' => 'sanitize_text_field',
	] );
	register_setting( 'plinkly-license-group', 'plinkly_license_status', [
		'type' => 'string',
		'sanitize_callback' => 'sanitize_text_field',
	] );
} );

/* ───────── 2. Custom POST handler ───────── */
add_action( 'admin_post_plinkly_save_license', 'plinkly_save_license_cb' );

function plinkly_save_license_cb() {

	check_admin_referer( 'plinkly_save_license' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'No permission', 'plinkly-smart-cta-buttons' ) );
	}

	$key = sanitize_text_field( $_POST['plinkly_pro_license_key'] ?? '' );

	/* Save the key in both options (old + new) */
	update_option( 'plinkly_pro_license_key', $key );
	update_option( 'plinkly_license_key',     $key );

	/* If the key is deleted ▸ reset the data */
	if ( $key === '' ) {
		update_option( 'plinkly_license_status', 'invalid' );
		update_option( 'plinkly_hmac_secret',    '' );
		wp_safe_redirect( wp_get_referer() );
		exit;
	}

	/* ─── 2‑A. Set headers ─── */
	$headers = [
		'Content-Type' => 'application/json; charset=utf-8',
	];

	if ( defined( 'PLINKLY_API_SECRET' ) && PLINKLY_API_SECRET !== '' ) {
		$headers['Authorization'] = 'Bearer ' . PLINKLY_API_SECRET;
	}

	/* ─── 2‑B. Call validate.php ─── */
	$response = wp_remote_post(
		PLINKLY_LICENSE_VALIDATE_ENDPOINT,
		[
			'timeout' => 15,
			'headers' => $headers,
			'body'    => wp_json_encode( [
				'key'      => $key,
				'site_url' => home_url(),
			] ),
		]
	);

	$status = 'invalid';
	$secret = '';

	if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
		$body   = json_decode( wp_remote_retrieve_body( $response ), true );
		$status = $body['status'] ?? 'invalid';

		if ( 'success' === $status ) {
			$secret = sanitize_text_field( $body['hmac_secret'] ?? '' );
		}
	}

	update_option( 'plinkly_license_status', $status );
	update_option( 'plinkly_hmac_secret',    $secret );

	/* إشعار للمستخدم */
	add_settings_error(
		'plinkly-license',
		'license',
		( 'success' === $status )
			? esc_html__( 'License activated successfully.', 'plinkly-smart-cta-buttons' )
			: esc_html__( 'License verification failed.',    'plinkly-smart-cta-buttons' ),
		( 'success' === $status ) ? 'updated' : 'error'
	);

	wp_safe_redirect( wp_get_referer() );
	exit;
}

/* ───────── 3. License Page Interface ───────── */
function plinkly_render_license_page() {

	/** Remove unwanted notices then display the saved ones */
	if ( function_exists( 'get_current_screen' ) ) {
		$screen = get_current_screen();
		if ( $screen ) {
			remove_all_actions( 'admin_notices' );
			do_action( 'admin_notices' );
		}
	}

	plinkly_render_header( 'PlinkLy CTA – License' );

	$stored_key = get_option( 'plinkly_pro_license_key', '' );
	$status     = get_option( 'plinkly_license_status', 'invalid' );
	?>
	<div id="plinkly-settings" class="plinkly-settings-container">
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="plinkly_save_license">
			<?php wp_nonce_field( 'plinkly_save_license' ); ?>

			<div class="postbox" style="margin-top:20px;">
				<h2 class="hndle"><?php esc_html_e( 'License Activation', 'plinkly-smart-cta-buttons' ); ?></h2>
				<div class="inside">
					<table class="form-table">
						<tr valign="top">
							<th scope="row"><?php esc_html_e( 'License Key', 'plinkly-smart-cta-buttons' ); ?></th>
							<td>
								<input type="text" name="plinkly_pro_license_key"
									   value="<?php echo esc_attr( $stored_key ); ?>"
									   class="regular-text" />

								<p class="description" style="margin-top:8px;">
									<?php
									echo ( 'success' === $status )
										? '<span style="color:green;font-weight:bold;">&#10004; ' . esc_html__( 'Active', 'plinkly-smart-cta-buttons' ) . '</span>'
										: '<span style="color:red;font-weight:bold;">&#10006; ' . esc_html__( 'Inactive', 'plinkly-smart-cta-buttons' ) . '</span>';
									?>
								</p>
							</td>
						</tr>
					</table>
				</div>
			</div>

			<?php submit_button( esc_html__( 'Save & Validate License', 'plinkly-smart-cta-buttons' ) ); ?>
		</form>
	</div>
	<?php
}
