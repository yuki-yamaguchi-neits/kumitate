<?php
/**
 * Common Parts management
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * 共通パーツ管理画面の描画
 */
function kmt_render_common_parts_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    if ( isset( $_POST['kmt_common_parts_nonce'] ) && wp_verify_nonce( $_POST['kmt_common_parts_nonce'], 'kmt_save_common_parts' ) ) {
        kmt_save_common_parts_action();
        echo '<div class="updated"><p>共通パーツを保存しました。</p></div>';
    }

    $parts = get_option( '_kmt_common_parts', array() );
    ?>
    <div class="wrap">
        <h1>共通パーツ管理</h1>
        <p>ヘッダーやフッターなど、複数のページで使い回すパーツを管理します。</p>

        <form method="post" action="">
            <?php wp_nonce_field( 'kmt_save_common_parts', 'kmt_common_parts_nonce' ); ?>
            
            <div id="kmt-common-parts-list" class="kmt-sections-area">
                <?php
                if ( ! empty( $parts ) ) {
                    foreach ( $parts as $index => $part ) {
                        kmt_render_common_part_card( $index, $part );
                    }
                }
                ?>
            </div>

            <div class="kmt-admin-actions">
                <button type="button" id="kmt-add-common-part" class="button button-secondary">+ 共通パーツ追加</button>
            </div>

            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary" value="変更を保存">
            </p>
        </form>

        <!-- JS用テンプレート -->
        <script type="text/template" id="kmt-common-part-template">
            <?php kmt_render_common_part_card( '__INDEX__', array(), true ); ?>
        </script>
    </div>
    <?php
}

/**
 * 共通パーツカードの描画
 */
function kmt_render_common_part_card( $index, $data, $is_new = false ) {
    $id      = $data['id'] ?? '';
    $name    = $data['name'] ?? '';
    $enabled = $data['enabled'] ?? '1';
    $html    = $data['html'] ?? '';
    $css     = $data['css'] ?? '';
    $js      = $data['js'] ?? '';

    $prefix = "kmt_common_parts[{$index}]";
    $display_name = $name ?: '名称未設定';
    $display_status = ($enabled === '1') ? 'ON' : 'OFF';

    $card_class = 'kmt-section-card kmt-common-part-card kmt-accordion-item';
    if ( ! $is_new ) {
        $card_class .= ' kmt-is-closed';
    }
    ?>
    <div class="<?php echo esc_attr( $card_class ); ?>" data-index="<?php echo esc_attr( $index ); ?>">
        <div class="kmt-section-header kmt-accordion-toggle">
            <div class="kmt-header-main">
                <span class="kmt-header-indicator"></span>
                <span class="kmt-header-title">
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
            <div class="kmt-section-card-fields">
                <div class="kmt-admin-field">
                    <label>パーツ名</label>
                    <input type="text" name="<?php echo $prefix; ?>[name]" value="<?php echo esc_attr( $name ); ?>" class="kmt-input-name" placeholder="例: 共通ヘッダー">
                </div>
                <div class="kmt-admin-field">
                    <label>パーツID</label>
                    <input type="text" name="<?php echo $prefix; ?>[id]" value="<?php echo esc_attr( $id ); ?>" class="kmt-input-id" placeholder="例: site-header">
                </div>
                <div class="kmt-admin-field">
                    <label>
                        <input type="hidden" name="<?php echo $prefix; ?>[enabled]" value="0">
                        <input type="checkbox" name="<?php echo $prefix; ?>[enabled]" value="1" class="kmt-input-enabled" <?php checked( $enabled, '1' ); ?>>
                        表示する
                    </label>
                </div>
            </div>

            <div class="kmt-admin-field">
                <label>HTML</label>
                <textarea name="<?php echo $prefix; ?>[html]" class="kmt-textarea-html"><?php echo esc_textarea( $html ); ?></textarea>
            </div>
            <div class="kmt-admin-field">
                <label>CSS</label>
                <textarea name="<?php echo $prefix; ?>[css]" class="kmt-textarea-css"><?php echo esc_textarea( $css ); ?></textarea>
            </div>
            <div class="kmt-admin-field">
                <label>JS</label>
                <textarea name="<?php echo $prefix; ?>[js]" class="kmt-textarea-js"><?php echo esc_textarea( $js ); ?></textarea>
            </div>

            <div class="kmt-section-footer">
                <a class="kmt-delete-section">削除</a>
            </div>
        </div>
    </div>
    <?php
}

/**
 * 共通パーツの保存アクション
 */
function kmt_save_common_parts_action() {
    if ( ! current_user_can( 'manage_options' ) ) return;

    $raw_parts = $_POST['kmt_common_parts'] ?? array();
    $sanitized_parts = array();
    $used_ids = array();

    foreach ( $raw_parts as $index => $part ) {
        $id = kmt_sanitize_section_id( $part['id'] ?? '' );
        if ( empty( $id ) ) {
            $id = 'part-' . ( $index + 1 );
        }

        $base_id = $id;
        $counter = 1;
        while ( in_array( $id, $used_ids ) ) {
            $id = $base_id . '-' . $counter;
            $counter++;
        }
        $used_ids[] = $id;

        $sanitized_parts[] = array(
            'id'      => $id,
            'name'    => sanitize_text_field( $part['name'] ?? '' ),
            'enabled' => ( ( $part['enabled'] ?? '0' ) === '1' ) ? '1' : '0',
            'html'    => $part['html'] ?? '',
            'css'     => $part['css'] ?? '',
            'js'      => $part['js'] ?? '',
        );
    }

    update_option( '_kmt_common_parts', $sanitized_parts );
}
