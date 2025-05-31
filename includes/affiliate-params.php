<?php
// File: includes/affiliate-params.php

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Modify external links to automatically append affiliate parameters.
 *
 * @param string $link The original URL.
 * @return string The modified URL.
 */
function plinkly_add_affiliate_params( $link ) {
    if ( ! plinkly_is_pro_active() ) {
        return $link;
    }

    // Amazon
    if ( strpos( $link, 'amazon.' ) !== false && strpos( $link, 'tag=' ) === false ) {
        $tag = trim( get_option( 'plinkly_amazon_tag', '' ) );
        if ( $tag ) {
            $link .= ( strpos( $link, '?' ) !== false ? '&' : '?' ) . 'tag=' . urlencode( $tag );
        }
    }

    // eBay
    elseif ( strpos( $link, 'ebay.' ) !== false && strpos( $link, 'campid=' ) === false ) {
        $campid = trim( get_option( 'plinkly_ebay_campid', '' ) );
        if ( $campid ) {
            $link .= ( strpos( $link, '?' ) !== false ? '&' : '?' ) . 'campid=' . urlencode( $campid );
        }
    }

    // AliExpress
    elseif ( strpos( $link, 'aliexpress.' ) !== false && strpos( $link, 'aff_' ) === false ) {
        $aff = trim( get_option( 'plinkly_aliexpress_aff', '' ) );
        if ( $aff ) {
            $link .= ( strpos( $link, '?' ) !== false ? '&' : '?' ) . $aff;
        }
    }

    return $link;
}
