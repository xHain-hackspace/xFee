<?php

/**
 * Enqueues all css & js
 *
 * @package    xfee_theme
 * @author     Richard Lemke
 * @link       https://mrhide.de
 * @license    GPL-3.0 or later
 */

/**
 * Enqueues all frontend css
 *
 * @since 0.9.0
 */
function main_load_scripts() {
  $theme_version = wp_get_theme()->get('Version');
  wp_enqueue_style('main_css', get_template_directory_uri() . '/assets/css/main_style.css', array(), $theme_version, 'all');
  wp_enqueue_style('font_css', get_template_directory_uri() . '/assets/css/fonts.css', array(), $theme_version, 'all');
  wp_enqueue_script('main_script', get_template_directory_uri() . '/assets/js/main_script.js', array('jquery'), $theme_version, true);
  wp_enqueue_script('ajax_script', get_template_directory_uri() . '/assets/js/ajax.js', array('jquery'), $theme_version, true);
}
add_action('wp_enqueue_scripts', 'main_load_scripts');

// ADMIN STYLE
function add_admin_style() {
  $theme_version = wp_get_theme()->get('Version');
  wp_enqueue_style('admin_style', get_template_directory_uri() . '/assets/css/admin_style.css', array(), $theme_version, 'all');
  wp_enqueue_style('dark_style', get_template_directory_uri() . '/assets/css/dark_mode_style.css', array(), $theme_version, 'all');
  wp_enqueue_script('admin_script', get_template_directory_uri() . '/assets/js/admin_script.js', array('jquery'), $theme_version, true);
}
add_action('admin_enqueue_scripts', 'add_admin_style');
