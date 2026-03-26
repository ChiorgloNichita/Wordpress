<?php
/**
 * Plugin Name: USM Notes
 * Plugin URI: https://example.com/
 * Description: Учебный плагин для управления заметками с приоритетами и датой напоминания.
 * Version: 1.0.0
 * Author: Nikita
 * License: GPL2+
 * Text Domain: usm-notes
 */

if (!defined('ABSPATH')) {
  exit;
}

/**
 * Регистрация Custom Post Type: Notes
 */
function usm_notes_register_post_type()
{
  $labels = array(
    'name' => 'Notes',
    'singular_name' => 'Note',
    'menu_name' => 'Notes',
    'name_admin_bar' => 'Note',
    'add_new' => 'Add New',
    'add_new_item' => 'Add New Note',
    'new_item' => 'New Note',
    'edit_item' => 'Edit Note',
    'view_item' => 'View Note',
    'all_items' => 'All Notes',
    'search_items' => 'Search Notes',
    'not_found' => 'No notes found',
    'not_found_in_trash' => 'No notes found in Trash',
  );

  $args = array(
    'labels' => $labels,
    'public' => true,
    'has_archive' => true,
    'menu_icon' => 'dashicons-edit-page',
    'supports' => array('title', 'editor', 'author', 'thumbnail'),
    'show_in_rest' => true,
    'rewrite' => array('slug' => 'notes'),
  );

  register_post_type('usm_note', $args);
}
add_action('init', 'usm_notes_register_post_type');
/**
 * Регистрация пользовательской таксономии: Priority
 */
function usm_notes_register_taxonomy()
{
  $labels = array(
    'name' => 'Priorities',
    'singular_name' => 'Priority',
    'search_items' => 'Search Priorities',
    'all_items' => 'All Priorities',
    'parent_item' => 'Parent Priority',
    'parent_item_colon' => 'Parent Priority:',
    'edit_item' => 'Edit Priority',
    'update_item' => 'Update Priority',
    'add_new_item' => 'Add New Priority',
    'new_item_name' => 'New Priority Name',
    'menu_name' => 'Priority',
  );

  $args = array(
    'hierarchical' => true,
    'labels' => $labels,
    'public' => true,
    'show_admin_column' => true,
    'show_in_rest' => true,
    'rewrite' => array('slug' => 'priority'),
  );

  register_taxonomy('usm_priority', array('usm_note'), $args);
}
add_action('init', 'usm_notes_register_taxonomy');
/**
 * Добавление метабокса для даты напоминания
 */
function usm_notes_add_due_date_meta_box()
{
  add_meta_box(
    'usm_notes_due_date',
    'Due Date',
    'usm_notes_render_due_date_meta_box',
    'usm_note',
    'side',
    'default'
  );
}
add_action('add_meta_boxes', 'usm_notes_add_due_date_meta_box');

/**
 * Отрисовка метабокса
 */
function usm_notes_render_due_date_meta_box($post)
{
  wp_nonce_field('usm_notes_save_due_date', 'usm_notes_due_date_nonce');

  $due_date = get_post_meta($post->ID, '_usm_due_date', true);

  echo '<label for="usm_due_date_field">Select due date:</label>';
  echo '<input 
          type="date" 
          id="usm_due_date_field" 
          name="usm_due_date_field" 
          value="' . esc_attr($due_date) . '" 
          required 
          style="width:100%; margin-top:8px;"
        >';
}

/**
 * Сохранение даты напоминания
 */
function usm_notes_save_due_date($post_id)
{
  if (!isset($_POST['usm_notes_due_date_nonce'])) {
    return;
  }

  if (!wp_verify_nonce($_POST['usm_notes_due_date_nonce'], 'usm_notes_save_due_date')) {
    return;
  }

  if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
    return;
  }

  if (!isset($_POST['post_type']) || $_POST['post_type'] !== 'usm_note') {
    return;
  }

  if (!current_user_can('edit_post', $post_id)) {
    return;
  }

  if (!isset($_POST['usm_due_date_field']) || empty($_POST['usm_due_date_field'])) {
    set_transient('usm_notes_due_date_error_' . $post_id, 'Due Date is required.', 30);
    return;
  }

  $due_date = sanitize_text_field($_POST['usm_due_date_field']);
  $today = date('Y-m-d');

  if ($due_date < $today) {
    set_transient('usm_notes_due_date_error_' . $post_id, 'Due Date cannot be in the past.', 30);
    return;
  }

  update_post_meta($post_id, '_usm_due_date', $due_date);
}
add_action('save_post', 'usm_notes_save_due_date');

/**
 * Вывод сообщения об ошибке в админке
 */
function usm_notes_due_date_admin_notice()
{
  global $pagenow;

  if (($pagenow === 'post.php' || $pagenow === 'post-new.php') && isset($_GET['post'])) {
    $post_id = (int) $_GET['post'];
    $error = get_transient('usm_notes_due_date_error_' . $post_id);

    if ($error) {
      echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($error) . '</p></div>';
      delete_transient('usm_notes_due_date_error_' . $post_id);
    }
  }
}
add_action('admin_notices', 'usm_notes_due_date_admin_notice');

/**
 * Добавление колонки Due Date в список заметок
 */
function usm_notes_add_due_date_column($columns)
{
  $new_columns = array();

  foreach ($columns as $key => $value) {
    $new_columns[$key] = $value;

    if ($key === 'title') {
      $new_columns['usm_due_date'] = 'Due Date';
    }
  }

  return $new_columns;
}
add_filter('manage_usm_note_posts_columns', 'usm_notes_add_due_date_column');

/**
 * Заполнение колонки Due Date
 */
function usm_notes_render_due_date_column($column, $post_id)
{
  if ($column === 'usm_due_date') {
    $due_date = get_post_meta($post_id, '_usm_due_date', true);

    if (!empty($due_date)) {
      echo esc_html($due_date);
    } else {
      echo '—';
    }
  }
}
add_action('manage_usm_note_posts_custom_column', 'usm_notes_render_due_date_column', 10, 2);
/**
 * Шорткод для вывода заметок
 * [usm_notes priority="high" before_date="2025-04-30"]
 */
function usm_notes_shortcode($atts)
{
  $atts = shortcode_atts(array(
    'priority' => '',
    'before_date' => '',
  ), $atts, 'usm_notes');

  $tax_query = array();
  $meta_query = array();

  if (!empty($atts['priority'])) {
    $tax_query[] = array(
      'taxonomy' => 'usm_priority',
      'field' => 'slug',
      'terms' => sanitize_title($atts['priority']),
    );
  }

  if (!empty($atts['before_date'])) {
    $meta_query[] = array(
      'key' => '_usm_due_date',
      'value' => sanitize_text_field($atts['before_date']),
      'compare' => '<=',
      'type' => 'DATE',
    );
  }

  $args = array(
    'post_type' => 'usm_note',
    'post_status' => 'publish',
    'posts_per_page' => -1,
  );

  if (!empty($tax_query)) {
    $args['tax_query'] = $tax_query;
  }

  if (!empty($meta_query)) {
    $args['meta_query'] = $meta_query;
  }

  $query = new WP_Query($args);

  ob_start();

  echo '<div class="usm-notes-list">';

  if ($query->have_posts()) {
    while ($query->have_posts()) {
      $query->the_post();

      $post_id = get_the_ID();
      $due_date = get_post_meta($post_id, '_usm_due_date', true);
      $terms = get_the_terms($post_id, 'usm_priority');

      echo '<div class="usm-note-item">';
      echo '<h3 class="usm-note-title">' . esc_html(get_the_title()) . '</h3>';
      echo '<div class="usm-note-content">' . wp_kses_post(get_the_content()) . '</div>';

      if (!empty($terms) && !is_wp_error($terms)) {
        echo '<p class="usm-note-priority"><strong>Priority:</strong> ' . esc_html($terms[0]->name) . '</p>';
      }

      if (!empty($due_date)) {
        echo '<p class="usm-note-date"><strong>Due Date:</strong> ' . esc_html($due_date) . '</p>';
      }

      echo '</div>';
    }
  } else {
    echo '<p class="usm-notes-empty">Нет заметок с заданными параметрами</p>';
  }

  echo '</div>';

  wp_reset_postdata();

  return ob_get_clean();
}
add_shortcode('usm_notes', 'usm_notes_shortcode');

/**
 * Стили для списка заметок
 */
function usm_notes_enqueue_shortcode_styles()
{
  $custom_css = "
    .usm-notes-list {
      display: grid;
      gap: 20px;
      margin: 20px 0;
    }

    .usm-note-item {
      border: 1px solid #ddd;
      padding: 16px;
      border-radius: 8px;
      background: #f9f9f9;
    }

    .usm-note-title {
      margin: 0 0 10px;
      font-size: 22px;
    }

    .usm-note-content {
      margin-bottom: 12px;
    }

    .usm-note-priority,
    .usm-note-date {
      margin: 6px 0;
      font-size: 14px;
    }

    .usm-notes-empty {
      padding: 12px;
      background: #fff3cd;
      border: 1px solid #ffe69c;
      border-radius: 6px;
    }
  ";

  wp_register_style('usm-notes-shortcode-style', false);
  wp_enqueue_style('usm-notes-shortcode-style');
  wp_add_inline_style('usm-notes-shortcode-style', $custom_css);
}
add_action('wp_enqueue_scripts', 'usm_notes_enqueue_shortcode_styles');