/**
 * Kumitate Sections Admin JS
 */

(function($) {
    'use strict';

    $(function() {
        // --- WordPress 本文エリアの抑制制御 ---
        const $enabledCheckbox = $('input[name="kmt_enabled"]');
        function updateGutenbergState() {
            if ($enabledCheckbox.is(':checked')) {
                $('body').addClass('kmt-enabled');
            } else {
                $('body').removeClass('kmt-enabled');
            }
        }
        $enabledCheckbox.on('change', updateGutenbergState);
        updateGutenbergState(); // 初期状態反映

        // --- アコーディオンの共通制御 ---
        $(document).on('click', '.kmt-accordion-toggle', function(e) {
            // 入力要素等のクリック時は無視
            if ($(e.target).is('input, select, textarea, button, a')) return;
            
            const $item = $(this).closest('.kmt-accordion-item, .kmt-bulk-import-container');
            const $body = $item.find('.kmt-section-body, .kmt-bulk-import-body').first();

            if ($item.hasClass('kmt-is-closed') || !$item.hasClass('kmt-is-open')) {
                $item.removeClass('kmt-is-closed').addClass('kmt-is-open');
                $body.slideDown(200);
            } else {
                $item.removeClass('kmt-is-open').addClass('kmt-is-closed');
                $body.slideUp(200);
            }
        });

        // --- セクション管理 ---
        const $list = $('#kmt-sections-list');
        const $template = $('#kmt-section-template');
        let indexCounter = $list.find('.kmt-section-card').length;

        function addNewSection(data = {}) {
            let html = $template.html();
            html = html.replace(/__INDEX__/g, indexCounter);
            const $newCard = $(html);
            
            // 新規追加時は開いた状態にする
            $newCard.removeClass('kmt-is-closed').addClass('kmt-is-open');
            $newCard.hide().appendTo($list).slideDown(200);
            
            if (data.name) $newCard.find('.kmt-input-name').val(data.name).trigger('input');
            if (data.id) $newCard.find('.kmt-input-id').val(data.id).trigger('input');
            if (data.html) $newCard.find('.kmt-textarea-html').val(data.html);
            if (data.css) $newCard.find('.kmt-textarea-css').val(data.css);
            if (data.js) $newCard.find('.kmt-textarea-js').val(data.js);
            
            $newCard.find('.kmt-input-order').val(indexCounter);
            indexCounter++;
            return $newCard;
        }

        $('.kmt-add-section-btn').on('click', function(e) {
            e.preventDefault();
            addNewSection();
        });

        // --- 入力値の見出しへのリアルタイム反映 ---
        $(document).on('input', '.kmt-input-name', function() {
            const val = $(this).val();
            $(this).closest('.kmt-section-card').find('.kmt-display-name').text(val || '名称未設定');
        });

        $(document).on('input', '.kmt-input-id', function() {
            const val = $(this).val();
            $(this).closest('.kmt-section-card').find('.kmt-display-id').text(val || '');
        });

        $(document).on('change', '.kmt-input-enabled', function() {
            const isChecked = $(this).is(':checked');
            $(this).closest('.kmt-section-card').find('.kmt-display-status').text(isChecked ? 'ON' : 'OFF');
        });

        // --- 共通パーツ選択欄の連動 ---
        $('.kmt-part-toggle').on('change', function() {
            const isChecked = $(this).is(':checked');
            $(this).closest('.kmt-admin-field-row').find('.kmt-part-select').prop('disabled', !isChecked);
        });

        // --- 一括HTML取り込み ---
        $('#kmt-import-html-as-section').on('click', function(e) {
            e.preventDefault();
            const rawHtml = $('#kmt-bulk-html-input').val().trim();
            if (!rawHtml) {
                alert('HTMLを貼り付けてください。');
                return;
            }

            try {
                const parser = new DOMParser();
                const doc = parser.parseFromString(rawHtml, 'text/html');
                
                let css = '';
                let js = '';
                let jsonLd = '';
                let bodyHtml = '';

                doc.querySelectorAll('style').forEach(el => {
                    css += el.textContent + '\n';
                    el.remove();
                });

                doc.querySelectorAll('script[type="application/ld+json"]').forEach(el => {
                    jsonLd += el.textContent + '\n';
                    el.remove();
                });

                doc.querySelectorAll('script').forEach(el => {
                    js += el.textContent + '\n';
                    el.remove();
                });

                if (doc.body) {
                    bodyHtml = doc.body.innerHTML.trim();
                } else {
                    bodyHtml = doc.documentElement.innerHTML.trim();
                }

                if (jsonLd) {
                    const $jsonLdField = $('#kmt_page_jsonld');
                    const currentJsonLd = $jsonLdField.val().trim();
                    let proceedJson = true;
                    if (currentJsonLd) {
                        proceedJson = confirm('ページJSON-LD欄に既存の内容があります。取り込んだJSON-LDで置き換えますか？');
                    }
                    if (proceedJson) {
                        $jsonLdField.val(jsonLd.trim());
                    }
                }

                addNewSection({
                    name: 'Imported Section',
                    id: 'imported-section-' + (Date.now() % 10000),
                    html: bodyHtml,
                    css: css.trim(),
                    js: js.trim()
                });

                $('#kmt-bulk-html-input').val('');
                alert('HTMLを取り込み、新しいセクションとして追加しました。');

            } catch (err) {
                console.error(err);
                alert('HTMLの解析に失敗しました。形式を確認してください。');
            }
        });

        // --- データエクスポート ---
        $('#kmt-show-export-json').on('click', function(e) {
            e.preventDefault();
            const $output = $('#kmt-export-output');
            $output.slideToggle(200);
            $(this).text($output.is(':visible') ? 'エクスポートJSONを非表示' : 'エクスポートJSONを表示');
        });

        // --- 共通パーツ管理画面 ---
        const $partsList = $('#kmt-common-parts-list');
        const $partsTemplate = $('#kmt-common-part-template');
        let partsCounter = $partsList.find('.kmt-common-part-card').length;

        $('#kmt-add-common-part').on('click', function(e) {
            e.preventDefault();
            let html = $partsTemplate.html();
            html = html.replace(/__INDEX__/g, partsCounter);
            const $newCard = $(html);
            $newCard.removeClass('kmt-is-closed').addClass('kmt-is-open');
            $newCard.hide().appendTo($partsList).slideDown(200);
            partsCounter++;
        });

        // --- 共通処理（削除） ---
        $(document).on('click', '.kmt-delete-section', function(e) {
            e.preventDefault();
            if (confirm('この項目を削除してもよろしいですか？')) {
                const $card = $(this).closest('.kmt-section-card');
                $card.slideUp(200, function() {
                    $(this).remove();
                });
            }
        });
    });

})(jQuery);
