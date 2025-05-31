<?php
/**
 * File: includes/register-blocks.php
 * Description: Register the PlinkLy CTA block in the Gutenberg editor and pass all required settings
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register the block and all its assets
 */
add_action( 'init', 'plinkly_register_blocks' );

function plinkly_register_blocks() {
    $handle = 'block-editor-script';

    // 1) Block script path
    $script_path = dirname( __DIR__ ) . '/assets/js/block.js';
    $script_url  = plugin_dir_url( dirname( __FILE__ ) ) . 'assets/js/block.js';

    // 2) Register the script if not already registered
    if ( ! wp_script_is( $handle, 'registered' ) ) {
        wp_register_script(
            $handle,
            $script_url,
            [ 'wp-blocks', 'wp-block-editor', 'wp-element', 'wp-components' ],
            file_exists( $script_path ) ? filemtime( $script_path ) : false,
            true
        );
    }
    
    // 2.1) Localize CryptoJS path
    wp_localize_script(
        $handle,
        'PlinkLyAssets',
        ['cryptoJsUrl' => plugin_dir_url(__DIR__) . 'assets/js/crypto-js.min.js',]
        
        );

    // 3) Pass default settings
    wp_localize_script(
        $handle,
        'PlinkLyDefaults',
        [
            'defaultColor'         => get_option( 'plinkly_default_color', '#3498db' ),
            'defaultNewTab'        => (int) get_option( 'plinkly_default_new_tab', 1 ),
            'defaultBorderStyle'   => get_option( 'plinkly_default_border_style', 'none' ),
            'defaultBorderWidth'   => (int) get_option( 'plinkly_default_border_width', 0 ),
            'defaultBorderColor'   => get_option( 'plinkly_default_border_color', '#CCCCCC' ),
            'defaultBorderRadius'  => (int) get_option( 'plinkly_default_border_radius', 5 ),
            'defaultShadowOffsetX' => (int) get_option( 'plinkly_default_shadow_offset_x', 0 ),
            'defaultShadowOffsetY' => (int) get_option( 'plinkly_default_shadow_offset_y', 0 ),
            'defaultShadowBlur'    => (int) get_option( 'plinkly_default_shadow_blur', 0 ),
            'defaultShadowColor'   => get_option( 'plinkly_default_shadow_color', '#999999' ),
            'siteDirection'        => is_rtl() ? 'rtl' : 'ltr',
            'defaultGapH'          => (int) get_option( 'plinkly_default_gap_horizontal', 10 ),
            'defaultGapV'          => (int) get_option( 'plinkly_default_gap_vertical', 10 ),
            'defaultFontFamily'    => get_option( 'plinkly_default_font_family', '' ),
            'defaultFontWeight'    => get_option( 'plinkly_default_font_weight', '' ),
            'defaultLineHeight'    => get_option( 'plinkly_default_line_height', '' ),
            'hmacSecret'           => get_option( 'plinkly_hmac_secret', '' ),
        ]
    );

    // 4) Pass company data
    if ( function_exists( 'plinkly_get_company_data' ) ) {
        $company_colors = plinkly_get_company_data();
        wp_add_inline_script(
            $handle,
            'window.PlinkLyCompanyColors = ' . wp_json_encode( $company_colors ) . ';',
            'before'
        );
    }

    // 5) Configure PlinkLyConfig
    $license_key  = get_option( 'plinkly_license_key', '' );
    if ( empty( $license_key ) ) {
        $license_key = get_option( 'plinkly_pro_license_key', '' );
    }
    $hmac_secret = get_option( 'plinkly_hmac_secret', '' );

    $plinkly_config = [
        'aiProxyUrl' => 'https://api.plink.ly/api/ai-proxy.php',
        'proxyToken' => PLYCTA_PROXY_API_KEY,
        'licenseKey' => $license_key,
        'hmacSecret' => $hmac_secret,
        'nonce'      => wp_create_nonce( 'plinkly_ai' ),
    ];
    wp_add_inline_script(
        $handle,
        'window.PlinkLyConfig = ' . wp_json_encode( $plinkly_config ) . ';',
        'before'
    );

    // 6) Register block type
    register_block_type(
        'custom/affiliate-buttons-group',
        [
            'editor_script'   => $handle,
            'render_callback' => 'plycta_render_affiliate_button_group',
            'attributes'      => [
                'buttons'        => [
                    'type'    => 'array',
                    'default' => [],
                    'items'   => [
                        'type'       => 'object',
                        'properties' => [
                            'text'         => [ 'type' => 'string',  'default' => 'Buy Now' ],
                            'link'         => [ 'type' => 'string',  'default' => ''        ],
                            'openInNewTab' => [ 'type' => 'boolean', 'default' => false     ],
                            'iconUrl'      => [ 'type' => 'string',  'default' => ''        ],
                            'iconId'       => [ 'type' => 'number',  'default' => 0         ],
                            'customColor'  => [ 'type' => 'string',  'default' => ''        ],
                            // إذا لديك خصائص أخرى لكل زرّ، أضفها هنا...
                        ],
                    ],
                ],
                'layout'         => [ 'type' => 'string', 'default' => 'horizontal' ],
                'alignment'      => [ 'type' => 'string', 'default' => is_rtl() ? 'right' : 'left' ],
                'gapHorizontal'  => [ 'type' => 'number', 'default' => 10 ],
                'gapVertical'    => [ 'type' => 'number', 'default' => 10 ],
            ],
        ]
    );
}
