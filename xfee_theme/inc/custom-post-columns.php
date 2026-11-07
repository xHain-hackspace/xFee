<?php

/**
 * Admin columns for goodies and shifts
 *
 * @package    xfee_theme
 * @author     Richard Lemke
 * @link       https://mrhide.de
 * @license    GPL-3.0 or later
 */

global $pagenow;

if (is_admin() && 'edit.php' == $pagenow) {
  if (isset($_GET['post_type']) && 'shift' == $_GET['post_type']) {
    add_filter('manage_shift_posts_columns', 'manage_shift_columns');
    add_action('manage_shift_posts_custom_column', 'populate_shift_columns', 10, 2);
  } else if (isset($_GET['post_type']) && 'goodie' == $_GET['post_type']) {
    add_filter('manage_goodie_posts_columns', 'manage_goodie_columns');
    add_action('manage_goodie_posts_custom_column', 'populate_goodie_columns', 10, 2);
  }
}

/**
 * Adds custom columns to admin columns
 *
 * @param array $columns
 * @return array
 * @since 0.9.0
 */
function manage_shift_columns($columns) {
  $date = $columns['author'];
  unset($columns['date']);
  unset($columns['author']);
  unset($columns['title']);
  $columns['shift_name']        = __('Shift');
  $columns['occurrences_till']  = __('Occurrences till');
  $columns['author'] = $date;
  return $columns;
}

/**
 * Populates custom admin columns
 *
 * @param array $column
 * @param integer $post_id
 * @return array
 * @since 0.9.0
 */
function populate_shift_columns($column, $post_id) {
  switch ($column) {
    case 'shift_name':
      $shift_fields = get_post_meta($post_id, 'shift_fields', true);
      echo '<a href="' . get_admin_url() . 'post.php?post=' . $post_id . '&action=edit">' . $shift_fields['name'] . '</a>';
      break;
    case 'occurrences_till':
      $shift_fields = get_post_meta($post_id, 'shift_fields', true);
      if (!is_array($shift_fields) || count($shift_fields) === 0) break;
      $occurrences_till = $shift_fields['dates'][array_key_last($shift_fields['dates'])];
      echo '<span>' . $occurrences_till['date'] . '</span>';
      break;
  }
}

/**
 * Adds custom columns to admin columns
 *
 * @param array $column
 * @return array
 * @since 0.9.0
 */
function manage_goodie_columns($columns) {
  $date = $columns['author'];
  unset($columns['date']);
  unset($columns['author']);
  unset($columns['title']);
  $columns['goodie_name'] = __('Goodie');
  $columns['author']      = $date;
  return $columns;
}

/**
 * Populates custom admin columns
 *
 * @param array $column
 * @param integer $post_id
 * @return array
 * @since 0.9.0
 */
function populate_goodie_columns($column, $post_id) {
  switch ($column) {
    case 'goodie_name':
      $goodie_fields = get_post_meta($post_id, 'goodie_fields', true);
      echo '<a href="' . get_admin_url() . 'post.php?post=' . $post_id . '&action=edit">' . $goodie_fields['name'] . '</a>';
      break;
  }
}
