/**
 * Kumitate Sections Admin JS - v0.1.2
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
        updateGutenbergState();

        // --- セクション番号とインデックスの再計算 ---
        function reindexSections() {
            $('#kmt-sections-list .kmt-section-card').each(function(i) {
                const $card = $(this);
                // 表示番号の更新
                $card.find('.kmt-section-number').text('セクション' + (i + 1));
                // order値の更新
                $card.find('.kmt-input-order').val(i);
                // data-indexの更新
                $card.attr('data-index', i);
                
                // name属性のインデックス更新（重要：kmt_sections[x][field] 形式）
                $card.find('input, textarea, select').each(function() {
                    const name = $(this).attr('name');
                    if (name) {
                        const newName = name.replace(/kmt_sections\[\d+\]/, 'kmt_sections[' + i + ']');
                        $(this).attr('name', newName);
                    }
                });
            });
        }

        // --- 並び替え (Sortable) の初期化 ---
        if ($('#kmt-sections-list').length && $.fn.sortable) {
            $('#kmt-sections-list').sortable({
                handle: '.kmt-section-drag-handle',
                items: '.kmt-section-card',
                placeholder: 'kmt-section-sort-placeholder',
                forcePlaceholderSize: true,
                opacity: 0.8,
                update: function() {
                    reindexSections();
                }
            });
        }

        // --- アコーディオンの共通制御 ---
        $(document).on('click', '.kmt-accordion-toggle', function(e) {
            if ($(e.target).is('input, select, textarea, button, a, .kmt-section-drag-handle')) return;
            
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

        // --- セクション管理（固定ページ） ---
        const $list = $('#kmt-sections-list');
        const $template = $('#kmt-section-template');
        let indexCounter = $list.find('.kmt-section-card').length;

        function addNewSection(data = {}) {
            let html = $template.html();
            html = html.replace(/__INDEX__/g, indexCounter);
            const $newCard = $(html);
            
            $newCard.removeClass('kmt-is-closed').addClass('kmt-is-open');
            $newCard.hide().appendTo($list).slideDown(200, function() {
                reindexSections();
            });
            
            if (data.name) $newCard.find('.kmt-input-name').val(data.name).trigger('input');
            if (data.id) $newCard.find('.kmt-input-id').val(data.id).trigger('input');
            if (data.html) $newCard.find('.kmt-textarea-html').val(data.html).trigger('input');
            if (data.css) $newCard.find('.kmt-textarea-css').val(data.css).trigger('input');
            if (data.js) $newCard.find('.kmt-textarea-js').val(data.js).trigger('input');
            
            indexCounter++;
            return $newCard;
        }

        $('.kmt-add-section-btn').on('click', function(e) {
            e.preventDefault();
            addNewSection();
        });

        // --- 共通パーツ管理 ---
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

        // --- セクション内検索機能 ---
        $(document).on('input', '.kmt-section-search-input', function() {
            const query = $(this).val().toLowerCase();
            const $card = $(this).closest('.kmt-section-card');
            
            if (!query) {
                $card.find('.kmt-count-html, .kmt-count-css, .kmt-count-js').text('0');
                return;
            }

            const searchIn = (selector) => {
                const text = $card.find(selector).val().toLowerCase();
                const count = (text.split(query).length - 1);
                return count;
            };

            $card.find('.kmt-count-html').text(searchIn('.kmt-textarea-html'));
            $card.find('.kmt-count-css').text(searchIn('.kmt-textarea-css'));
            $card.find('.kmt-count-js').text(searchIn('.kmt-textarea-js'));
        });

        // --- AI編集用JSONコピー機能 ---
        $('#kmt-copy-export-json').on('click', function(e) {
            e.preventDefault();
            const $textarea = $('#kmt-export-textarea');
            $textarea.select();
            
            try {
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText($textarea.val()).then(() => {
                        showCopySuccess();
                    });
                } else {
                    document.execCommand('copy');
                    showCopySuccess();
                }
            } catch (err) {
                alert('コピーに失敗しました。手動でコピーしてください。');
            }
        });

        function showCopySuccess() {
            $('#kmt-copy-success').fadeIn(200).delay(2000).fadeOut(200);
        }

        // --- プロンプトコピー機能 ---
        $('#kmt-copy-prompt-btn').on('click', function(e) {
            e.preventDefault();
            const $textarea = $('#kmt-starter-prompt');
            $textarea.select();
            
            try {
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText($textarea.val()).then(() => {
                        showPromptCopySuccess();
                    });
                } else {
                    document.execCommand('copy');
                    showPromptCopySuccess();
                }
            } catch (err) {
                alert('コピーに失敗しました。');
            }
        });

        function showPromptCopySuccess() {
            $('#kmt-copy-prompt-success').fadeIn(200).delay(2000).fadeOut(200);
        }

        // --- 共通パーツ選択欄の連動 ---
        $('.kmt-part-toggle').on('change', function() {
            const isChecked = $(this).is(':checked');
            $(this).closest('.kmt-admin-field-row').find('.kmt-part-select').prop('disabled', !isChecked);
        });

        // --- 一括HTML取り込み ---
        $('#kmt-import-html-as-section').on('click', function(e) {
            e.preventDefault();
            const rawHtml = $('#kmt-bulk-html-input').val().trim();
            if (!rawHtml) return;

            try {
                const parser = new DOMParser();
                const doc = parser.parseFromString(rawHtml, 'text/html');
                let css = ''; let js = ''; let jsonLd = ''; let bodyHtml = '';

                // 1. JSON-LDの抽出と除去（最優先）
                doc.querySelectorAll('script[type="application/ld+json"]').forEach(el => {
                    let content = el.textContent.trim();
                    // scriptタグが含まれている場合は中身だけ抽出
                    content = content.replace(/^<script[^>]*>/i, '').replace(/<\/script>$/i, '');
                    jsonLd += content + '\n';
                    el.remove();
                });

                // 2. 通常の style の抽出と除去
                doc.querySelectorAll('style').forEach(el => {
                    css += el.textContent.trim() + '\n';
                    el.remove();
                });

                // 3. 通常の script の抽出と除去
                doc.querySelectorAll('script').forEach(el => {
                    js += el.textContent.trim() + '\n';
                    el.remove();
                });

                // 4. 残ったものを本文HTMLとして取得
                if (doc.body) bodyHtml = doc.body.innerHTML.trim();
                else bodyHtml = doc.documentElement.innerHTML.trim();

                // 5. JSON-LDをページ設定欄へ（確認付き）
                if (jsonLd.trim()) {
                    const $jsonLdField = $('#kmt_page_jsonld');
                    const currentVal = $jsonLdField.val().trim();
                    if (!currentVal || confirm('ページJSON-LD欄に既存の内容があります。取り込んだJSON-LDで上書きしますか？')) {
                        $jsonLdField.val(jsonLd.trim());
                    }
                }

                addNewSection({
                    name: 'Imported Section',
                    id: 'imported-' + (Date.now() % 10000),
                    html: bodyHtml, css: css.trim(), js: js.trim()
                });

                $('#kmt-bulk-html-input').val('');
            } catch (err) {
                alert('解析に失敗しました。');
            }
        });

        // --- AI編集用JSON表示トグル ---
        $('#kmt-show-export-json').on('click', function(e) {
            e.preventDefault();
            const $output = $('#kmt-export-output');
            $output.slideToggle(200);
            $('#kmt-copy-export-json').toggle();
            $(this).text($output.is(':visible') ? 'AI編集用JSONを非表示' : 'AI編集用JSONを表示');
        });

        // --- 共通処理（削除） ---
        $(document).on('click', '.kmt-delete-section', function(e) {
            e.preventDefault();
            if (confirm('この項目を削除しますか？')) {
                const $card = $(this).closest('.kmt-section-card');
                $card.slideUp(200, function() {
                    $(this).remove();
                    if ($('#kmt-sections-list').length) {
                        reindexSections();
                    }
                });
            }
        });
    });

})(jQuery);
