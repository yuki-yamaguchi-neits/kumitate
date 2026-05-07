<?php
/**
 * Admin Page Meta management
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * メタボックスの登録
 */
function kmt_register_page_meta_boxes() {
    add_meta_box(
        'kmt-sections-meta-box',
        'Kumitate Sections',
        'kmt_render_page_meta_box',
        'page',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes_page', 'kmt_register_page_meta_boxes' );

/**
 * メタボックスの描画
 */
function kmt_render_page_meta_box( $post ) {
    wp_nonce_field( 'kmt_sections_save_meta', 'kmt_sections_nonce' );

    $enabled  = get_post_meta( $post->ID, '_kmt_enabled', true );
    $json_ld  = get_post_meta( $post->ID, '_kmt_page_jsonld', true );
    $sections = get_post_meta( $post->ID, '_kmt_sections', true );

    // 共通パーツ設定
    $use_header = get_post_meta( $post->ID, '_kmt_use_header_part', true );
    $header_id  = get_post_meta( $post->ID, '_kmt_header_part', true );
    $use_footer = get_post_meta( $post->ID, '_kmt_use_footer_part', true );
    $footer_id  = get_post_meta( $post->ID, '_kmt_footer_part', true );

    // 選択肢用の共通パーツ取得
    $common_parts = get_option( '_kmt_common_parts', array() );
    $active_parts = array_filter( $common_parts, function( $p ) { return ( $p['enabled'] ?? '0' ) === '1'; } );

    if ( ! is_array( $sections ) ) {
        $sections = array();
    }

    // 並び順でソート
    usort( $sections, function( $a, $b ) {
        return (int) ( $a['order'] ?? 0 ) - (int) ( $b['order'] ?? 0 );
    } );

    // AI編集用JSONデータの生成
    $export_data = array(
        'type'        => 'kumitate_page_export',
        'version'     => '0.1.1',
        'page_id'     => $post->ID,
        'page_title'  => get_the_title( $post->ID ),
        'exported_at' => current_time( 'c' ),
        'data' => array(
            'enabled'         => $enabled,
            'page_jsonld'     => $json_ld,
            'use_header_part' => $use_header,
            'header_part'     => $header_id,
            'use_footer_part' => $use_footer,
            'footer_part'     => $footer_id,
            'sections'        => $sections,
        )
    );
    $json_export = wp_json_encode( $export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );

    // テンプレートチェック
    $current_template = get_post_meta( $post->ID, '_wp_page_template', true );
    $is_correct_template = ( 'template-kumitate-blank.php' === $current_template );

    ?>
    <div class="kmt-admin-meta-wrap">
        <!-- テンプレート警告 -->
        <?php if ( ! $is_correct_template ) : ?>
            <div class="kmt-template-warning">
                <strong>注意：</strong> 現在のテンプレートは 「Kumitate Blank Template」 ではありません。<br>
                Kumitateの表示が正しく反映されない可能性があります。右サイドバーの「テンプレート」設定を確認してください。
            </div>
        <?php endif; ?>

        <!-- 丸ごと貼り替えの注意文 -->
        <div class="kmt-admin-notice kmt-info-notice">
            <span class="dashicons dashicons-info"></span>
            <p>AIから受け取ったHTML / CSS / JSは、原則として該当セクションの欄を丸ごと上書きしてください。部分修正を行う場合は、検索して該当箇所を確認してから編集してください。</p>
        </div>

        <p class="description">このページでは、通常本文の代わりにKumitateセクションを表示します。</p>

        <!-- 有効化 -->
        <div class="kmt-admin-field">
            <label>
                <input type="checkbox" name="kmt_enabled" value="1" <?php checked( $enabled, '1' ); ?>>
                Kumitateを有効にする
            </label>
        </div>

        <hr>

        <!-- 共通パーツ設定 -->
        <div class="kmt-admin-common-settings-area kmt-accordion-item kmt-is-closed">
            <div class="kmt-accordion-toggle">
                <span class="kmt-header-indicator"></span>
                <strong>共通パーツ設定</strong>
            </div>
            <div class="kmt-section-body" style="display: none;">
                <div class="kmt-admin-field-row">
                    <label>
                        <input type="checkbox" name="kmt_use_header_part" value="1" class="kmt-part-toggle" <?php checked( $use_header, '1' ); ?>>
                        共通ヘッダーを使う
                    </label>
                    <select name="kmt_header_part" class="kmt-part-select" <?php disabled( $use_header !== '1' ); ?>>
                        <option value="">-- 共通パーツを選択 --</option>
                        <?php foreach ( $active_parts as $part ) : ?>
                            <option value="<?php echo esc_attr( $part['id'] ); ?>" <?php selected( $header_id, $part['id'] ); ?>><?php echo esc_html( $part['name'] ); ?> (<?php echo esc_html( $part['id'] ); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="kmt-admin-field-row">
                    <label>
                        <input type="checkbox" name="kmt_use_footer_part" value="1" class="kmt-part-toggle" <?php checked( $use_footer, '1' ); ?>>
                        共通フッターを使う
                    </label>
                    <select name="kmt_footer_part" class="kmt-part-select" <?php disabled( $use_footer !== '1' ); ?>>
                        <option value="">-- 共通パーツを選択 --</option>
                        <?php foreach ( $active_parts as $part ) : ?>
                            <option value="<?php echo esc_attr( $part['id'] ); ?>" <?php selected( $footer_id, $part['id'] ); ?>><?php echo esc_html( $part['name'] ); ?> (<?php echo esc_html( $part['id'] ); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <hr>

        <!-- JSON-LD -->
        <div class="kmt-admin-field kmt-accordion-item kmt-is-closed">
            <div class="kmt-accordion-toggle">
                <span class="kmt-header-indicator"></span>
                <strong>ページJSON-LD</strong>
            </div>
            <div class="kmt-section-body" style="display: none;">
                <textarea id="kmt_page_jsonld" name="kmt_page_jsonld" class="kmt-textarea-jsonld" placeholder='{"@context": "https://schema.org", ...}'><?php echo esc_textarea( $json_ld ); ?></textarea>
            </div>
        </div>

        <hr>

        <!-- 一括HTML取り込み -->
        <div class="kmt-bulk-import-container kmt-accordion-item kmt-is-closed">
            <div class="kmt-bulk-import-header kmt-accordion-toggle">
                <span class="kmt-header-indicator"></span>
                <strong>一括HTML取り込み</strong>
            </div>
            <div class="kmt-bulk-import-body kmt-section-body" style="display: none;">
                <p class="description">自己完結HTMLを貼り付けると、style / script / body を分解して、1つのKumitateセクションとして追加します。AIがHTML / CSS / JSを分けて出力している場合は、この機能を使わず、各欄へ直接貼り付けてください。</p>
                <textarea id="kmt-bulk-html-input" class="kmt-bulk-html-input" placeholder="<html>...</html>"></textarea>
                <button type="button" id="kmt-import-html-as-section" class="button button-secondary">このHTMLをセクションとして取り込む</button>
            </div>
        </div>

        <hr>

        <!-- セクション管理 -->
        <div class="kmt-sections-area">
            <div class="kmt-sections-area-header">
                <h3>Kumitateセクション</h3>
            </div>

            <div id="kmt-sections-list">
                <?php
                if ( ! empty( $sections ) ) {
                    foreach ( $sections as $index => $section ) {
                        kmt_render_section_card( $index, $section );
                    }
                }
                ?>
            </div>

            <div class="kmt-admin-actions">
                <button type="button" id="kmt-add-section-bottom" class="button button-primary kmt-add-section-btn">+ セクション追加</button>
            </div>
        </div>

        <hr>

        <!-- データ管理（AI編集用JSON出力） -->
        <div class="kmt-data-management-container">
            <div class="kmt-export-area">
                <h4>AI編集用JSON</h4>
                <p class="description">
                    このページのKumitateセクション構成を、AIに渡しやすいJSON形式で出力します。このJSONには、各セクションの名前、ID、HTML、CSS、JS、ページJSON-LDなどが含まれます。<br>
                    ChatGPTや他のAIに貼り付けると、現在のページ構成を前提にして、続きの編集・修正・差し替え指示を作りやすくなります。<br>
                    <strong>※これは自動復元用のデータではありません。</strong>復元が必要な場合は、AIにこのJSONを渡して、必要なセクションのHTML / CSS / JSを再生成してください。
                </p>
                
                <div style="margin: 15px 0; padding: 12px; background: #fff; border-left: 4px solid #72aee6; font-size: 13px;">
                    <strong>使い方：</strong><br>
                    1. 「AI編集用JSONを表示」を押す<br>
                    2. 「JSONをコピー」を押す<br>
                    3. ChatGPTなどのAIに貼り付ける<br>
                    4. 「このページの続きとして、セクション3を修正して」などと指示する
                </div>

                <button type="button" id="kmt-show-export-json" class="button button-secondary">AI編集用JSONを表示</button>
                <button type="button" id="kmt-copy-export-json" class="button button-secondary" style="display: none;">JSONをコピー</button>
                <span id="kmt-copy-success" style="display: none; color: #46b450; margin-left: 10px; font-size: 13px;">コピーしました</span>
                
                <div id="kmt-export-output" style="display: none; margin-top: 15px;">
                    <textarea id="kmt-export-textarea" class="kmt-export-json" readonly onclick="this.select()"><?php echo esc_textarea( $json_export ); ?></textarea>
                </div>
            </div>
        </div>

        <!-- JS用テンプレート -->
        <script type="text/template" id="kmt-section-template">
            <?php kmt_render_section_card( '__INDEX__', array(), true ); ?>
        </script>
    </div>
    <?php
}

/**
 * セクション1件のカード描画（内部用）
 */
function kmt_render_section_card( $index, $data, $is_new = false ) {
    $id      = $data['id'] ?? '';
    $name    = $data['name'] ?? '';
    $enabled = $data['enabled'] ?? '1';
    $order   = $data['order'] ?? 0;
    $html    = $data['html'] ?? '';
    $css     = $data['css'] ?? '';
    $js      = $data['js'] ?? '';

    $prefix = "kmt_sections[{$index}]";
    $display_name = $name ?: '名称未設定';
    $display_status = ($enabled === '1') ? 'ON' : 'OFF';
    
    $card_class = 'kmt-section-card kmt-accordion-item';
    if ( ! $is_new ) {
        $card_class .= ' kmt-is-closed';
    }
    ?>
    <div class="<?php echo esc_attr( $card_class ); ?>" data-index="<?php echo esc_attr( $index ); ?>">
        <div class="kmt-section-header kmt-accordion-toggle">
            <div class="kmt-header-main">
                <span class="kmt-section-drag-handle" title="ドラッグして並び替え">☰</span>
                <span class="kmt-header-indicator"></span>
                <span class="kmt-header-title">
                    <span class="kmt-section-number">セクション<?php echo esc_html( (int)$index + 1 ); ?></span>：
                    <span class="kmt-display-name"><?php echo esc_html( $display_name ); ?></span>
                    <span class="kmt-header-meta">
                        ID: <span class="kmt-display-id"><?php echo esc_html( $id ); ?></span> / 
                        表示: <span class="kmt-display-status"><?php echo esc_html( $display_status ); ?></span>
                    </span>
                </span>
            </div>
            <div class="kmt-header-action-hint"></div>
        </div>

        <div class="kmt-section-body">
            <!-- セクション内検索 -->
            <div class="kmt-section-search-bar">
                <label>文字列確認</label>
                <div class="kmt-search-input-wrap">
                    <input type="text" class="kmt-section-search-input" placeholder="文字列やクラス名などが含まれているかを確認します">
                    <span class="kmt-search-results-summary">
                        HTML: <span class="kmt-count-html">0</span>件 / 
                        CSS: <span class="kmt-count-css">0</span>件 / 
                        JS: <span class="kmt-count-js">0</span>件
                    </span>
                </div>
            </div>

            <div class="kmt-section-card-fields">
                <div class="kmt-admin-field">
                    <label>セクション名</label>
                    <input type="text" name="<?php echo $prefix; ?>[name]" value="<?php echo esc_attr( $name ); ?>" class="kmt-input-name" placeholder="例: ヒーロー">
                </div>
                <div class="kmt-admin-field">
                    <label>セクションID</label>
                    <input type="text" name="<?php echo $prefix; ?>[id]" value="<?php echo esc_attr( $id ); ?>" class="kmt-input-id" placeholder="例: hero">
                </div>
                <div class="kmt-admin-field">
                    <label>
                        <input type="hidden" name="<?php echo $prefix; ?>[enabled]" value="0">
                        <input type="checkbox" name="<?php echo $prefix; ?>[enabled]" value="1" class="kmt-input-enabled" <?php checked( $enabled, '1' ); ?>>
                        表示する
                    </label>
                    <input type="hidden" name="<?php echo $prefix; ?>[order]" value="<?php echo esc_attr( $order ); ?>" class="kmt-input-order">
                </div>
            </div>

            <div class="kmt-admin-field">
                <label>HTML</label>
                <textarea name="<?php echo $prefix; ?>[html]" class="kmt-textarea-html kmt-searchable"><?php echo esc_textarea( $html ); ?></textarea>
            </div>
            <div class="kmt-admin-field">
                <label>CSS</label>
                <textarea name="<?php echo $prefix; ?>[css]" class="kmt-textarea-css kmt-searchable"><?php echo esc_textarea( $css ); ?></textarea>
            </div>
            <div class="kmt-admin-field">
                <label>JS</label>
                <textarea name="<?php echo $prefix; ?>[js]" class="kmt-textarea-js kmt-searchable"><?php echo esc_textarea( $js ); ?></textarea>
            </div>
            
            <div class="kmt-section-footer">
                <a class="kmt-delete-section">削除</a>
            </div>
        </div>
    </div>
    <?php
}

/**
 * メタデータの保存
 */
function kmt_save_page_meta( $post_id ) {
    if ( ! isset( $_POST['kmt_sections_nonce'] ) || ! wp_verify_nonce( $_POST['kmt_sections_nonce'], 'kmt_sections_save_meta' ) ) {
        return;
    }

    if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) ) {
        return;
    }

    if ( ! current_user_can( 'edit_page', $post_id ) ) {
        return;
    }

    // 通常の保存処理（インポート機能は削除）
    if ( isset( $_POST['kmt_enabled'] ) ) {
        update_post_meta( $post_id, '_kmt_enabled', '1' );
    } else {
        update_post_meta( $post_id, '_kmt_enabled', '0' );
    }

    update_post_meta( $post_id, '_kmt_use_header_part', isset( $_POST['kmt_use_header_part'] ) ? '1' : '0' );
    update_post_meta( $post_id, '_kmt_header_part', sanitize_text_field( $_POST['kmt_header_part'] ?? '' ) );
    update_post_meta( $post_id, '_kmt_use_footer_part', isset( $_POST['kmt_use_footer_part'] ) ? '1' : '0' );
    update_post_meta( $post_id, '_kmt_footer_part', sanitize_text_field( $_POST['kmt_footer_part'] ?? '' ) );

    if ( isset( $_POST['kmt_page_jsonld'] ) ) {
        update_post_meta( $post_id, '_kmt_page_jsonld', $_POST['kmt_page_jsonld'] );
    }

    if ( isset( $_POST['kmt_sections'] ) && is_array( $_POST['kmt_sections'] ) ) {
        $raw_sections = $_POST['kmt_sections'];
        $sanitized_sections = array();
        $used_ids = array();

        foreach ( $raw_sections as $index => $section ) {
            $id = kmt_sanitize_section_id( $section['id'] ?? '' );
            if ( empty( $id ) ) {
                $id = 'section-' . ( $index + 1 );
            }
            
            $base_id = $id;
            $counter = 1;
            while ( in_array( $id, $used_ids ) ) {
                $id = $base_id . '-' . $counter;
                $counter++;
            }
            $used_ids[] = $id;

            $sanitized_sections[] = array(
                'id'      => $id,
                'name'    => sanitize_text_field( $section['name'] ?? '' ),
                'enabled' => ( ( $section['enabled'] ?? '0' ) === '1' ) ? '1' : '0',
                'order'   => (int) ( $section['order'] ?? 0 ),
                'html'    => $section['html'] ?? '',
                'css'     => $section['css'] ?? '',
                'js'      => $section['js'] ?? '',
            );
        }

        usort( $sanitized_sections, function( $a, $b ) {
            return $a['order'] - $b['order'];
        } );

        update_post_meta( $post_id, '_kmt_sections', $sanitized_sections );
    } else {
        delete_post_meta( $post_id, '_kmt_sections' );
    }
}
add_action( 'save_post_page', 'kmt_save_page_meta' );
