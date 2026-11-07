<?php

/**
 * Register shift post type
 *
 * @package    xfee_theme
 * @author     Richard Lemke
 * @link       https://mrhide.de
 * @license    GPL-3.0 or later
 */

/**
 * Register shift post type
 *
 * @return void
 * @since 0.9.0
 */
add_action('init', 'post_type_shift');
function post_type_shift() {
  $labels = array(
    'name'               => _x('Shifts', 'Post type general name', 'textdomain'),
    'singular_name'      => _x('Shift', 'Post type singular name', 'textdomain'),
    'menu_name'          => _x('Shifts', 'Admin Menu text', 'textdomain'),
    'name_admin_bar'     => _x('Shifts', 'Add New on Toolbar', 'textdomain'),
    'add_new'            => __('New Shift', 'textdomain'),
    'add_new_item'       => __('New Shift', 'textdomain'),
    'new_item'           => __('New Shift', 'textdomain'),
    'edit_item'          => __('Edit Shift', 'textdomain'),
    'view_item'          => __('Edit Shift', 'textdomain'),
    'all_items'          => __('All Shifts', 'textdomain'),
    'search_items'       => __('Search Shifts', 'textdomain'),
    'parent_item_colon'  => __('Parent Shift:', 'textdomain'),
    'not_found'          => __('No Shifts found.', 'textdomain'),
    'not_found_in_trash' => __('No Shifts in trash.', 'textdomain')
  );

  $args = array(
    'labels'             => $labels,
    'public'             => true,
    'publicly_queryable' => true,
    'show_ui'            => true,
    'show_in_menu'       => true,
    'show_in_rest'       => true,
    'query_var'          => true,
    'rewrite'            => array('slug' => 'shift'),
    'capability_type'    => 'post',
    'has_archive'        => true,
    'hierarchical'       => false,
    'menu_position'      => 5,
    'supports'           => array('author', 'revisions'),
    'menu_icon'          => 'dashicons-list-view',
    'revisions'          => true
  );

  register_post_type('shift', $args);
}
