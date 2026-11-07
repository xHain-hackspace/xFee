<?php

/**
 * Editor functions
 *
 * @package    xfee_theme
 * @author     Richard Lemke
 * @link       https://mrhide.de
 * @license    GPL-3.0 or later
 */

/**
 * Returns edit link for shifts and goodies
 *
 * @param integer $post_id
 * @return string HTML
 * @since 0.9.0
 */
function get_editor_link($post_id) {
  if (!current_user_can('edit_others_pages')) return;
  return '<a class="edit_link" href="' . admin_url('post.php?post=' . $post_id . '&action=edit') . '">edit</a>';
}
