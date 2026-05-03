<?php
/**
 * Official Log management using standard posts
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * 初期カテゴリの作成（スラッグを安定させる）
 */
function kmt_ensure_official_log_category() {
    $parent_cat_name = '公式ログ';
    $parent_slug = 'official-log';
    
    $parent_cat = get_term_by( 'slug', $parent_slug, 'category' );
    if ( ! $parent_cat ) {
        $parent_cat = get_term_by( 'name', $parent_cat_name, 'category' );
    }

    if ( ! $parent_cat ) {
        $result = wp_insert_term( $parent_cat_name, 'category', array( 'slug' => $parent_slug ) );
        if ( is_wp_error( $result ) ) return;
        $parent_id = $result['term_id'];
    } else {
        $parent_id = $parent_cat->term_id;
    }

    // 子カテゴリの作成
    $sub_cats = array(
        'サイト更新' => 'site-update',
        'Instagram' => 'instagram',
        'Googleビジネスプロフィール' => 'google-business-profile',
        'note' => 'note',
        'X' => 'x',
        'Facebook' => 'facebook',
        'YouTube' => 'youtube',
        'LINE公式' => 'line-official',
        '料金・サービス変更' => 'pricing-service-change',
        '制作事例' => 'case-study',
        '重要なお知らせ' => 'important-notice'
    );

    foreach ( $sub_cats as $name => $slug ) {
        if ( ! get_term_by( 'slug', $slug, 'category' ) && ! get_term_by( 'name', $name, 'category' ) ) {
            wp_insert_term( $name, 'category', array( 'parent' => $parent_id, 'slug' => $slug ) );
        }
    }
}
add_action( 'admin_init', 'kmt_ensure_official_log_category' );

/**
 * 投稿編集画面にメタボックスを追加
 */
function kmt_register_official_log_meta_boxes() {
    add_meta_box(
        'kmt-official-log-url',
        '公式リンクURL',
        'kmt_render_official_log_meta_box',
        'post',
        'side',
        'default'
    );
}
add_action( 'add_meta_boxes_post', 'kmt_register_official_log_meta_boxes' );

function kmt_render_official_log_meta_box( $post ) {
    $url = get_post_meta( $post->ID, '_kmt_official_log_url', true );
    wp_nonce_field( 'kmt_official_log_save_meta', 'kmt_official_log_nonce' );

    // 公式ログ判定（子カテゴリ含む）
    $is_log = kmt_is_official_log_post( $post->ID );

    ?>
    <div class="kmt-official-log-field">
        <p class="description">Instagram投稿や外部記事、更新先URLを記入してください。</p>
        <input type="url" name="kmt_official_log_url" value="<?php echo esc_url( $url ); ?>" style="width:100%;" placeholder="https://...">
        
        <?php if ( $is_log ) : ?>
            <div style="margin-top: 15px; padding: 10px; background: #f0f6fb; border-left: 4px solid #2271b1; font-size: 12px;">
                この投稿は<strong>公式ログ</strong>として扱われます。<br>
                一覧では指定したURLへ直接リンクされ、個別ページは非公開（一覧へリダイレクト）となります。
            </div>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * メタデータの保存
 */
function kmt_save_official_log_meta( $post_id ) {
    if ( ! isset( $_POST['kmt_official_log_nonce'] ) || ! wp_verify_nonce( $_POST['kmt_official_log_nonce'], 'kmt_official_log_save_meta' ) ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    if ( isset( $_POST['kmt_official_log_url'] ) ) {
        update_post_meta( $post_id, '_kmt_official_log_url', esc_url_raw( $_POST['kmt_official_log_url'] ) );
    }
}
add_action( 'save_post_post', 'kmt_save_official_log_meta' );

/**
 * Rewriteルールの追加 (/official-log/)
 */
function kmt_official_log_rewrite_rule() {
    add_rewrite_rule( '^official-log/?$', 'index.php?kmt_official_log_page=1', 'top' );
}
add_action( 'init', 'kmt_official_log_rewrite_rule' );

function kmt_official_log_query_vars( $vars ) {
    $vars[] = 'kmt_official_log_page';
    return $vars;
}
add_filter( 'query_vars', 'kmt_official_log_query_vars' );

/**
 * 表示とリダイレクトの制御
 */
function kmt_official_log_template_redirect() {
    if ( get_query_var( 'kmt_official_log_page' ) ) {
        kmt_render_official_log_page();
        exit;
    }

    if ( is_single() && kmt_is_official_log_post( get_the_ID() ) ) {
        wp_safe_redirect( home_url( '/official-log/' ) );
        exit;
    }
}
add_action( 'template_redirect', 'kmt_official_log_template_redirect' );

/**
 * 一覧ページの描画
 */
function kmt_render_official_log_page() {
    $official_log_ids = kmt_get_official_log_category_ids();
    
    if ( empty( $official_log_ids ) ) {
        wp_die( '公式ログカテゴリが見つかりません。' );
    }

    $args = array(
        'post_type'      => 'post',
        'category__in'   => $official_log_ids, // 子カテゴリも含む
        'posts_per_page' => 100,
        'orderby'        => 'date',
        'order'          => 'DESC'
    );
    $query = new WP_Query( $args );

    ?>
    <!DOCTYPE html>
    <html <?php language_attributes(); ?>>
    <head>
        <meta charset="<?php bloginfo( 'charset' ); ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>公式ログ | <?php bloginfo( 'name' ); ?></title>
        <?php wp_head(); ?>
        <style>
            .kmt-official-log-page { max-width: 840px; margin: 40px auto; padding: 0 16px; font-family: system-ui, -apple-system, sans-serif; line-height: 1.6; }
            .kmt-official-log-header { border-bottom: 2px solid #eee; margin-bottom: 30px; padding-bottom: 10px; }
            .kmt-official-log-list { list-style: none; padding: 0; margin: 0; }
            .kmt-official-log-item { margin-bottom: 12px; padding: 8px 0; border-bottom: 1px solid #f5f5f5; display: flex; align-items: flex-start; }
            .kmt-official-log-date { color: #888; font-size: 0.9em; min-width: 100px; display: inline-block; }
            .kmt-official-log-title { flex: 1; margin: 0; font-size: 1em; font-weight: normal; }
            .kmt-official-log-title a { color: #2271b1; text-decoration: none; }
            .kmt-official-log-title a:hover { text-decoration: underline; }
            .kmt-official-log-footer { margin-top: 50px; font-size: 0.9em; text-align: center; }
        </style>
    </head>
    <body <?php body_class(); ?>>
        <div class="kmt-official-log-page">
            <header class="kmt-official-log-header">
                <h1>公式ログ</h1>
            </header>

            <ul class="kmt-official-log-list">
                <?php if ( $query->have_posts() ) : while ( $query->have_posts() ) : $query->the_post(); ?>
                    <?php
                    $url = get_post_meta( get_the_ID(), '_kmt_official_log_url', true );
                    if ( empty( $url ) ) {
                        $url = home_url( '/official-log/' );
                    }
                    ?>
                    <li class="kmt-official-log-item">
                        <span class="kmt-official-log-date"><?php echo get_the_date( 'Y-m-d' ); ?></span>
                        <h2 class="kmt-official-log-title">
                            <a href="<?php echo esc_url( $url ); ?>"><?php the_title(); ?></a>
                        </h2>
                    </li>
                <?php endwhile; wp_reset_postdata(); else : ?>
                    <li>ログはまだありません。</li>
                <?php endif; ?>
            </ul>

            <footer class="kmt-official-log-footer">
                <a href="<?php echo home_url(); ?>">トップページに戻る</a>
            </footer>
        </div>
        <?php wp_footer(); ?>
    </body>
    </html>
    <?php
}
