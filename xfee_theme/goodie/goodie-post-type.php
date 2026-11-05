<?php

/**
 * Register goodie post type
 *
 * @package    xfee_theme
 * @author     Richard Lemke
 * @link       https://mrhide.de
 * @license    GPL-3.0 or later
 */

/**
 * Register goodie post type
 *
 * @return void
 *
 * @author Richard Lemke <richard@mrhide.de>
 * @since 0.9.0
 */
add_action('init', 'post_type_goodie');
function post_type_goodie() {
  $labels = array(
    'name'                => _x('Goodies', 'Post type general name', 'textdomain'),
    'singular_name'       => _x('Goodie', 'Post type singular name', 'textdomain'),
    'menu_name'           => _x('Goodies', 'Admin Menu text', 'textdomain'),
    'name_admin_bar'      => _x('Goodies', 'Add New on Toolbar', 'textdomain'),
    'add_new'             => __('New Goodie', 'textdomain'),
    'add_new_item'        => __('New Goodie', 'textdomain'),
    'new_item'            => __('New Goodie', 'textdomain'),
    'edit_item'           => __('Edit Goodie', 'textdomain'),
    'view_item'           => __('Edit Goodie', 'textdomain'),
    'all_items'           => __('All Goodies', 'textdomain'),
    'search_items'        => __('Search Goodies', 'textdomain'),
    'parent_item_colon'   => __('Parent Goodie:', 'textdomain'),
    'not_found'           => __('No Goodies found.', 'textdomain'),
    'not_found_in_trash'  => __('No Goodies in trash.', 'textdomain'),
  );

  $args = array(
    'labels'              => $labels,
    'public'              => true,
    'publicly_queryable'  => true,
    'show_ui'             => true,
    'show_in_menu'        => true,
    'show_in_rest'        => true,
    'query_var'           => true,
    'rewrite'             => array('slug' => 'goodie'),
    'capability_type'     => 'post',
    'has_archive'         => true,
    'hierarchical'        => false,
    'menu_position'       => 6,
    'supports'            => array('author', 'thumbnail', 'revisions'),
    'menu_icon'           => 'dashicons-heart',
    'revisions'           => true
  );

  register_post_type('goodie', $args);
}
