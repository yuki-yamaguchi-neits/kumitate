<!doctype html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
  <?php wp_body_open(); ?>

  <main class="kmt-blank-main">
    <?php
    if ( have_posts() ) :
        while ( have_posts() ) :
            the_post();
            the_content();
        endwhile;
    else :
        echo '<p>No content found.</p>';
    endif;
    ?>
  </main>

  <?php wp_footer(); ?>
</body>
</html>
