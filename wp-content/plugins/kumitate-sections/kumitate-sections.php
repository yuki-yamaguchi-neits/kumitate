<?php
/**
 * Plugin Name: Kumitate Sections
 * Description: Section-based AI generated HTML/CSS/JS management for WordPress pages.
 * Version: 0.1.2
 * Author: Kumitate Team
 * Text Domain: kumitate-sections
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// 定数定義
define( 'KMT_PLUGIN_VERSION', '0.1.2' );
define( 'KMT_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'KMT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// ファイルのインクルード
require_once KMT_PLUGIN_PATH . 'includes/helpers.php';
require_once KMT_PLUGIN_PATH . 'includes/admin-page-meta.php';
require_once KMT_PLUGIN_PATH . 'includes/render-page.php';
require_once KMT_PLUGIN_PATH . 'includes/common-parts.php';
require_once KMT_PLUGIN_PATH . 'includes/official-log.php';
require_once KMT_PLUGIN_PATH . 'includes/prompt-page.php';

/**
 * メニュー登録
 */
function kmt_register_menus() {
    // 親メニュー (入口をプロンプトページに変更)
    add_menu_page(
        'Kumitate',
        'Kumitate',
        'manage_options',
        'kumitate',
        'kmt_render_prompt_page',
        'none', // CSS Mask で制御するため none
        30
    );

    // サブメニュー: 使い方・プロンプト
    add_submenu_page(
        'kumitate',
        '使い方・プロンプト',
        '使い方・プロンプト',
        'manage_options',
        'kumitate',
        'kmt_render_prompt_page'
    );

    // サブメニュー: 共通パーツ
    add_submenu_page(
        'kumitate',
        '共通パーツ',
        '共通パーツ',
        'manage_options',
        'kumitate-common-parts',
        'kmt_render_common_parts_page'
    );
}
add_action( 'admin_menu', 'kmt_register_menus' );

/**
 * 管理画面用アセット読み込み
 */
function kmt_enqueue_admin_assets( $hook ) {
    // 固定ページ編集画面 または Kumitate 管理画面でのみ読み込む
    $allowed_hooks = array( 
        'post.php', 
        'post-new.php', 
        'toplevel_page_kumitate', 
        'kumitate_page_kumitate-common-parts',
        'kumitate_page_kumitate-prompt',
        'admin_page_kumitate-common-parts'
    );
    
    if ( ! in_array( $hook, $allowed_hooks ) ) {
        return;
    }

    if ( in_array( $hook, array( 'post.php', 'post-new.php' ) ) ) {
        if ( get_post_type() !== 'page' ) return;
    }

    wp_enqueue_style( 'kmt-admin-style', KMT_PLUGIN_URL . 'assets/admin.css', array(), KMT_PLUGIN_VERSION );
    
    // jquery-ui-sortable を追加
    wp_enqueue_script( 
        'kmt-admin-script', 
        KMT_PLUGIN_URL . 'assets/admin.js', 
        array( 'jquery', 'jquery-ui-sortable' ), 
        KMT_PLUGIN_VERSION, 
        true 
    );
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
    if ( ! kmt_should_render_page( get_the_ID() ) ) {
        return;
    }

    // CSS出力
    kmt_output_combined_css( get_the_ID() );

    // JSON-LD出力
    $json_ld = get_post_meta( get_the_ID(), '_kmt_page_jsonld', true );
    if ( $json_ld ) {
        $clean_json = kmt_extract_jsonld_content( $json_ld );
        if ( ! empty( $clean_json ) ) {
            echo "\n<script type=\"application/ld+json\">\n" . $clean_json . "\n</script>\n";
        }
    }
}
add_action( 'wp_head', 'kmt_frontend_head_output' );

/**
 * フロントエンド用JS出力
 */
function kmt_frontend_footer_output() {
    if ( ! kmt_should_render_page( get_the_ID() ) ) {
        return;
    }

    kmt_output_combined_js( get_the_ID() );
}
add_action( 'wp_footer', 'kmt_frontend_footer_output' );
