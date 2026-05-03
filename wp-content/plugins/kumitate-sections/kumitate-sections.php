<?php
/**
 * Plugin Name: Kumitate Sections
 * Description: Section-based AI generated HTML/CSS/JS management for WordPress pages.
 * Version: 0.1.0
 * Author: Kumitate Team
 * Text Domain: kumitate-sections
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// 定数定義
define( 'KMT_PLUGIN_VERSION', '0.1.0' );
define( 'KMT_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'KMT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * インクルードファイルの読み込み
 */
function kmt_load_includes() {
    require_once KMT_PLUGIN_PATH . 'includes/helpers.php';
    require_once KMT_PLUGIN_PATH . 'includes/admin-page-meta.php';
    require_once KMT_PLUGIN_PATH . 'includes/render-page.php';
    require_once KMT_PLUGIN_PATH . 'includes/common-parts.php';
    require_once KMT_PLUGIN_PATH . 'includes/official-log.php';
}
add_action( 'plugins_loaded', 'kmt_load_includes' );

/**
 * プラグイン有効化時の処理
 */
function kmt_plugin_activate() {
    kmt_load_includes();
    kmt_ensure_official_log_category();
    kmt_official_log_rewrite_rule();
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'kmt_plugin_activate' );

/**
 * 管理画面メニューの登録
 */
function kmt_register_menus() {
    add_menu_page(
        'Kumitate',
        'Kumitate',
        'manage_options',
        'kumitate-sections',
        'kmt_render_common_parts_page', // 共通パーツページを暫定的にトップに
        'none',
        30
    );

    add_submenu_page(
        'kumitate-sections',
        '共通パーツ',
        '共通パーツ',
        'manage_options',
        'kumitate-common-parts',
        'kmt_render_common_parts_page'
    );
    
    // トップレベルのデフォルトメニューを削除（サブメニューと重複するため）
    remove_submenu_page( 'kumitate-sections', 'kumitate-sections' );
}
add_action( 'admin_menu', 'kmt_register_menus' );

/**
 * 管理画面用アセットの読み込み
 */
function kmt_enqueue_admin_assets( $hook ) {
    // 固定ページ編集画面 または Kumitate 管理画面でのみ読み込む
    $allowed_hooks = array( 'post.php', 'post-new.php', 'toplevel_page_kumitate-sections', 'kumitate_page_kumitate-common-parts' );
    if ( ! in_array( $hook, $allowed_hooks ) ) {
        return;
    }

    if ( in_array( $hook, array( 'post.php', 'post-new.php' ) ) ) {
        if ( get_post_type() !== 'page' ) return;
    }

    wp_enqueue_style( 'kmt-admin-style', KMT_PLUGIN_URL . 'assets/admin.css', array(), KMT_PLUGIN_VERSION );
    wp_enqueue_script( 'kmt-admin-script', KMT_PLUGIN_URL . 'assets/admin.js', array( 'jquery' ), KMT_PLUGIN_VERSION, true );
}
add_action( 'admin_enqueue_scripts', 'kmt_enqueue_admin_assets' );

/**
 * サイドバーアイコン専用CSSを全管理画面で読み込む
 */
function kmt_enqueue_sidebar_icon_style() {
    wp_enqueue_style(
        'kmt-sidebar-icon-style',
        KMT_PLUGIN_URL . 'assets/sidebar-icon.css',
        array(),
        KMT_PLUGIN_VERSION
    );
}
add_action( 'admin_enqueue_scripts', 'kmt_enqueue_sidebar_icon_style' );

/**
 * フロントエンド用アセット・メタ出力
 */
function kmt_frontend_head_output() {
    if ( ! is_page() ) return;
    $post_id = get_the_ID();
    if ( ! kmt_should_render_page( $post_id ) ) return;

    // CSS出力 (共通パーツ + セクション)
    kmt_output_combined_css( $post_id );

    // JSON-LDの出力
    $jsonld = get_post_meta( $post_id, '_kmt_page_jsonld', true );
    if ( ! empty( $jsonld ) ) {
        $clean_jsonld = kmt_extract_jsonld_content( $jsonld );
        echo '<script type="application/ld+json" id="kmt-page-jsonld">' . "\n" . $clean_jsonld . "\n</script>\n";
    }
}
add_action( 'wp_head', 'kmt_frontend_head_output' );

function kmt_frontend_footer_output() {
    if ( ! is_page() ) return;
    $post_id = get_the_ID();
    if ( ! kmt_should_render_page( $post_id ) ) return;

    // JS出力 (共通パーツ + セクション)
    kmt_output_combined_js( $post_id );
}
add_action( 'wp_footer', 'kmt_frontend_footer_output' );

/**
 * フロントエンド用ベーススタイル読み込み
 */
function kmt_enqueue_frontend_assets() {
    wp_enqueue_style( 'kmt-frontend-style', KMT_PLUGIN_URL . 'assets/frontend.css', array(), KMT_PLUGIN_VERSION );
}
add_action( 'wp_enqueue_scripts', 'kmt_enqueue_frontend_assets' );
