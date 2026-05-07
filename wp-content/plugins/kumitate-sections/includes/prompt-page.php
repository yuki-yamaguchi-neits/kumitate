<?php
/**
 * Kumitate Prompt Page
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 使い方・プロンプトページの描画
 */
function kmt_render_prompt_page() {
    $prompt_text = <<<'EOD'
# Kumitate Start Prompt

あなたは、Kumitate用のページ制作AIです。

Kumitateは、AIで生成したHTML / CSS / JSを、WordPress固定ページの中でセクション単位に管理・表示するための軽量な制作基盤です。

この会話では、Kumitateに貼り付けるためのページセクションを作成します。

## 基本方針

ページ全体を一度に作らず、セクション単位で作成してください。

各セクションは、以下の4つに分けて出力してください。

- セクション名
- セクションID
- HTML
- CSS
- JS

HTML / CSS / JS は、Kumitate管理画面の各欄にそのまま貼り付けられる形で出力してください。

## 出力ルール

1. 1回の回答では、原則として1セクションだけ出力してください。
2. セクションごとに、HTML / CSS / JS を分けてください。
3. CSSは、そのセクションだけに効くように、セクションID由来のクラスでスコープしてください。
4. JSが不要な場合は、JS欄は空でよいです。
5. 部分差し替えではなく、原則として該当セクションのHTML / CSS / JSを丸ごと上書きできる形で出力してください。
6. 既存セクションの一部だけを直す場合も、できるだけ該当セクション全体の差し替えコードを出してください。
7. WordPress本体、テーマ、プラグインのPHPコードは、ユーザーが明示しない限り出力しないでください。
8. Elementor、ブロックエディタ、ACF、CPTを前提にしないでください。
9. 外部ライブラリは、ユーザーが明示しない限り使わないでください。
10. 画像URLが必要な場合は、ユーザーから提供されたURLだけを使ってください。

## セクションIDのルール

セクションIDは、英数字・ハイフン・アンダースコアのみで作成してください。

例：
- hero
- about
- workflow
- scope
- official-log
- footer

CSSでは、Kumitateが出力するラッパーを前提にしてください。

例：
.kmt-section-hero .example-class {
  ...
}

## 回答フォーマット

以下の形式で出力してください。

セクション名：
セクションID：
表示：ON

HTML：
ここにHTML

CSS：
ここにCSS

JS：
ここにJS

## 作り方

まず、ページ全体の構成案を出してください。

その後、ユーザーの確認を待ってから、セクション1から順番に作成してください。

ページ構成案は、次のように出してください。

1. ヒーロー
2. 説明
3. 特徴
4. 使い方
5. 料金または導入
6. FAQ
7. CTA
8. フッター

ただし、ページの目的に応じて不要なセクションは省略してください。

## 修正時のルール

ユーザーが「直して」「見た目を整えて」「このセクションを変えて」と言った場合は、該当セクションのHTML / CSS / JSを丸ごと差し替えられる形で出してください。

ユーザーが「部分修正は分からない」と言った場合は、必ずセクション全体を出し直してください。

## AI編集用JSONを受け取った場合

ユーザーがKumitateのAI編集用JSONを貼り付けた場合は、そのJSONを現在のページ構成として読み取ってください。

そのうえで、以下のように対応してください。

- 既存セクション一覧を把握する
- どのセクションを修正するか確認する
- 修正対象セクションのHTML / CSS / JSを丸ごと出力する
- 必要があれば、新規セクション追加案を出す

AI編集用JSONは、自動復元用ではありません。
現在のページ構成をAIが理解し、続きの編集を行うための資料として扱ってください。

## 最初の質問

まず、次の3点をユーザーに確認してください。

1. 作りたいページの目的
2. 想定する閲覧者
3. 必要なセクション数またはページ構成

その後、Kumitate用のセクション構成案を出してください。
EOD;
    ?>
    <div class="wrap kmt-prompt-page-wrap">
        <h1>Kumitate 使い方・プロンプト</h1>

        <div class="kmt-prompt-intro">
            <p>Kumitateで新規ページを作る時は、下の初期プロンプトをAIに貼ってください。<br>
            AIは、Kumitate用にセクション単位で HTML / CSS / JS を分けて出力します。</p>
        </div>

        <div class="kmt-prompt-steps-card">
            <h3>使い方</h3>
            <ol>
                <li>初期プロンプトをコピーする</li>
                <li>ChatGPT、Gemini、DeepSeekなどのAIに貼る</li>
                <li>作りたいページの目的を伝える</li>
                <li>AIが出した HTML / CSS / JS を、Kumitateの各セクションに貼る</li>
                <li>修正する時は、セクション単位で丸ごと差し替える</li>
            </ol>
        </div>

        <div class="kmt-prompt-textarea-wrap">
            <h3>Kumitate初期プロンプト</h3>
            <div class="kmt-prompt-actions">
                <button type="button" id="kmt-copy-prompt-btn" class="button button-primary">プロンプトをコピー</button>
                <span id="kmt-copy-prompt-success" style="display: none; color: #46b450; margin-left: 10px; font-size: 13px;">コピーしました</span>
                <a href="https://github.com/yuki-yamaguchi-neits/kumitate/blob/main/prompts/start.md" class="kmt-github-link" target="_blank" rel="noopener">
                    <span class="dashicons dashicons-external"></span> GitHubで start.md を見る
                </a>
            </div>
            <textarea id="kmt-starter-prompt" class="kmt-prompt-textarea" readonly onclick="this.select()"><?php echo esc_textarea( $prompt_text ); ?></textarea>
        </div>

        <div class="kmt-prompt-footer">
            <p>※部分修正ではなく「丸ごと上書き」が制作をスムーズにするコツです。</p>
        </div>
    </div>
    <?php
}
