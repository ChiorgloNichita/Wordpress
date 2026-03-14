<?php

function usm_theme_setup()
{
  add_theme_support('title-tag');
}
add_action('after_setup_theme', 'usm_theme_setup');

function usm_theme_styles()
{
  wp_enqueue_style(
    'usm-style',
    get_stylesheet_uri()
  );
}

add_action('wp_enqueue_scripts', 'usm_theme_styles');
