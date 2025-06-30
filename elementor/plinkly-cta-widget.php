<?php
/**
 * Elementor Widget: PlinkLy CTA Buttons
 * --------------------------------------------------------------------------
 * Repeater-based CTA button group with rich style controls + optional A/B-Test.
 *
 * All output is escaped with esc_* functions according to WP-CS "EscapeOutput".
 */

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Repeater;
use Elementor\Group_Control_Typography;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/* ════════════════════════════════════════════════════════════════════════
 * 0) Company data cache (loaded once per page)
 * ===================================================================== */
$GLOBALS['PlinkLyCompanyColors'] = $GLOBALS['PlinkLyCompanyColors']
	?? ( function_exists( 'plinkly_get_company_data' )
	     ? plinkly_get_company_data()
	     : [] );

class PlinkLy_CTA_Elementor_Widget extends Widget_Base {

	/* ───────────────────────────────
	 * 1. Widget meta
	 * ───────────────────────────── */
	public function get_name()       { return 'plinkly_cta'; }
	public function get_title()      { return esc_html__( 'PlinkLy CTA Buttons', 'plinkly-smart-cta-buttons' ); }
	public function get_icon()       { return 'eicon-button'; }
	public function get_categories() { return [ 'basic' ]; }

	/* ───────────────────────────────
	 * 2. Controls
	 * ───────────────────────────── */
	protected function _register_controls() {

		/* ░░ Buttons ░░ */
		$this->start_controls_section( 'section_content', [
			'label' => __( 'Buttons', 'plinkly-smart-cta-buttons' ),
		] );

		$repeater = new Repeater();

		/* Tabs per-button ------------------------------------------------ */
		$repeater->start_controls_tabs( 'tabs_button' );

		/* ▸ Content TAB */
		$repeater->start_controls_tab( 'tab_content', [ 'label' => __( 'Content', 'plinkly-smart-cta-buttons' ) ] );

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

		/* ★ NEW – A/B-Test fields */
		$repeater->add_control( 'ab_test_enabled', [
			'label'        => __( 'Enable A/B Test', 'plinkly-smart-cta-buttons' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => '',
		] );

		$repeater->add_control( 'ab_button_text', [
			'label'       => __( 'Variant B – Text', 'plinkly-smart-cta-buttons' ),
			'type'        => Controls_Manager::TEXT,
			'condition'   => [ 'ab_test_enabled' => 'yes' ],
			'label_block' => true,
		] );

		$repeater->add_control( 'ab_custom_color', [
			'label'     => __( 'Variant B – Background', 'plinkly-smart-cta-buttons' ),
			'type'      => Controls_Manager::COLOR,
			'condition' => [ 'ab_test_enabled' => 'yes' ],
		] );
		/* END A/B additions */

		$repeater->add_control( 'icon', [
			'label'       => __( 'Custom Icon', 'plinkly-smart-cta-buttons' ),
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

		$repeater->end_controls_tab(); /* content */

		/* ▸ Style TAB (unchanged) */
		$repeater->start_controls_tab( 'tab_style', [ 'label' => __( 'Style', 'plinkly-smart-cta-buttons' ) ] );

		$repeater->add_control( 'custom_color', [
			'label' => __( 'Background Color', 'plinkly-smart-cta-buttons' ),
			'type'  => Controls_Manager::COLOR,
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

		$repeater->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'typography',
				'label'    => __( 'Typography', 'plinkly-smart-cta-buttons' ),
				'selector' => '{{WRAPPER}} {{CURRENT_ITEM}} .plinkly-cta-text',
			]
		);

		$repeater->end_controls_tab(); /* style */

		/* ▸ Advanced TAB (unchanged) */
		$repeater->start_controls_tab( 'tab_adv', [ 'label' => __( 'Advanced', 'plinkly-smart-cta-buttons' ) ] );

		$repeater->add_control( 'shadow_offset_x', [
			'label'   => __( 'Shadow Offset X', 'plinkly-smart-cta-buttons' ),
			'type'    => Controls_Manager::SLIDER,
			'range'   => [ 'px' => [ 'min' => -20, 'max' => 20 ] ],
			'default' => [ 'size' => intval( get_option( 'plinkly_default_shadow_offset_x', 0 ) ) ],
		] );

		$repeater->add_control( 'shadow_offset_y', [
			'label'   => __( 'Shadow Offset Y', 'plinkly-smart-cta-buttons' ),
			'type'    => Controls_Manager::SLIDER,
			'range'   => [ 'px' => [ 'min' => -20, 'max' => 20 ] ],
			'default' => [ 'size' => intval( get_option( 'plinkly_default_shadow_offset_y', 0 ) ) ],
		] );

		$repeater->add_control( 'shadow_blur', [
			'label'   => __( 'Shadow Blur', 'plinkly-smart-cta-buttons' ),
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
			'label'   => __( 'rel attribute', 'plinkly-smart-cta-buttons' ),
			'type'    => Controls_Manager::SELECT,
			'options' => [
				''                   => __( 'None',               'plinkly-smart-cta-buttons' ),
				'nofollow'           => 'nofollow',
				'sponsored'          => 'sponsored',
				'nofollow sponsored' => 'nofollow sponsored',
			],
			'default' => get_option( 'plinkly_default_nofollow', 1 ) ? 'nofollow sponsored' : '',
		] );

		$repeater->end_controls_tab(); /* adv */
		$repeater->end_controls_tabs(); /* tabs */

		$this->add_control( 'buttons', [
			'label'       => __( 'Buttons List', 'plinkly-smart-cta-buttons' ),
			'type'        => Controls_Manager::REPEATER,
			'fields'      => $repeater->get_controls(),
			'default'     => [
				[ 'text' => 'Buy Now', 'link' => [ 'url' => '#' ] ],
			],
			'title_field' => '{{{ text }}}',
		] );

		$this->end_controls_section(); /* /Buttons */

		/* ░░ Layout ░░ */
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
			'label'   => __( 'Direction', 'plinkly-smart-cta-buttons' ),
			'type'    => Controls_Manager::SELECT,
			'options' => [ 'horizontal' => 'Horizontal', 'vertical' => 'Vertical' ],
			'default' => 'horizontal',
		] );

		$this->add_responsive_control( 'gap_horizontal', [
			'label'      => __( 'Horizontal Gap', 'plinkly-smart-cta-buttons' ),
			'type'       => Controls_Manager::SLIDER,
			'range'      => [ 'px' => [ 'min' => 0, 'max' => 100 ] ],
			'size_units' => [ 'px', 'em', 'rem' ],
			'default'    => [ 'size' => (int) get_option( 'plinkly_default_gap_horizontal', 10 ), 'unit' => 'px' ],
		] );

		$this->add_responsive_control( 'gap_vertical', [
			'label'      => __( 'Vertical Gap', 'plinkly-smart-cta-buttons' ),
			'type'       => Controls_Manager::SLIDER,
			'range'      => [ 'px' => [ 'min' => 0, 'max' => 100 ] ],
			'size_units' => [ 'px', 'em', 'rem' ],
			'default'    => [ 'size' => (int) get_option( 'plinkly_default_gap_vertical', 10 ), 'unit' => 'px' ],
		] );

		$this->end_controls_section();
	}

	/* ───────────────────────────────
	 * 3. Helpers
	 * ───────────────────────────── */
	private function flex_justify( $align ) {
		return $align === 'center' ? 'center' : ( $align === 'right' ? 'flex-end' : 'flex-start' );
	}

	/* ───────────────────────────────
	 * 4. Render
	 * ───────────────────────────── */
	protected function render() {

		$buttons = $this->get_settings_for_display()['buttons'] ?? [];
		if ( empty( $buttons ) ) return;

		$settings = $this->get_settings_for_display();
		$dir      = ( $settings['layout'] ?? 'horizontal' ) === 'vertical' ? 'column' : 'row';
		$align    = $settings['alignment'] ?? 'left';

		$gap_row = (int)( $settings['gap_vertical']['size']   ?? 10 ) . ( $settings['gap_vertical']['unit']   ?? 'px' );
		$gap_col = (int)( $settings['gap_horizontal']['size'] ?? 10 ) . ( $settings['gap_horizontal']['unit'] ?? 'px' );

		echo '<div class="plinkly-cta-buttons" style="display:flex;flex-direction:' . esc_attr( $dir ) .
		     ';flex-wrap:wrap;justify-content:' . esc_attr( $this->flex_justify( $align ) ) .
		     ';row-gap:' . esc_attr( $gap_row ) . ';column-gap:' . esc_attr( $gap_col ) .
		     ';text-align:' . esc_attr( $align ) . ';">';

		$company = $GLOBALS['PlinkLyCompanyColors'];

		foreach ( $buttons as $btn ) {

			/* URL + host */
			$url = $btn['link']['url'] ?? '#';
			if ( $url && ! preg_match( '#^https?://#i', $url ) ) $url = 'https://' . $url;
			$href = esc_url( $url );
			$host = '';
			if ( $href ) {
				$p = wp_parse_url( $href );
				if ( ! empty( $p['host'] ) ) $host = preg_replace( '/^www\./i', '', $p['host'] );
			}

			/* ★ NEW choose variant */
			$variant = ( ! empty( $btn['ab_test_enabled'] ) && 'yes' === $btn['ab_test_enabled'] && rand(0,1) )
				? 'B' : 'A';

			/* Text */
			$text = ( $variant === 'B' && ! empty( $btn['ab_button_text'] ) )
				? $btn['ab_button_text']
				: ( $btn['text'] ?? esc_html__( 'Buy Now', 'plinkly-smart-cta-buttons' ) );

			/* Background */
			$custom_color = $variant === 'B' ? ( $btn['ab_custom_color'] ?: '' ) : ( $btn['custom_color'] ?: '' );
			$brand_color  = $company[ $host ]['color'] ?? '';
			$color        = $custom_color ?: ( $brand_color ?: get_option( 'plinkly_default_color', '#3498db' ) );

			/* Icon vs logo (icon wins) */
			$icon_html = '';
			if ( ! empty( $btn['icon']['value'] ) ) {
				$icon_html = '<i class="' . esc_attr( $btn['icon']['value'] ) .
				             '" style="font-size:1em;margin-right:5px;"></i>';
			}

			$logo_html = '';
			if ( empty( $icon_html ) && ! empty( $company[ $host ]['logo'] ) ) {
				$sizes = [ 'small'=>20,'medium'=>30,'large'=>40 ];
				$sz    = $sizes[ $btn['logo_size'] ?? 'small' ] ?? 20;
				$logo_html = '<img src="' . esc_url( $company[ $host ]['logo'] ) .
				             '" alt="" style="height:' . $sz . 'px;width:auto;margin-right:5px;">';
			}

			/* Border / radius / shadow */
			$bw      = intval( $btn['border_width']['size'] ?? 0 );
			$border  = ( 'none' !== ( $btn['border_style'] ?? 'none' ) && $bw )
				? "border:{$bw}px " . esc_attr( $btn['border_style'] ) . ' ' . esc_attr( $btn['border_color'] ) . ';'
				: '';
			$radius  = 'border-radius:' . intval( $btn['border_radius']['size'] ?? 5 ) . 'px;';
			$sb      = intval( $btn['shadow_blur']['size'] ?? 0 );
			$shadow  = $sb ? 'box-shadow:' .
			            intval( $btn['shadow_offset_x']['size'] ?? 0 ) . 'px ' .
			            intval( $btn['shadow_offset_y']['size'] ?? 0 ) . 'px ' .
			            $sb . 'px ' . esc_attr( $btn['shadow_color'] ) . ';' : '';

			$style = 'background:' . esc_attr( $color ) .
			         ';padding:10px 20px;display:inline-flex;align-items:center;gap:5px;color:#fff;text-decoration:none;' .
			         $border . $radius . $shadow;

			/* Order according to icon_position */
			$pos = $btn['icon_position'] ?? 'before_text';
			$order = match ( $pos ) {
				'before_logo' => $icon_html . $logo_html,
				'after_logo'  => $logo_html . $icon_html,
				'after_text'  => $logo_html . '<span class="plinkly-cta-text">' . esc_html( $text ) . '</span>' . $icon_html,
				default       => $logo_html . $icon_html /* before_text (default) */
			};
			if ( ! str_contains( $order, '<span' ) ) {
				$order .= '<span class="plinkly-cta-text">' . esc_html( $text ) . '</span>';
			}

			echo '<a href="' . esc_url( $href ) . '"' .
				( ! empty( $btn['open_in_new_tab'] ) && 'yes' === $btn['open_in_new_tab'] ? ' target="_blank"' : '' ) .
				( $btn['rel_attribute'] ? ' rel="' . esc_attr( $btn['rel_attribute'] ) . '"' : '' ) .
				' class="plinkly-cta-button elementor-repeater-item-' . esc_attr( $btn['_id'] ) .
				'" style="' . esc_attr( $style ) . '" data-variant="' . esc_attr( $variant ) . '">' .
				wp_kses_post( $order ) .
				'</a>';
		}

		echo '</div>';
	}
}
