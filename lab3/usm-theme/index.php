<?php get_header(); ?>

<main style="padding:20px;">

  <h1>Последние записи</h1>

  <?php
  $count = 0;

  if (have_posts()):
    while (have_posts() && $count < 5):
      the_post();
      $count++;
      ?>

      <article>
        <h2>
          <a href="<?php the_permalink(); ?>">
            <?php the_title(); ?>
          </a>
        </h2>

        <p><?php the_excerpt(); ?></p>

      </article>

      <?php
    endwhile;
  endif;
  ?>

</main>

<?php get_footer(); ?>
