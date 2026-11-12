<?php

/**
 * Adds columns for shifts to admin screen
 *
 * @package    xfee_theme
 * @author     Richard Lemke
 * @link       https://mrhide.de
 * @license    GPL-3.0 or later
 */

// Hide admin bar
add_filter('show_admin_bar', '__return_false');

/**
 * Removes some menus by page
 *
 * @param string $action from $_POST['shift_booking_action']
 * @param integer $shift_id from $_POST['shift_id']
 * @param string $shift_date from $_POST['shift_date']
 * @param integer $compensation from $_POST['compensation']
 * @param string $signature from $_POST['signature']
 * @return void
 * @since 0.9.0
 */
add_action('admin_menu', 'wpdocs_remove_menus');
function wpdocs_remove_menus() {
  remove_menu_page('edit.php');                   //Posts
  remove_menu_page('upload.php');                 //Media
  remove_menu_page('edit-comments.php');          //Comments
  remove_menu_page('plugins.php');                //Plugins
}


/*
* TODO: CHECK THESE !
*/

add_action('admin_init', function () {
  // Redirect any user trying to access comments page
  global $pagenow;

  if ($pagenow === 'edit-comments.php') {
    wp_safe_redirect(admin_url());
    exit;
  }

  // Remove comments metabox from dashboard
  remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');

  // Disable support for comments and trackbacks in post types
  foreach (get_post_types() as $post_type) {
    if (post_type_supports($post_type, 'comments')) {
      remove_post_type_support($post_type, 'comments');
      remove_post_type_support($post_type, 'trackbacks');
    }
  }
});

// Close comments on the front-end
add_filter('comments_open', '__return_false', 20, 2);
add_filter('pings_open', '__return_false', 20, 2);

// Hide existing comments
add_filter('comments_array', '__return_empty_array', 10, 2);

// Remove comments page in menu
add_action('admin_menu', function () {
  remove_menu_page('edit-comments.php');
});

// Remove comments links from admin bar
add_action('init', function () {
  if (is_admin_bar_showing()) {
    remove_action('admin_bar_menu', 'wp_admin_bar_comments_menu', 60);
  }
});


/**
 * Disable media uploads for all WordPress roles.
 *
 * @return void
 */
add_action('init', 'xfee_disable_media_uploads', 1);
function xfee_disable_media_uploads(): void {
  foreach (wp_roles()->roles as $role_name => $role_data) {
    $role = get_role($role_name);

    if ($role && $role->has_cap('upload_files')) {
      $role->remove_cap('upload_files');
    }
  }
}

/**
 * Block direct access to the WordPress media upload endpoint.
 *
 * @return void
 */
add_action('admin_init', 'xfee_block_media_upload_endpoint');
function xfee_block_media_upload_endpoint(): void {
  if (
    isset($GLOBALS['pagenow'])
    && $GLOBALS['pagenow'] === 'async-upload.php'
  ) {
    wp_die(
      'Media uploads are disabled.',
      'Upload disabled',
      ['response' => 403]
    );
  }
}

/**
 * Disable media creation through the WordPress REST API.
 *
 * @param mixed           $result  The current REST API response.
 * @param WP_REST_Server  $server  The REST API server.
 * @param WP_REST_Request $request The REST API request.
 * @return mixed|WP_Error
 */
function xfee_disable_rest_media_uploads($result, $server, $request) {
  if (
    $request->get_method() === 'POST'
    && $request->get_route() === '/wp/v2/media'
  ) {
    return new WP_Error(
      'xfee_media_uploads_disabled',
      'Media uploads are disabled.',
      ['status' => 403]
    );
  }

  return $result;
}

add_filter('rest_pre_dispatch', 'xfee_disable_rest_media_uploads', 10, 3);
