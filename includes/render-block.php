<?php
// File: includes/render-block.php
// Renders CTA buttons on the front-end (incl. A/B variant & click-tracking)

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shortcode / block render-callback.
 *
 * @param array $attributes Gutenberg attributes.
 * @return string HTML output.
 */
function plycta_render_affiliate_button_group( $attributes ) {

	global $wpdb, $post;

	/* 0) لا أزرار؟ ارجع فارغ */
	if ( empty( $attributes['buttons'] ) || ! is_array( $attributes['buttons'] ) ) {
		return '';
	}

	/* 1) جداول ومعرّف التدوينة */
	$imp_table = $wpdb->prefix . 'plinkly_impressions';
	$post_id   = isset( $post->ID ) ? intval( $post->ID ) : 0;

	/* 2) إعدادات عامة */
	$force_update_old    = get_option( 'plinkly_force_update_old_buttons', false );
	$force_change_always = get_option( 'plinkly_force_change_always', false );
	$company_data        = plinkly_get_company_data();

	/* 3) flex container */
	$direction      = is_rtl() ? 'rtl' : 'ltr';
	$flex_dir       = ( $attributes['layout'] ?? 'horizontal' ) === 'vertical' ? 'column' : 'row';
	$justify        = $attributes['alignment'] ?? ( is_rtl() ? 'flex-end' : 'flex-start' );
	$vertical_class = $flex_dir === 'column' ? ' vertical' : '';

	$gapH = isset( $attributes['gapHorizontal'] )
		? (int) $attributes['gapHorizontal']
		: (int) get_option( 'plinkly_default_gap_horizontal', 10 );
	$gapV = isset( $attributes['gapVertical'] )
		? (int) $attributes['gapVertical']
		: (int) get_option( 'plinkly_default_gap_vertical', 10 );

	$container_style = sprintf(
		'--gap-h:%dpx;--gap-v:%dpx;direction:%s;justify-content:%s;',
		$gapH,
		$gapV,
		esc_attr( $direction ),
		esc_attr( $justify )
	);

	$output  = '<div class="affiliate-buttons-group' . esc_attr( $vertical_class ) . '" style="' . $container_style . '">';

	/* 4) loop buttons */
	foreach ( $attributes['buttons'] as $button ) {

		/* (4-a) الرابط */
		$link_raw = ! empty( $button['link'] ) ? trim( $button['link'] ) : '#';
if ( $link_raw && ! preg_match( '#^https?://#i', $link_raw ) ) {
	$link_raw = 'https://' . $link_raw;
}
$link = esc_url( $link_raw );

		/* (4-b) نسخة A/B */
		$variant = ( ! empty( $button['abTestEnabled'] ) && wp_rand( 0, 1 ) ) ? 'B' : 'A';

		/* (4-c) النص */
		$text = ( $variant === 'B' && ! empty( $button['abButtonText'] ) )
			? esc_html( $button['abButtonText'] )
			: esc_html( $button['text'] ?? 'Buy Now' );

		/* (4-d) اللون */
		if ( $variant === 'B' && ! empty( $button['abCustomColor'] ) ) {
			$color = sanitize_hex_color( $button['abCustomColor'] );
		} elseif ( ! empty( $button['customColor'] ) ) {
			$color = sanitize_hex_color( $button['customColor'] );
		} else {
			$host   = wp_parse_url( $link_raw, PHP_URL_HOST );
			$host   = $host ? preg_replace( '/^www\./i', '', $host ) : '';
			$remote = $company_data[ $host ]['color'] ?? '';
			$color  = $remote ? sanitize_hex_color( $remote )
			                  : get_option( 'plinkly_default_color', '#3498db' );
		}

		/* (4-e) سجل الانطباع */
		if ( ! is_admin() && $post_id ) {
			$wpdb->insert(
				$imp_table,
				[
					'post_id'     => $post_id,
					'button_link' => $link_raw,        // خام بدون esc_url لتقليل طول السلسلة
					'showed_at'   => current_time( 'mysql' ),
					'variant'     => $variant          // سيُتجاهل إن لم يكن العمود موجودًا
				],
				[ '%d', '%s', '%s', '%s' ]
			);
		}

		/* (4-f) فرض القيم الافتراضية حسب الإعداد */
		if ( $force_change_always || $force_update_old ) {
			$defaults = [
				'customColor'   => 'plinkly_default_color',
				'borderStyle'   => 'plinkly_default_border_style',
				'borderWidth'   => 'plinkly_default_border_width',
				'borderColor'   => 'plinkly_default_border_color',
				'borderRadius'  => 'plinkly_default_border_radius',
				'shadowOffsetX' => 'plinkly_default_shadow_offset_x',
				'shadowOffsetY' => 'plinkly_default_shadow_offset_y',
				'shadowBlur'    => 'plinkly_default_shadow_blur',
				'shadowColor'   => 'plinkly_default_shadow_color',
				'gapHorizontal' => 'plinkly_default_gap_horizontal',
				'gapVertical'   => 'plinkly_default_gap_vertical',
				'fontFamily'    => 'plinkly_default_font_family',
				'fontWeight'    => 'plinkly_default_font_weight',
				'lineHeight'    => 'plinkly_default_line_height',
				'fontSize'      => 'plinkly_default_font_size',
				'paddingV'      => 'plinkly_default_padding_v',
				'paddingH'      => 'plinkly_default_padding_h',
				'logoSize'      => 'plinkly_default_logo_size',
			];
			foreach ( $defaults as $k => $opt ) {
				if ( $force_change_always || empty( $button[ $k ] ) ) {
					$button[ $k ] = get_option( $opt );
				}
			}
		}

		/* (4-g) خصائص العرض */
		$border_style  = esc_attr( $button['borderStyle']  ?? 'none' );
		$border_width  = intval(  $button['borderWidth']  ?? 0 );
		$border_color  = sanitize_hex_color( $button['borderColor'] ?? '#CCCCCC' );
		$border_radius = ( isset( $button['borderRadius'] ) && $button['borderRadius'] !== '' )
			? intval( $button['borderRadius'] ) : 5;

		$shadow_x   = intval( $button['shadowOffsetX'] ?? 0 );
		$shadow_y   = intval( $button['shadowOffsetY'] ?? 0 );
		$shadow_blr = intval( $button['shadowBlur']    ?? 0 );
		$shadow_clr = sanitize_hex_color( $button['shadowColor'] ?? '#000000' );

		$font_family = esc_attr( $button['fontFamily'] ?? '' );
		$font_weight = esc_attr( $button['fontWeight'] ?? '' );
		$line_height = esc_attr( $button['lineHeight'] ?? '' );
		$font_size   = isset( $button['fontSize'] ) ? intval( $button['fontSize'] ) : 16;
		$padding_v   = isset( $button['paddingV'] ) ? intval( $button['paddingV'] ) : 12;
		$padding_h   = isset( $button['paddingH'] ) ? intval( $button['paddingH'] ) : 26;
		$logo_size   = isset( $button['logoSize'] ) ? intval( $button['logoSize'] ) : intval( get_option( 'plinkly_default_logo_size', 24 ) );

		$button_styles = sprintf(
			'--btn-bg:%s;--btn-border-style:%s;--btn-border-width:%dpx;--btn-border-color:%s;--btn-border-radius:%dpx;--btn-shadow:%dpx %dpx %dpx %s;--btn-font-family:%s;--btn-font-weight:%s;--btn-line-height:%s;--btn-font-size:%dpx;--btn-padding-v:%dpx;--btn-padding-h:%dpx;--logo-size:%dpx;',
			esc_attr( $color ),
			$border_style,
			$border_width,
			esc_attr( $border_color ),
			$border_radius,
			$shadow_x,
			$shadow_y,
			$shadow_blr,
			esc_attr( $shadow_clr ),
			$font_family,
			$font_weight,
			$line_height,
			$font_size,
			$padding_v,
			$padding_h,
			$logo_size
		);

		/* (4-h) target / rel */
		$open_new = isset( $button['openInNewTab'] )
			? (bool) $button['openInNewTab']
			: (bool) get_option( 'plinkly_default_new_tab', 1 );
		$target = $open_new ? ' target="_blank"' : '';

		$rel = [];
		if ( get_option( 'plinkly_default_nofollow', 1 ) ) {
			$rel[] = 'nofollow';
			$rel[] = 'sponsored';
		}
		if ( $open_new ) {
			$rel[] = 'noopener';
			$rel[] = 'noreferrer';
		}
		$rel_attr = $rel ? ' rel="' . implode( ' ', $rel ) . '"' : '';

		/* (4-i) build <a> */
		$output .= sprintf(
			'<a class="plinkly-cta-button affiliate-button" href="%s"%s%s style="%s" data-variant="%s">',
			esc_url( $link ),
			$target,
			$rel_attr,
			$button_styles,
			esc_attr( $variant )
		);

		/* (4-j) icon / logo */
		if ( ! empty( $button['iconUrl'] ) ) {
			$output .= '<img src="' . esc_url( $button['iconUrl'] ) . '" class="plinkly-cta-icon" style="width:' . $logo_size . 'px;height:' . $logo_size . 'px;" alt="" />';
		} else {
			$host = wp_parse_url( $link_raw, PHP_URL_HOST );
			$host = $host ? preg_replace( '/^www\./i', '', $host ) : '';
			if ( ! empty( $company_data[ $host ]['logo'] ) ) {
				$output .= '<img src="' . esc_url( $company_data[ $host ]['logo'] ) . '" class="plinkly-cta-icon" style="width:' . $logo_size . 'px;height:' . $logo_size . 'px;" alt="" />';
			}
		}

		/* (4-k) text & close */
		$output .= '<span>' . $text . '</span></a>';
	}

	/* 5) close container */
	$output .= '</div>';

	return $output;
}
