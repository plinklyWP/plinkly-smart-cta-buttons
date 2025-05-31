<?php
/**
 * Elementor Widget: PlinkLy CTA Buttons
 * --------------------------------------------------------------------------
 * A repeater-based CTA button group with rich style controls.
 * All dynamic outputs are escaped to satisfy WP-CS “EscapeOutput”.
 */

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Repeater;
use Elementor\Group_Control_Typography;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Prevent direct access
}

class PlinkLy_CTA_Elementor_Widget extends Widget_Base {

	/* ───────────────────────────────
	 * 1. Widget metadata
	 * ───────────────────────────── */
	public function get_name()       { return 'plinkly_cta'; }
	public function get_title()      { return esc_html__( 'PlinkLy CTA Buttons', 'plinkly-smart-cta-buttons' ); }
	public function get_icon()       { return 'eicon-button'; }
	public function get_categories() { return [ 'basic' ]; }

	/* ───────────────────────────────
	 * 2. Register controls
	 * ───────────────────────────── */
	protected function _register_controls() {

		/* ===== Buttons section ===== */
		$this->start_controls_section( 'section_content', [
			'label' => __( 'Buttons', 'plinkly-smart-cta-buttons' ),
		] );

		$repeater = new Repeater();

		/* --- Tabs per button --- */
		$repeater->start_controls_tabs( 'tabs_button_style' );

		/* ▸ Tab: Content */
		$repeater->start_controls_tab( 'tab_content', [
			'label' => __( 'Content', 'plinkly-smart-cta-buttons' ),
		] );

		$repeater->add_control( 'text', [
			'label'       => __( 'Button Text', 'plinkly-smart-cta-buttons' ),
			'type'        => Controls_Manager::TEXT,
			'default'     => 'Buy Now',
			'label_block' => true,
		] );

		$repeater->add_control( 'link', [
			'label'       => __( 'Button Link', 'plinkly-smart-cta-buttons' ),
			'type'        => Controls_Manager::URL,
			'placeholder' => 'https://example.com',
			'label_block' => true,
		] );

		$repeater->add_control( 'open_in_new_tab', [
			'label'        => __( 'Open in New Tab?', 'plinkly-smart-cta-buttons' ),
			'type'         => Controls_Manager::SWITCHER,
			'label_on'     => __( 'Yes', 'plinkly-smart-cta-buttons' ),
			'label_off'    => __( 'No',  'plinkly-smart-cta-buttons' ),
			'return_value' => 'yes',
			'default'      => get_option( 'plinkly_default_new_tab', 1 ) ? 'yes' : '',
		] );

		$repeater->add_control( 'icon', [
			'label'       => __( 'Icon', 'plinkly-smart-cta-buttons' ),
			'type'        => Controls_Manager::ICONS,
			'label_block' => true,
		] );

		$repeater->add_control( 'icon_position', [
			'label'   => __( 'Icon Position', 'plinkly-smart-cta-buttons' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'before_text',
			'options' => [
				'before_logo' => __( 'Before Logo', 'plinkly-smart-cta-buttons' ),
				'after_logo'  => __( 'After Logo',  'plinkly-smart-cta-buttons' ),
				'before_text' => __( 'Before Text', 'plinkly-smart-cta-buttons' ),
				'after_text'  => __( 'After Text',  'plinkly-smart-cta-buttons' ),
			],
		] );

		$repeater->end_controls_tab(); /* tab_content */

		/* ▸ Tab: Style */
		$repeater->start_controls_tab( 'tab_style', [
			'label' => __( 'Style', 'plinkly-smart-cta-buttons' ),
		] );

		$repeater->add_control( 'custom_color', [
			'label'   => __( 'Background Color', 'plinkly-smart-cta-buttons' ),
			'type'    => Controls_Manager::COLOR,
			'default' => get_option( 'plinkly_default_color', '#3498db' ),
		] );

		$repeater->add_control( 'border_style', [
			'label'   => __( 'Border Style', 'plinkly-smart-cta-buttons' ),
			'type'    => Controls_Manager::SELECT,
			'options' => [
				'none'   => __( 'None',   'plinkly-smart-cta-buttons' ),
				'solid'  => __( 'Solid',  'plinkly-smart-cta-buttons' ),
				'dotted' => __( 'Dotted', 'plinkly-smart-cta-buttons' ),
				'dashed' => __( 'Dashed', 'plinkly-smart-cta-buttons' ),
			],
			'default' => get_option( 'plinkly_default_border_style', 'none' ),
		] );

		$repeater->add_control( 'border_width', [
			'label'      => __( 'Border Width (px)', 'plinkly-smart-cta-buttons' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => [ 'px' ],
			'range'      => [ 'px' => [ 'min' => 0, 'max' => 10 ] ],
			'default'    => [ 'size' => intval( get_option( 'plinkly_default_border_width', 0 ) ) ],
		] );

		$repeater->add_control( 'border_color', [
			'label'   => __( 'Border Color', 'plinkly-smart-cta-buttons' ),
			'type'    => Controls_Manager::COLOR,
			'default' => get_option( 'plinkly_default_border_color', '#CCCCCC' ),
		] );

		$repeater->add_control( 'border_radius', [
			'label'      => __( 'Border Radius (px)', 'plinkly-smart-cta-buttons' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => [ 'px' ],
			'range'      => [ 'px' => [ 'min' => 0, 'max' => 50 ] ],
			'default'    => [ 'size' => intval( get_option( 'plinkly_default_border_radius', 5 ) ) ],
		] );

		/* Typography */
		$repeater->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'typography',
				'label'    => __( 'Typography', 'plinkly-smart-cta-buttons' ),
				'selector' => '{{WRAPPER}} {{CURRENT_ITEM}} .plinkly-cta-text',
			]
		);

		$repeater->end_controls_tab(); /* tab_style */

		/* ▸ Tab: Advanced */
		$repeater->start_controls_tab( 'tab_advanced', [
			'label' => __( 'Advanced', 'plinkly-smart-cta-buttons' ),
		] );

		$repeater->add_control( 'shadow_offset_x', [
			'label'   => __( 'Shadow Offset X (px)', 'plinkly-smart-cta-buttons' ),
			'type'    => Controls_Manager::SLIDER,
			'range'   => [ 'px' => [ 'min' => -20, 'max' => 20 ] ],
			'default' => [ 'size' => intval( get_option( 'plinkly_default_shadow_offset_x', 0 ) ) ],
		] );

		$repeater->add_control( 'shadow_offset_y', [
			'label'   => __( 'Shadow Offset Y (px)', 'plinkly-smart-cta-buttons' ),
			'type'    => Controls_Manager::SLIDER,
			'range'   => [ 'px' => [ 'min' => -20, 'max' => 20 ] ],
			'default' => [ 'size' => intval( get_option( 'plinkly_default_shadow_offset_y', 0 ) ) ],
		] );

		$repeater->add_control( 'shadow_blur', [
			'label'   => __( 'Shadow Blur (px)', 'plinkly-smart-cta-buttons' ),
			'type'    => Controls_Manager::SLIDER,
			'range'   => [ 'px' => [ 'min' => 0, 'max' => 20 ] ],
			'default' => [ 'size' => intval( get_option( 'plinkly_default_shadow_blur', 0 ) ) ],
		] );

		$repeater->add_control( 'shadow_color', [
			'label'   => __( 'Shadow Color', 'plinkly-smart-cta-buttons' ),
			'type'    => Controls_Manager::COLOR,
			'default' => get_option( 'plinkly_default_shadow_color', '#000000' ),
		] );

		$repeater->add_control( 'logo_size', [
			'label'   => __( 'Logo Size', 'plinkly-smart-cta-buttons' ),
			'type'    => Controls_Manager::SELECT,
			'options' => [
				'small'  => __( 'Small (20px)',  'plinkly-smart-cta-buttons' ),
				'medium' => __( 'Medium (30px)', 'plinkly-smart-cta-buttons' ),
				'large'  => __( 'Large (40px)',  'plinkly-smart-cta-buttons' ),
			],
			'default' => 'small',
		] );

		$repeater->add_control( 'rel_attribute', [
			'label'   => __( 'Link rel attribute', 'plinkly-smart-cta-buttons' ),
			'type'    => Controls_Manager::SELECT,
			'options' => [
				''                   => __( 'None',               'plinkly-smart-cta-buttons' ),
				'nofollow'           => 'nofollow',
				'sponsored'          => 'sponsored',
				'nofollow sponsored' => 'nofollow sponsored',
			],
			'default' => get_option( 'plinkly_default_nofollow', 1 ) ? 'nofollow sponsored' : '',
		] );

		$repeater->end_controls_tab();  /* tab_advanced */
		$repeater->end_controls_tabs(); /* tabs_button_style */

		$this->add_control( 'buttons', [
			'label'       => __( 'Buttons List', 'plinkly-smart-cta-buttons' ),
			'type'        => Controls_Manager::REPEATER,
			'fields'      => $repeater->get_controls(),
			'default'     => [
				[
					'text'         => 'Buy Now',
					'link'         => [ 'url' => '#' ],
					'custom_color' => get_option( 'plinkly_default_color', '#3498db' ),
				],
			],
			'title_field' => '{{{ text }}}',
		] );

		$this->end_controls_section(); /* /Buttons */

		/* ===== Layout section ===== */
		$this->start_controls_section( 'section_layout', [
			'label' => __( 'Layout', 'plinkly-smart-cta-buttons' ),
		] );

		$this->add_control( 'alignment', [
			'label'   => __( 'Alignment', 'plinkly-smart-cta-buttons' ),
			'type'    => Controls_Manager::CHOOSE,
			'options' => [
				'left'   => [ 'title' => __( 'Left',   'plinkly-smart-cta-buttons' ), 'icon' => 'eicon-text-align-left' ],
				'center' => [ 'title' => __( 'Center', 'plinkly-smart-cta-buttons' ), 'icon' => 'eicon-text-align-center' ],
				'right'  => [ 'title' => __( 'Right',  'plinkly-smart-cta-buttons' ), 'icon' => 'eicon-text-align-right' ],
			],
			'default' => 'left',
			'toggle'  => true,
		] );

		$this->add_control( 'layout', [
			'label'   => __( 'Layout Direction', 'plinkly-smart-cta-buttons' ),
			'type'    => Controls_Manager::SELECT,
			'options' => [
				'horizontal' => __( 'Horizontal', 'plinkly-smart-cta-buttons' ),
				'vertical'   => __( 'Vertical',   'plinkly-smart-cta-buttons' ),
			],
			'default' => 'horizontal',
		] );

		$this->add_responsive_control( 'gap_horizontal', [
			'label'      => __( 'Horizontal Spacing', 'plinkly-smart-cta-buttons' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => [ 'px', 'em', 'rem' ],
			'range'      => [ 'px' => [ 'min' => 0, 'max' => 100 ] ],
			'default'    => [
				'size' => intval( get_option( 'plinkly_default_gap_horizontal', 10 ) ),
				'unit' => 'px',
			],
		] );

		$this->add_responsive_control( 'gap_vertical', [
			'label'      => __( 'Vertical Spacing', 'plinkly-smart-cta-buttons' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => [ 'px', 'em', 'rem' ],
			'range'      => [ 'px' => [ 'min' => 0, 'max' => 100 ] ],
			'default'    => [
				'size' => intval( get_option( 'plinkly_default_gap_vertical', 10 ) ),
				'unit' => 'px',
			],
		] );

		$this->end_controls_section(); /* /Layout */
	}

	/* ───────────────────────────────
	 * 3. Helper: alignment → flex
	 * ───────────────────────────── */
	private function get_justify_content( $align ) {
		if ( 'center' === $align ) {
			return 'center';
		}
		if ( 'right' === $align ) {
			return 'flex-end';
		}
		return 'flex-start';
	}

	/* ───────────────────────────────
	 * 4. Render widget
	 * ───────────────────────────── */
	protected function render() {

		/* Company colors / logos fetched once */
		$company_data = $GLOBALS['PlinkLyCompanyColors']
		             ?? ( function_exists( 'plinkly_get_company_data' ) ? plinkly_get_company_data() : [] );

		$s               = $this->get_settings_for_display();
		$alignment       = $s['alignment'] ?? 'left';
		$layout          = $s['layout']    ?? 'horizontal';
		$flex_dir        = ( 'vertical' === $layout ) ? 'column' : 'row';
		$justify_content = $this->get_justify_content( $alignment );

		$gap_row_val  = $s['gap_vertical']['size']   ?? 10;
		$gap_row_unit = $s['gap_vertical']['unit']   ?? 'px';
		$gap_col_val  = $s['gap_horizontal']['size'] ?? 10;
		$gap_col_unit = $s['gap_horizontal']['unit'] ?? 'px';

		if ( empty( $s['buttons'] ) ) {
			return;
		}

		/* Container */
		echo '<div class="plinkly-cta-buttons" style="display:flex;flex-direction:' .
		     esc_attr( $flex_dir ) .
		     ';flex-wrap:wrap;justify-content:' . esc_attr( $justify_content ) .
		     ';row-gap:' . esc_attr( $gap_row_val . $gap_row_unit ) .
		     ';column-gap:' . esc_attr( $gap_col_val . $gap_col_unit ) .
		     ';text-align:' . esc_attr( $alignment ) . ';">';

		/* Loop buttons */
		foreach ( $s['buttons'] as $btn ) {

			/* Prepare URL */
			$url_raw = $btn['link']['url'] ?? '#';
			if ( $url_raw && ! preg_match( '#^https?://#i', $url_raw ) ) {
				$url_raw = 'https://' . $url_raw;
			}
			$href = esc_url( $url_raw ); // escaped once

			$text     = $btn['text'] ?? esc_html__( 'Buy Now', 'plinkly-smart-cta-buttons' );
			$font     = intval( $btn['typography_font_size']['size'] ?? $btn['font_size']['size'] ?? 16 );
			$target   = ! empty( $btn['open_in_new_tab'] ) && 'yes' === $btn['open_in_new_tab'] ? '_blank' : '';
			$rel_attr = $btn['rel_attribute'] ?? '';

			/* Company logo/color */
			$host   = ( $h = parse_url( $href, PHP_URL_HOST ) ) ? preg_replace( '/^www\./i', '', $h ) : '';
			$remote = $company_data[ $host ] ?? [];
			$logo   = ! empty( $remote['logo'] ) ? esc_url( $remote['logo'] ) : '';
			$color  = $remote['color'] ?? ( $btn['custom_color'] ?? get_option( 'plinkly_default_color', '#3498db' ) );

			/* Logo HTML */
			$logo_html = '';
			if ( $logo ) {
				$sizes    = [ 'small' => 20, 'medium' => 30, 'large' => 40 ];
				$h_px     = $sizes[ $btn['logo_size'] ?? 'small' ] ?? 20;
				$logo_html = '<img src="' . $logo . '" alt="" style="height:' . intval( $h_px ) . 'px;width:auto;margin-right:5px;">';
			}

			/* Icon HTML */
			$icon_html = '';
			if ( ! empty( $btn['icon']['value'] ) ) {
				$icon_html = '<i class="' . esc_attr( $btn['icon']['value'] ) . '" style="font-size:' . intval( $font ) . 'px;margin-right:5px;"></i>';
			}

			/* Border / radius / shadow */
			$bs         = $btn['border_style'] ?? 'none';
			$bw         = intval( $btn['border_width']['size'] ?? 0 );
			$border_css = ( 'none' !== $bs && $bw > 0 )
				? "border:{$bw}px {$bs} " . esc_attr( $btn['border_color'] ) . ';'
				: '';
			$radius_css = 'border-radius:' . intval( $btn['border_radius']['size'] ?? 5 ) . 'px;';

			$sb         = intval( $btn['shadow_blur']['size'] ?? 0 );
			$shadow_css = ( $sb > 0 )
				? 'box-shadow:' .
				  intval( $btn['shadow_offset_x']['size'] ?? 0 ) . 'px ' .
				  intval( $btn['shadow_offset_y']['size'] ?? 0 ) . 'px ' .
				  $sb . 'px ' . esc_attr( $btn['shadow_color'] ) . ';'
				: '';

			$style = 'background-color:' . esc_attr( $color ) . ';padding:10px 20px;' .
			         'display:inline-flex;align-items:center;gap:5px;' .
			         'text-decoration:none;color:#fff;' .
			         $border_css . $radius_css . $shadow_css;

			/* Arrange inner */
			$pos = $btn['icon_position'] ?? 'before_text';
			switch ( $pos ) {
				case 'before_logo':
					$inner = $icon_html . $logo_html . '<span class="plinkly-cta-text">' . esc_html( $text ) . '</span>';
					break;
				case 'after_logo':
					$inner = $logo_html . $icon_html . '<span class="plinkly-cta-text">' . esc_html( $text ) . '</span>';
					break;
				case 'after_text':
					$inner = $logo_html . '<span class="plinkly-cta-text">' . esc_html( $text ) . '</span>' . $icon_html;
					break;
				default: /* before_text */
					$inner = $logo_html . $icon_html . '<span class="plinkly-cta-text">' . esc_html( $text ) . '</span>';
			}

			/* Print anchor */
			echo '<a href="' . $href . '"' .                       /* href already escaped */
			     ( $target   ? ' target="' . esc_attr( $target )   . '"' : '' ) .
			     ( $rel_attr ? ' rel="'    . esc_attr( $rel_attr ) . '"' : '' ) .
			     ' class="plinkly-cta-button elementor-repeater-item-' . esc_attr( $btn['_id'] ) .
			     '" style="' . esc_attr( $style ) . '">' .
			     wp_kses_post( $inner ) .
			     '</a>';
		}

		echo '</div>'; /* .plinkly-cta-buttons */
	}
}
