<?php get_header(); ?>

<main class="container">

  <div class="content">

    <?php
    if (have_posts()):
      while (have_posts()):
        the_post();
        ?>

        <h1><?php the_title(); ?></h1>

        <div>
          <?php the_content(); ?>
        </div>

        <?php comments_template(); ?>

        <?php
      endwhile;
    endif;
    ?>

  </div>

  <?php get_sidebar(); ?>

</main>

<?php get_footer(); ?>
