<?php
/**
 * Template Name: Kumitate Blank Template
 * Template Post Type: page
 *
 * @package Kumitate_Blank
 */
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
  <?php wp_body_open(); ?>

  <div id="kumitate-canvas" class="kmt-canvas">
    <?php
    if ( function_exists( 'kmt_render_page_sections' ) && kmt_should_render_page( get_the_ID() ) ) {
        // Kumitateが有効な場合はセクションを出力
        kmt_render_page_sections( get_the_ID() );
    } else {
        // それ以外は通常のコンテンツを表示
        while ( have_posts() ) :
            the_post();
            the_content();
        endwhile;
    }
    ?>
  </div>

  <?php wp_footer(); ?>
</body>
</html>
