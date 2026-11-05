<?php

/**
 * Basic wordpress related theme functions
 *
 * @package    xfee_theme
 * @author     Richard Lemke
 * @link       https://mrhide.de
 * @license    GPL-3.0 or later
 */


// login cookie // TODO: REMOVE
function keep_me_logged_in_for_1_year($expirein) {
  return 15700000; // 1/2 year in seconds
}
add_filter('auth_cookie_expiration', 'keep_me_logged_in_for_1_year');


/**
 * Protects pages from logged out users
 *
 * @author Richard Lemke <richard@mrhide.de>
 * @since 0.9.0
 */
add_action('template_redirect', 'protect_internal_pages');
function protect_internal_pages() {
  if (is_user_logged_in()) {
    if (is_front_page()) {
      wp_safe_redirect(home_url('/shifts/'));
      exit;
    }
    return;
  }

  if (!is_front_page()) {
    wp_safe_redirect(home_url('/'));
    exit;
  }
}

/**
 * Redirects wordpress admin login page
 *
 * @author Richard Lemke <richard@mrhide.de>
 * @since 0.9.0
 */
add_action('init', 'xfee_redirect_wp_login');
function xfee_redirect_wp_login() {
  global $pagenow;
  if ($pagenow === 'wp-login.php' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    wp_safe_redirect(home_url('/'));
    exit;
  }
}

/**
 * Creates the required pages, if they don't exist
 *
 * @return integer Stock count
 *
 * @author Richard Lemke <richard@mrhide.de>
 * @since 0.9.0
 */
add_action('after_switch_theme', 'xfee_check_required_pages');
function xfee_check_required_pages() {

  $pages = [
    [
      'title'    => 'Shifts',
      'slug'     => 'shifts',
      'template' => 'template-shifts.php',
    ],
    [
      'title'    => 'Goodies',
      'slug'     => 'goodies',
      'template' => 'template-goodies.php',
    ],
    [
      'title'    => 'Profile',
      'slug'     => 'profile',
      'template' => 'template-profile.php',
    ],
    [
      'title'    => 'Login',
      'slug'     => 'login',
      'template' => 'template-login.php',
    ],
  ];

  foreach ($pages as $page) {

    $existing_page = get_page_by_path($page['slug']);

    if (!$existing_page) {

      $page_id = wp_insert_post([
        'post_title'  => $page['title'],
        'post_name'   => $page['slug'],
        'post_status' => 'publish',
        'post_type'   => 'page',
      ]);
    } else {
      $page_id = $existing_page->ID;
    }

    if ($page_id && !is_wp_error($page_id)) {

      update_post_meta(
        $page_id,
        '_wp_page_template',
        $page['template']
      );

      if ($page['slug'] === 'login') {
        update_option('show_on_front', 'page');
        update_option('page_on_front', $page_id);
        update_option('blog_public', 0);
      }
    }
  }
}
