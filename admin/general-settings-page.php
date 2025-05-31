<?php
/**
 * PlinkLy – General Settings Page
 * Registers and renders all default-style options for CTA buttons.
 *
 * File: admin/general-settings-page.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* -------------------------------------------------------------------------
 * 1. Register options
 * ---------------------------------------------------------------------- */
add_action( 'admin_init', function () {

	$opts = [
		'plinkly_default_color'           => 'sanitize_hex_color',
		'plinkly_default_new_tab'         => 'intval',
		'plinkly_default_nofollow'        => 'intval',

		'plinkly_default_border_style'    => 'sanitize_text_field',
		'plinkly_default_border_width'    => 'absint',
		'plinkly_default_border_color'    => 'sanitize_hex_color',
		'plinkly_default_border_radius'   => 'absint',

		'plinkly_default_shadow_offset_x' => 'intval',
		'plinkly_default_shadow_offset_y' => 'intval',
		'plinkly_default_shadow_blur'     => 'absint',
		'plinkly_default_shadow_color'    => 'sanitize_hex_color',

		'plinkly_default_gap_horizontal'  => 'absint',
		'plinkly_default_gap_vertical'    => 'absint',
		'plinkly_default_font_family'     => 'sanitize_text_field',
		'plinkly_default_font_weight'     => 'sanitize_text_field',
		'plinkly_default_line_height'     => 'sanitize_text_field',

		'plinkly_force_update_old_buttons' => 'intval',
		'plinkly_force_change_always'      => 'intval',
		'plinkly_force_fetch_company_data' => 'intval',

		'plinkly_amazon_tag'   => 'sanitize_text_field',
		'plinkly_ebay_campid'  => 'sanitize_text_field',
		'plinkly_aliexpress_aff' => 'sanitize_text_field',
	];

	foreach ( $opts as $name => $sanitize_cb ) {
		register_setting(
			'plinkly-general-settings',
			$name,
			[ 'type' => 'string', 'sanitize_callback' => $sanitize_cb ]
		);
	}
} );

/* -------------------------------------------------------------------------
 * 2. Render page
 * ---------------------------------------------------------------------- */
function plinkly_render_general_settings_page() {

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Access denied.', 'plinkly-smart-cta-buttons' ) );
	}

	plinkly_render_header( 'PlinkLy CTA – ' . esc_html__( 'Settings', 'plinkly-smart-cta-buttons' ) );
	?>
	<div id="plinkly-settings" class="plinkly-settings-container">
		<form method="post" action="options.php">
			<?php settings_fields( 'plinkly-general-settings' ); ?>

			<div id="plinkly-tabs">
				<ul>
					<li><a href="#tab-basic"><?php esc_html_e( 'Basic', 'plinkly-smart-cta-buttons' ); ?></a></li>
					<li><a href="#tab-style"><?php esc_html_e( 'Style', 'plinkly-smart-cta-buttons' ); ?></a></li>
					<li><a href="#tab-force"><?php esc_html_e( 'Force Options', 'plinkly-smart-cta-buttons' ); ?></a></li>
					<li><a href="#tab-tags"><?php esc_html_e( 'Tags', 'plinkly-smart-cta-buttons' ); ?></a></li>
				</ul>

				<!-- TAB 1: Basic -->
				<div id="tab-basic">
					<?php plinkly_settings_box( [
						[
							'label' => esc_html__( 'Default Button Color', 'plinkly-smart-cta-buttons' ),
							'field' => 'plinkly_default_color',
							'type'  => 'color',
						],
						[
							'label' => esc_html__( 'Open in New Tab by Default?', 'plinkly-smart-cta-buttons' ),
							'field' => 'plinkly_default_new_tab',
							'type'  => 'checkbox',
							'desc'  => esc_html__( 'Yes, open links in a new tab', 'plinkly-smart-cta-buttons' ),
						],
						[
							'label' => esc_html__( 'Add nofollow to links?', 'plinkly-smart-cta-buttons' ),
							'field' => 'plinkly_default_nofollow',
							'type'  => 'checkbox',
							'desc'  => esc_html__( 'Yes, add rel="nofollow sponsored" to links', 'plinkly-smart-cta-buttons' ),
						],
					] ); ?>
				</div>

				<!-- TAB 2: Style -->
				<div id="tab-style">
					<?php
					$select = function ( $name, $choices ) {
						$val = get_option( $name, '' );
						echo '<select name="' . esc_attr( $name ) . '">';
						foreach ( $choices as $k => $label ) {
							echo '<option value="' . esc_attr( $k ) . '"' . selected( $val, $k, false ) . '>' . esc_html( $label ) . '</option>';
						}
						echo '</select>';
					};

					plinkly_settings_box( [

						[
							'label'  => esc_html__( 'Border Style', 'plinkly-smart-cta-buttons' ),
							'custom' => $select,
							'args'   => [ 'plinkly_default_border_style',
								[ 'none'=>'none','solid'=>'solid','dotted'=>'dotted','dashed'=>'dashed' ] ],
						],

						[ 'label'=>esc_html__('Border Width (px)', 'plinkly-smart-cta-buttons'),  'field'=>'plinkly_default_border_width',  'type'=>'number','min'=>0,'max'=>10 ],
						[ 'label'=>esc_html__('Border Color',      'plinkly-smart-cta-buttons'),  'field'=>'plinkly_default_border_color',  'type'=>'color' ],
						[ 'label'=>esc_html__('Border Radius (px)','plinkly-smart-cta-buttons'),  'field'=>'plinkly_default_border_radius', 'type'=>'number','min'=>0,'max'=>50 ],

						[ 'label'=>esc_html__('Shadow Offset X', 'plinkly-smart-cta-buttons'), 'field'=>'plinkly_default_shadow_offset_x','type'=>'number','min'=>-20,'max'=>20 ],
						[ 'label'=>esc_html__('Shadow Offset Y', 'plinkly-smart-cta-buttons'), 'field'=>'plinkly_default_shadow_offset_y','type'=>'number','min'=>-20,'max'=>20 ],
						[ 'label'=>esc_html__('Shadow Blur',     'plinkly-smart-cta-buttons'), 'field'=>'plinkly_default_shadow_blur',     'type'=>'number','min'=>0,'max'=>20 ],
						[ 'label'=>esc_html__('Shadow Color',    'plinkly-smart-cta-buttons'), 'field'=>'plinkly_default_shadow_color',   'type'=>'color' ],

						[ 'label'=>esc_html__('Horizontal Gap', 'plinkly-smart-cta-buttons'), 'field'=>'plinkly_default_gap_horizontal','type'=>'number','min'=>0,'max'=>100 ],
						[ 'label'=>esc_html__('Vertical Gap',   'plinkly-smart-cta-buttons'), 'field'=>'plinkly_default_gap_vertical','type'=>'number','min'=>0,'max'=>100 ],

						[
							'custom'=>$select,
							'args'=>[ 'plinkly_default_font_family',
								[ ''=>'Default','Arial'=>'Arial','Georgia'=>'Georgia','Tahoma'=>'Tahoma','"Times New Roman"'=>'Times New Roman','Verdana'=>'Verdana' ] ],
							'label'=>esc_html__( 'Font Family', 'plinkly-smart-cta-buttons' ),
						],
						[
							'custom'=>$select,
							'args'=>[ 'plinkly_default_font_weight',
								[ ''=>'Default','400'=>'400 – Normal','500'=>'500','600'=>'600','700'=>'700 – Bold','900'=>'900' ] ],
							'label'=>esc_html__( 'Font Weight', 'plinkly-smart-cta-buttons' ),
						],
						[ 'label'=>esc_html__('Line-Height', 'plinkly-smart-cta-buttons'), 'field'=>'plinkly_default_line_height', 'type'=>'number','step'=>'0.1','min'=>1,'max'=>3 ],
					] );
					?>
				</div>

				<!-- TAB 3: Force Options -->
				<div id="tab-force">
					<?php plinkly_settings_box( [
						[
							'label'=>esc_html__( 'Force Update Old Buttons', 'plinkly-smart-cta-buttons' ),
							'field'=>'plinkly_force_update_old_buttons',
							'type'=>'checkbox',
							'desc'=>'<span style="color:red;">'.esc_html__( 'Apply defaults to all existing buttons.', 'plinkly-smart-cta-buttons' ).'</span>',
						],
						[
							'label'=>esc_html__( 'Force Change Always', 'plinkly-smart-cta-buttons' ),
							'field'=>'plinkly_force_change_always',
							'type'=>'checkbox',
							'desc'=>'<span style="color:red;">'.esc_html__( 'Override all button styles.', 'plinkly-smart-cta-buttons' ).'</span>',
						],
						[
							'label'=>esc_html__( 'Force Fetch Company Data', 'plinkly-smart-cta-buttons' ),
							'field'=>'plinkly_force_fetch_company_data',
							'type'=>'checkbox',
							'desc'=>esc_html__( 'Bypass cache and always fetch latest company data.', 'plinkly-smart-cta-buttons' ),
						],
					] ); ?>
				</div>

				<!-- TAB 4: Affiliate Tags -->
				<div id="tab-tags">
					<?php plinkly_settings_box( [
						[ 'label'=>esc_html__('Amazon Affiliate Tag',   'plinkly-smart-cta-buttons'), 'field'=>'plinkly_amazon_tag','type'=>'text' ],
						[ 'label'=>esc_html__('eBay Campaign ID',      'plinkly-smart-cta-buttons'), 'field'=>'plinkly_ebay_campid','type'=>'text' ],
						[ 'label'=>esc_html__('AliExpress Aff Params', 'plinkly-smart-cta-buttons'), 'field'=>'plinkly_aliexpress_aff','type'=>'text' ],
					] ); ?>
				</div>
			</div>

			<?php submit_button(); ?>
		</form>
	</div>
<?php
}

/* -------------------------------------------------------------------------
 * 3. Helper – settings box
 * ---------------------------------------------------------------------- */
function plinkly_settings_box( array $rows ) {

	echo '<div class="postbox"><div class="inside"><table class="form-table">';

	foreach ( $rows as $row ) {

		echo '<tr valign="top"><th>';

		if ( isset( $row['label'] ) ) {
			echo ( $row['label'] === strip_tags( $row['label'] ) )
				? esc_html( $row['label'] )
				: wp_kses_post( $row['label'] );
		}

		echo '</th><td>';

		/* custom renderer ------------------------------------------------- */
		if ( isset( $row['custom'] ) && is_callable( $row['custom'] ) ) {

			call_user_func_array( $row['custom'], $row['args'] ?? [] );

		} else {

			$field = $row['field'];
			$type  = $row['type'] ?? 'text';
			$val   = get_option( $field, '' );

			switch ( $type ) {

				case 'color':
					echo '<input type="color" name="' . esc_attr( $field ) . '" value="' . esc_attr( $val ) . '">';
					break;

				case 'checkbox':
					echo '<label><input type="checkbox" name="' . esc_attr( $field ) . '" value="1"' .
					     checked( 1, (int) $val, false ) . '> ' .
					     ( isset( $row['desc'] )
					        ? ( $row['desc'] === strip_tags( $row['desc'] )
					            ? esc_html( $row['desc'] )
					            : wp_kses_post( $row['desc'] ) )
					        : '' ) .
					     '</label>';
					break;

				case 'number':
					echo '<input type="number" name="' . esc_attr( $field ) . '" value="' . esc_attr( $val ) . '"';
					if ( isset( $row['min'] ) )  echo ' min="'  . (int) $row['min']  . '"';
					if ( isset( $row['max'] ) )  echo ' max="'  . (int) $row['max']  . '"';
					if ( isset( $row['step'] ) ) echo ' step="' . esc_attr( $row['step'] ) . '"';
					echo '>';
					break;

				default: // text
					echo '<input type="text" name="' . esc_attr( $field ) . '" value="' . esc_attr( $val ) . '">';
			}
		}

		echo '</td></tr>';
	}

	echo '</table></div></div>';
}
