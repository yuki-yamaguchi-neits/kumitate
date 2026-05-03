<?php
/**
 * Helper functions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Kumitate がサポートする投稿タイプか判定
 */
function kmt_is_supported_post_type( $post_type ) {
    return ( 'page' === $post_type );
}

/**
 * セクションIDのサニタイズ
 */
function kmt_sanitize_section_id( $id ) {
    return preg_replace( '/[^a-zA-Z0-9\-_]/', '', $id );
}

/**
 * JSON-LDの内容からscriptタグを除去して中身だけ返す
 */
function kmt_extract_jsonld_content( $jsonld ) {
    $jsonld = trim( $jsonld );
    if ( preg_match( '/^<script[^>]*>(.*)<\/script>$/is', $jsonld, $matches ) ) {
        return trim( $matches[1] );
    }
    return $jsonld;
}

/**
 * 公式ログに関連する全てのカテゴリID（親＋子孫）を取得
 */
function kmt_get_official_log_category_ids() {
    $parent = get_term_by( 'slug', 'official-log', 'category' );
    if ( ! $parent ) {
        $parent = get_term_by( 'name', '公式ログ', 'category' );
    }

    if ( ! $parent || is_wp_error( $parent ) ) {
        return array();
    }

    $category_ids = array( (int) $parent->term_id );
    $children = get_term_children( $parent->term_id, 'category' );

    if ( ! is_wp_error( $children ) && ! empty( $children ) ) {
        $category_ids = array_merge( $category_ids, array_map( 'intval', $children ) );
    }

    return $category_ids;
}

/**
 * 指定された投稿が公式ログか判定
 */
function kmt_is_official_log_post( $post_id ) {
    $post_id = absint( $post_id );
    if ( ! $post_id || get_post_type( $post_id ) !== 'post' ) {
        return false;
    }

    $official_log_ids = kmt_get_official_log_category_ids();
    if ( empty( $official_log_ids ) ) {
        return false;
    }

    $post_category_ids = wp_get_post_categories( $post_id );
    return (bool) array_intersect( $official_log_ids, $post_category_ids );
}

/**
 * デバッグ用ログ出力
 */
function kmt_log( $message ) {
    if ( WP_DEBUG ) {
        error_log( '[Kumitate] ' . print_r( $message, true ) );
    }
}
