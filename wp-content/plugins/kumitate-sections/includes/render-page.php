<?php
/**
 * Page rendering logic
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 指定されたページが Kumitate 表示対象か判定
 */
function kmt_should_render_page( $post_id ) {
    if ( ! $post_id ) return false;
    if ( get_post_type( $post_id ) !== 'page' ) return false;

    $enabled = get_post_meta( $post_id, '_kmt_enabled', true );
    if ( $enabled !== '1' ) return false;

    // セクションまたは共通パーツのどちらかがあれば有効とみなす
    $sections = get_post_meta( $post_id, '_kmt_sections', true );
    $use_header = get_post_meta( $post_id, '_kmt_use_header_part', true );
    $use_footer = get_post_meta( $post_id, '_kmt_use_footer_part', true );

    if ( empty( $sections ) && $use_header !== '1' && $use_footer !== '1' ) return false;

    return true;
}

/**
 * 表示可能なセクション一覧を取得
 */
function kmt_get_page_sections( $post_id ) {
    $sections = get_post_meta( $post_id, '_kmt_sections', true );
    if ( ! is_array( $sections ) ) return array();

    $visible_sections = array_filter( $sections, function( $section ) {
        return ( ( $section['enabled'] ?? '0' ) === '1' );
    } );

    usort( $visible_sections, function( $a, $b ) {
        return (int) ( $a['order'] ?? 0 ) - (int) ( $b['order'] ?? 0 );
    } );

    return $visible_sections;
}

/**
 * 指定された共通パーツを取得
 */
function kmt_get_common_part( $part_id ) {
    $all_parts = get_option( '_kmt_common_parts', array() );
    foreach ( $all_parts as $part ) {
        if ( $part['id'] === $part_id && ( $part['enabled'] ?? '0' ) === '1' ) {
            return $part;
        }
    }
    return null;
}

/**
 * HTMLのレンダリング（共通パーツ含む）
 */
function kmt_render_page_sections( $post_id ) {
    // 共通ヘッダー
    if ( get_post_meta( $post_id, '_kmt_use_header_part', true ) === '1' ) {
        $header_id = get_post_meta( $post_id, '_kmt_header_part', true );
        $header = kmt_get_common_part( $header_id );
        if ( $header ) {
            printf( '<div class="kmt-common-part kmt-common-header" data-kmt-part="%s">%s</div>', esc_attr( $header['id'] ), $header['html'] );
        }
    }

    // セクション
    $sections = kmt_get_page_sections( $post_id );
    foreach ( $sections as $section ) {
        printf(
            '<section class="kmt-section kmt-section-%1$s" data-kmt-section="%1$s">%2$s</section>',
            esc_attr( $section['id'] ),
            $section['html']
        );
    }

    // 共通フッター
    if ( get_post_meta( $post_id, '_kmt_use_footer_part', true ) === '1' ) {
        $footer_id = get_post_meta( $post_id, '_kmt_footer_part', true );
        $footer = kmt_get_common_part( $footer_id );
        if ( $footer ) {
            printf( '<div class="kmt-common-part kmt-common-footer" data-kmt-part="%s">%s</div>', esc_attr( $footer['id'] ), $footer['html'] );
        }
    }
}

/**
 * CSSの結合出力
 */
function kmt_output_combined_css( $post_id ) {
    $combined_css = '';

    // 共通ヘッダー
    if ( get_post_meta( $post_id, '_kmt_use_header_part', true ) === '1' ) {
        $part = kmt_get_common_part( get_post_meta( $post_id, '_kmt_header_part', true ) );
        if ( $part && ! empty( $part['css'] ) ) {
            $combined_css .= "/* Common Part: {$part['id']} */\n" . $part['css'] . "\n";
        }
    }

    // 共通フッター
    if ( get_post_meta( $post_id, '_kmt_use_footer_part', true ) === '1' ) {
        $part = kmt_get_common_part( get_post_meta( $post_id, '_kmt_footer_part', true ) );
        if ( $part && ! empty( $part['css'] ) ) {
            $combined_css .= "/* Common Part: {$part['id']} */\n" . $part['css'] . "\n";
        }
    }

    // セクション（後に来るように）
    $sections = kmt_get_page_sections( $post_id );
    foreach ( $sections as $section ) {
        if ( ! empty( $section['css'] ) ) {
            $combined_css .= "/* Section: {$section['id']} */\n" . $section['css'] . "\n";
        }
    }

    if ( ! empty( $combined_css ) ) {
        echo '<style id="kmt-page-css">' . "\n" . $combined_css . "</style>\n";
    }
}

/**
 * JSの結合出力
 */
function kmt_output_combined_js( $post_id ) {
    $combined_js = '';

    // 共通ヘッダー
    if ( get_post_meta( $post_id, '_kmt_use_header_part', true ) === '1' ) {
        $part = kmt_get_common_part( get_post_meta( $post_id, '_kmt_header_part', true ) );
        if ( $part && ! empty( $part['js'] ) ) {
            $combined_js .= "/* Common Part: {$part['id']} */\n" . $part['js'] . "\n";
        }
    }

    // 共通フッター
    if ( get_post_meta( $post_id, '_kmt_use_footer_part', true ) === '1' ) {
        $part = kmt_get_common_part( get_post_meta( $post_id, '_kmt_footer_part', true ) );
        if ( $part && ! empty( $part['js'] ) ) {
            $combined_js .= "/* Common Part: {$part['id']} */\n" . $part['js'] . "\n";
        }
    }

    // セクション
    $sections = kmt_get_page_sections( $post_id );
    foreach ( $sections as $section ) {
        if ( ! empty( $section['js'] ) ) {
            $combined_js .= "/* Section: {$section['id']} */\n" . $section['js'] . "\n";
        }
    }

    if ( ! empty( $combined_js ) ) {
        echo '<script id="kmt-page-js">' . "\n" . $combined_js . "</script>\n";
    }
}
