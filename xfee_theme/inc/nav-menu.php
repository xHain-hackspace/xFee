<?php

/**
 * xFee nav menu
 *
 * @package    xfee_theme
 * @author     Richard Lemke
 * @link       https://mrhide.de
 * @license    GPL-3.0 or later
 *
 * @var array $all_users List of all users.
 */

/**
 * Prints user-role dependend nav-menu
 *
 * @author Richard Lemke <richard@mrhide.de>
 * @since 0.9.0
 */
function print_xfee_nav_menu() {
  if (!is_user_logged_in()) return;
  $user = wp_get_current_user();

  if (in_array('administrator', $user->roles, true)) {
    $menu = 'menu_admin';
  } elseif (in_array('editor', $user->roles, true)) {
    $menu = 'menu_editor';
  } elseif (in_array('author', $user->roles, true)) {
    $menu = 'menu_author';
  } else {
    return;
  }

  $menus = [
    'menu_author' => [
      ['title' => 'Shifts',       'url' => home_url('/shifts/'),  'class' => ''],
      ['title' => 'Profile',      'url' => home_url('/profile/'), 'class' => ''],
      ['title' => 'Goodies',      'url' => home_url('/goodies/'), 'class' => ''],
      ['title' => 'Logout',       'url' => wp_logout_url(home_url('/')), 'class' => 'new_nav_section'],
    ],
    'menu_editor' => [
      ['title' => 'Shifts',       'url' => home_url('/shifts/'),  'class' => ''],
      ['title' => 'Profile',      'url' => home_url('/profile/'), 'class' => ''],
      ['title' => 'Goodies',      'url' => home_url('/goodies/'), 'class' => ''],
      ['title' => 'Pick-ups',     'url' => admin_url('admin.php?page=pick_up_list'),  'class' => 'new_nav_section'],
      ['title' => 'Edit shifts',  'url' => admin_url('edit.php?post_type=shift'),     'class' => ''],
      ['title' => 'Edit goodies', 'url' => admin_url('edit.php?post_type=goodie'),    'class' => ''],
      ['title' => 'Logout',       'url' => wp_logout_url(home_url('/')), 'class' => 'new_nav_section'],
    ],
    'menu_admin' => [
      ['title' => 'Shifts',       'url' => home_url('/shifts/'),  'class' => ''],
      ['title' => 'Profile',      'url' => home_url('/profile/'), 'class' => ''],
      ['title' => 'Goodies',      'url' => home_url('/goodies/'), 'class' => ''],
      ['title' => 'Logout',       'url' => wp_logout_url(home_url('/')), 'class' => ''],
      ['title' => 'Admin',        'url' => admin_url('admin.php?page=admin_list'),    'class' => 'new_nav_section'],
      ['title' => 'Pick-ups',     'url' => admin_url('admin.php?page=pick_up_list'),  'class' => ''],
      ['title' => 'Edit shifts',  'url' => admin_url('edit.php?post_type=shift'),     'class' => ''],
      ['title' => 'Edit goodies', 'url' => admin_url('edit.php?post_type=goodie'),    'class' => ''],
      ['title' => 'Logout',       'url' => wp_logout_url(home_url('/')), 'class' => 'new_nav_section'],
    ],
  ];

  $current_url = untrailingslashit(home_url(add_query_arg([], $GLOBALS['wp']->request)));

  echo '<nav class="main-navigation">';
  echo '<ul class="menu">';

  foreach ($menus[$menu] as $item) {
    $is_current = $current_url === untrailingslashit($item['url']);
    $classes = [$item['class']];
    if ($is_current) {
      $classes[] = 'current_page_item';
    }

    echo '<li class="' . esc_attr(implode(' ', $classes)) . '">';
    echo '<a href="' . esc_url($item['url']) . '"';
    if ($is_current) echo ' aria-current="page"';
    echo '>' . esc_html($item['title']) . '</a>';
    echo '</li>';
  }

  echo '</ul>';
  echo '</nav>';
}
