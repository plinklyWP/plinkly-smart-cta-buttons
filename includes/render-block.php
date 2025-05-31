<?php

if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Render the Affiliate Button Group block on the front-end.
 *
 * @package PlinkLy CTA
 */

function plycta_render_affiliate_button_group( $attributes ) {
    global $wpdb, $post;

    // 0) Ensure there are buttons
    if ( empty( $attributes['buttons'] ) || ! is_array( $attributes['buttons'] ) ) {
        return '';
    }

    // 1) Impressions table and post ID
    $imp_table = $wpdb->prefix . 'plinkly_impressions';
    $post_id   = isset( $post->ID ) ? intval( $post->ID ) : 0;

    // 2) General settings + company data
    $force_update_old    = get_option( 'plinkly_force_update_old_buttons', false );
    $force_change_always = get_option( 'plinkly_force_change_always', false );
    $is_pro              = plinkly_is_pro_active();
    $company_data        = plinkly_get_company_data();

    // 3) Container flex settings
    $direction      = is_rtl() ? 'rtl' : 'ltr';
    $flex_dir       = ( $attributes['layout'] ?? 'horizontal' ) === 'vertical' ? 'column' : 'row';
    $alignment      = $attributes['alignment'] ?? ( is_rtl() ? 'right' : 'left' );
    $justify        = $alignment === 'center'
        ? 'center'
        : ( $alignment === 'right' ? 'flex-end' : 'flex-start' );
    $vertical_class = $flex_dir === 'column' ? ' vertical' : '';

    // gaps يمكن أن تتحكم بها عبر متغيرات css
    $gapH = isset( $attributes['gapHorizontal'] )
        ? (int) $attributes['gapHorizontal']
        : (int) get_option( 'plinkly_default_gap_horizontal', 10 );
    $gapV = isset( $attributes['gapVertical'] )
        ? (int) $attributes['gapVertical']
        : (int) get_option( 'plinkly_default_gap_vertical', 10 );

    // 4) Open the container
    $container_style = sprintf(
        '--gap-h:%dpx;--gap-v:%dpx;direction:%s;justify-content:%s;',
        $gapH,
        $gapV,
        esc_attr( $direction ),
        esc_attr( $justify )
    );
    $output  = '<div class="affiliate-buttons-group' . esc_attr( $vertical_class ) . '" style="' . $container_style . '">';

    // 5) Loop to render each button
    foreach ( $attributes['buttons'] as $button ) {

        // Text & link
        $text = ! empty( $button['text'] ) ? esc_html( $button['text'] ) : 'Buy Now';
        $link = ! empty( $button['link'] ) ? esc_url( $button['link'] ) : '#';

        // a) Impression tracking
        if ( ! is_admin() && $post_id ) {
            $wpdb->insert(
                $imp_table,
                [ 'post_id' => $post_id, 'button_link' => $link, 'showed_at' => current_time( 'mysql' ) ],
                [ '%d', '%s', '%s' ]
            );
        }

        if ( $force_change_always ) {
            $button['customColor']   = get_option( 'plinkly_default_color' );
            $button['borderStyle']   = get_option( 'plinkly_default_border_style', 'none' );
            $button['borderWidth']   = intval( get_option( 'plinkly_default_border_width', 0 ) );
            $button['borderColor']   = get_option( 'plinkly_default_border_color', '#CCCCCC' );
            $button['borderRadius']  = intval( get_option( 'plinkly_default_border_radius', 5 ) );
            $button['shadowOffsetX'] = intval( get_option( 'plinkly_default_shadow_offset_x', 0 ) );
            $button['shadowOffsetY'] = intval( get_option( 'plinkly_default_shadow_offset_y', 0 ) );
            $button['shadowBlur']    = intval( get_option( 'plinkly_default_shadow_blur', 0 ) );
            $button['shadowColor']   = get_option( 'plinkly_default_shadow_color', '#999999' );
            $button['gapHorizontal'] = intval( get_option( 'plinkly_default_gap_horizontal', 10 ) );
            $button['gapVertical']   = intval( get_option( 'plinkly_default_gap_vertical', 10 ) );
            $button['fontFamily']    = get_option( 'plinkly_default_font_family', '' );
            $button['fontWeight']    = get_option( 'plinkly_default_font_weight', '' );
            $button['lineHeight']    = get_option( 'plinkly_default_line_height', '' );
        } elseif ( $force_update_old ) {
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
            ];
            foreach ( $defaults as $k => $opt ) {
                if ( ! isset( $button[ $k ] ) || trim( (string) $button[ $k ] ) === '' ) {
                    $val = get_option( $opt );
                    if ( in_array( $k, [ 'borderWidth','borderRadius','shadowOffsetX','shadowOffsetY','shadowBlur' ], true ) ) {
                        $val = intval( $val );
                    }
                    $button[ $k ] = $val;
                }
            }
        }

        // Final color selection (يمكنك جعل الخلفية كمتغير CSS للزر إذا أردت)
        if ( ! empty( $button['customColor'] ) ) {
            $color = sanitize_hex_color( $button['customColor'] );
        } else {
            // Logo or color from company data
            $host = wp_parse_url( $link, PHP_URL_HOST );
            $host = $host ? preg_replace( '/^www\./i', '', $host ) : '';
            $remote_color = $company_data[ $host ]['color'] ?? '';
            $color = $remote_color ? sanitize_hex_color( $remote_color ) : get_option( 'plinkly_default_color', '#3498db' );
        }

        // جمع متغيرات الـ CSS للزر نفسه
        $button_styles = sprintf(
            '--btn-bg:%s;',
            esc_attr( $color )
        );

        // target & rel
        $open_in_new_tab = isset( $button['openInNewTab'] )
            ? (bool) $button['openInNewTab']
            : (bool) get_option( 'plinkly_default_new_tab', 1 );
        $target = $open_in_new_tab ? ' target="_blank"' : '';

        // rel attributes
        $rel_parts = [];
        if ( get_option( 'plinkly_default_nofollow', 1 ) ) {
            $rel_parts[] = 'nofollow';
            $rel_parts[] = 'sponsored';
        }
        if ( $open_in_new_tab ) {
            $rel_parts[] = 'noopener';
            $rel_parts[] = 'noreferrer';
        }
        $rel_attr = empty( $rel_parts ) ? '' : ' rel="' . implode( ' ', $rel_parts ) . '"';

        // Opening <a>
        $output .= sprintf(
            '<a class="plinkly-cta-button affiliate-button" href="%s"%s%s style="%s">',
            esc_url( $link ),
            $target,
            $rel_attr,
            $button_styles
        );

        // 1) Custom icon if set
        if ( ! empty( $button['iconUrl'] ) ) {
            $output .= '<img src="' . esc_url( $button['iconUrl'] ) . '"'
                     . ' alt="" class="plinkly-cta-icon" />';
        }
        // 2) Else fallback to company logo
        else {
            $host = wp_parse_url( $link, PHP_URL_HOST );
            $host = $host ? preg_replace( '/^www\./i', '', $host ) : '';
            if ( ! empty( $company_data[ $host ]['logo'] ) ) {
                $output .= '<img src="' . esc_url( $company_data[ $host ]['logo'] ) . '"'
                         . ' alt="" class="affiliate-button-logo" />';
            }
        }

        // Button text
        $output .= '<span>' . $text . '</span>';

        // Close <a>
        $output .= '</a>';
    }

    // 6) Close the container
    $output .= '</div>';

    return $output;
}
