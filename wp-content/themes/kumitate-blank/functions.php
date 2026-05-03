<?php
/**
 * Kumitate Blank functions and definitions
 *
 * @package Kumitate_Blank
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * テーマのセットアップ
 */
function kmt_blank_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
}
add_action( 'after_setup_theme', 'kmt_blank_setup' );

/**
 * スタイルとスクリプトの読み込み
 */
function kmt_blank_enqueue_scripts() {
	wp_enqueue_style( 'kmt-blank-base-style', get_template_directory_uri() . '/assets/base.css', array(), '0.1.0' );
}
add_action( 'wp_enqueue_scripts', 'kmt_blank_enqueue_scripts' );

/**
 * body_class にテーマ固有のクラスを追加
 */
function kmt_blank_body_classes( $classes ) {
	$classes[] = 'kumitate-blank-theme';

	if ( is_page_template( 'template-kumitate-blank.php' ) ) {
		$classes[] = 'kumitate-blank-template';
	}

	return $classes;
}
add_filter( 'body_class', 'kmt_blank_body_classes' );

/**
 * 公式ログへの最小限の導線をフッターに追加
 */
function kmt_blank_footer_log_link() {
    // 邪魔にならない位置に配置
    ?>
    <style>
        .kmt-official-log-link {
            position: fixed;
            right: 12px;
            bottom: 8px;
            font-size: 11px;
            color: #999;
            text-decoration: none;
            opacity: 0.5;
            z-index: 9999;
            font-family: sans-serif;
        }
        .kmt-official-log-link:hover {
            opacity: 1;
            color: #666;
        }
    </style>
    <a class="kmt-official-log-link" href="<?php echo home_url( '/official-log/' ); ?>">公式ログ</a>
    <?php
}
add_action( 'wp_footer', 'kmt_blank_footer_log_link' );
