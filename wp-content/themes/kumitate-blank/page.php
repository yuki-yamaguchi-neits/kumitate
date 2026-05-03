<!doctype html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
  <?php wp_body_open(); ?>

  <main class="kmt-blank-page">
    <?php
    if ( function_exists( 'kmt_render_page_sections' ) && kmt_should_render_page( get_the_ID() ) ) {
        kmt_render_page_sections( get_the_ID() );
    } else {
        while ( have_posts() ) :
            the_post();
            the_content();
        endwhile;
    }
    ?>
  </main>

  <?php wp_footer(); ?>
</body>
</html>
