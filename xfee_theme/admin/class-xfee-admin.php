<?php

/**
 * Admin class
 *
 * @package    xfee_theme
 * @author     Richard Lemke
 * @link       https://mrhide.de
 * @license    GPL-3.0 or later
 */

class xFee_Admin {

  /**
   * Initialize the admin module.
   */
  public function __construct() {

    if (!is_admin()) {
      return;
    }

    $this->restrict_backend_access();

    $this->register_hooks();
  }

  /**
   * Register admin-specific hooks
   */
  private function register_hooks() {
    add_action('admin_menu', [$this, 'register_frontend_menu']);
    add_action('admin_menu', [$this, 'register_admin_menu']);
    add_action('admin_init', [$this, 'redirect_frontend_admin_menu']);
    add_action('admin_post_handle_admin_action', [$this, 'handle_admin_action']);
    add_action('admin_menu', [$this, 'restrict_editor_menus'], 999);
  }

  /**
   * Restricts backend access for author
   */
  private function restrict_backend_access() {
    if (current_user_can('administrator')) return;
    if (wp_doing_ajax()) return;
    if (defined('REST_REQUEST') && REST_REQUEST) return;

    if (
      isset($GLOBALS['pagenow']) &&
      in_array(
        $GLOBALS['pagenow'],
        ['admin-post.php', 'admin-ajax.php', 'wp-login.php'],
        true
      )
    ) {
      return;
    }

    $user = wp_get_current_user();

    if (in_array('author', $user->roles, true)) {
      wp_safe_redirect(home_url('/shifts/'));
      exit;
    }
  }

  /**
   * Editor admin menu
   */
  private $editor_menus = [
    'go_to_frontend',
    'pick_up_list',
    'edit.php?post_type=shift',
    'post-new.php?post_type=shift',
    'edit.php?post_type=goodie',
    'edit.php?post_type=pick_up',
  ];

  /**
   * Edits admin menu for editor
   */
  public function restrict_editor_menus() {
    $user = wp_get_current_user();
    if (!in_array('editor', $user->roles, true)) return;

    global $menu;
    foreach ($menu as $key => $item) {
      $slug = $item[2];
      if (!in_array($slug, $this->editor_menus, true)) {
        remove_menu_page($slug);
      }
    }
  }

  /**
   * Register the frontend link in the admin menu
   */
  public function register_frontend_menu() {
    add_menu_page(
      'Frontend',
      'Frontend',
      'read',
      'go_to_frontend',
      '__return_null',
      'dashicons-admin-home',
      2
    );
  }

  /**
   * Register the custom admin page
   */
  public function register_admin_menu() {

    add_menu_page(
      'Admin',
      'Admin',
      'publish_posts',
      'admin_list',
      [$this, 'render_admin_page'],
      'dashicons-hammer',
      2
    );
  }

  /**
   * Redirect to the frontend
   */
  public function redirect_frontend_admin_menu() {
    if (isset($_GET['page']) && $_GET['page'] === 'go_to_frontend') {
      wp_safe_redirect(home_url('/shifts/'));
      exit;
    }
  }

  /**
   * Render the custom admin page
   */
  public function render_admin_page() {
    if (!current_user_can('manage_options')) {
      wp_die('You do not have permission to access this page.');
    }
    $all_users = $this->get_all_users();
    require get_template_directory() . '/admin/admin-template.php';
  }

  /**
   * Handles admin actions
   *
   * @return void
   *
   * @author Richard Lemke <richard@mrhide.de>
   * @since 0.9.0
   */
  public function handle_admin_action() {
    if (
      !is_user_logged_in()
      || !isset(
        $_POST['admin_action'],
        $_POST['user_id'],
        $_POST['admin_action_nonce']
      )
    ) {
      return;
    }

    $action = sanitize_text_field(
      wp_unslash($_POST['admin_action'])
    );

    if (!in_array($action, ['add acorn', 'remove acorn', 'remove pick-up'], true)) {
      return;
    }

    if (!wp_verify_nonce(
      sanitize_text_field(
        wp_unslash($_POST['admin_action_nonce'])
      ),
      'admin_action'
    )) {
      wp_die('Invalid request.');
    }

    $user_id = absint(wp_unslash($_POST['user_id']));

    switch ($action) {
      case 'add acorn':
        $this->add_acorn_to_user($user_id);
        break;
      case 'remove acorn':
        $index = (int) wp_unslash($_POST['index']) ?? -1;
        $this->remove_user_acorn($user_id, $index);
        break;
      case 'remove pick-up':
        $index = (int) wp_unslash($_POST['index']) ?? -1;
        $this->remove_user_pick_up($user_id, $index);
        break;
    }

    $redirect_url = wp_get_referer();

    $redirect_url = add_query_arg(
      'target_user',
      $user_id,
      $redirect_url
    );

    wp_safe_redirect($redirect_url);
    exit;
  }

  /**
   * Get all users
   *
   * @return array List with all users
   *
   * @author Richard Lemke <richard@mrhide.de>
   * @since 0.9.0
   */
  private function get_all_users() {
    $users            = get_users(array('role__in' => array('author', 'editor', 'administrator')));
    $all_users        = [];
    $user_shift_count = 0;
    $user_shifting    = false;

    foreach ($users as $user) {
      $user_shifts = get_user_shifts($user->ID);

      foreach ($user_shifts as $key => $shift) {
        if ($shift['assign_id'] === $user->ID) {
          $user_shift_count++;
        }
      }

      $user_hoard = get_user_hoard($user->ID);
      $pick_ups = get_user_pick_ups($user->ID);
      $all_users[] = [
        'id'            => $user->ID,
        'name'          => $user->user_login, // Options: user_login, display_name, user_nicename
        'user_email'    => $user->user_email,
        'signed_count'  => $user_shift_count,
        'acorn'         => count($user_hoard),
        'hoard'         => $user_hoard,
        'pick_up_count' => count($pick_ups),
        'pick_ups'      => $pick_ups,
      ];
    }

    return $all_users;
  }

  /**
   * Creates an acorn and adds it to user hoard
   * Artifical acorn signatures have a zero in place of the goodie id, e.g. 2026-10-29-0-1234
   *
   * @param integer $user_id
   *
   * @return void
   *
   * @author Richard Lemke <richard@mrhide.de>
   * @since 0.9.0
   */
  private function add_acorn_to_user($user_id) {
    if (!current_user_can('manage_options') || $user_id === 0) return;
    $signature    = date('Y-m-d') . '-0-' . rand(0, 10000);
    $user_hoard   = get_user_hoard($user_id);
    $user_hoard[] = $signature;
    update_user_meta($user_id, 'user_hoard', $user_hoard);
  }

  /**
   * Removes a certain acorn signature from user hoard
   *
   * @param integer $user_id
   * @param integer $index  Index of acorn signature
   *
   * @return void
   *
   * @author Richard Lemke <richard@mrhide.de>
   * @since 0.9.0
   */
  private function remove_user_acorn($user_id, $index) {
    if (!current_user_can('manage_options') || $index < 0) return;
    $user_hoard = get_user_meta($user_id, 'user_hoard', true);
    array_splice($user_hoard, $index, $index + 1);
    update_user_meta($user_id, 'user_hoard', $user_hoard);
  }

  /**
   * Removes a certain pick-up from user pick-up
   *
   * @param integer $user_id
   * @param integer $index  Index of pick-up
   *
   * @return void
   *
   * @author Richard Lemke <richard@mrhide.de>
   * @since 0.9.0
   */
  private function remove_user_pick_up($user_id, $index) {
    // TODO: move to pick-up functons
    if (!current_user_can('manage_options') || $index < 0) return;

    // Get user pick-ups
    $user_pick_ups  = get_user_meta($user_id, 'user_pick_ups', true);
    $goodie_id      = $user_pick_ups[$index]['goodie_id'];
    $goodie_name    = $user_pick_ups[$index]['goodie_name'];
    $goodie_option  = $user_pick_ups[$index]['option'];
    $reserve        = $user_pick_ups[$index]['acorns'];
    $code           = $user_pick_ups[$index]['code'];

    // Remove pick-up from goodie
    $goodie_fields = get_post_meta($goodie_id, 'goodie_fields', true) ?? [];
    $removed = false;
    foreach ($goodie_fields['pick_ups'] as $key => $pick_up) {
      if ($pick_up['user_id'] === $user_id && $pick_up['reserve'] === $reserve) {
        $transfer_acorns = $pick_up['acorns'];
        unset($goodie_fields['pick_ups'][$key]);
        $removed = true;
        break;
      }
    }
    if (!$removed) {
      error_log('Admin-functions error: Could not remove pick-up from goodie. (goodie_id: ' . $goodie_id . ')');
      return false;
    }

    // Return acorns to user
    $user_hoard = get_user_hoard($user_id);
    $user_hoard = array_merge($user_hoard, $transfer_acorns);
    update_user_hoard($user_id, $user_hoard);

    // Remove pick-up from user
    unset($user_pick_ups[$index]);
    update_user_meta($user_id, 'user_pick_ups', $user_pick_ups);

    // Add to stock
    $stock_count = (int)$goodie_fields['options'][$goodie_option];
    $goodie_fields['options'][$goodie_option] = $stock_count + 1;
    update_post_meta($goodie_id, 'goodie_fields', $goodie_fields);
  }


  /**
   * Returns admin forms
   * For: Adding and removing acorns, removing pick-ups
   *
   * @param string   $action  add acorn, remove acorn, remove pick-up
   * @param integer $user_id
   *
   * @return string One form
   *
   * @author Richard Lemke <richard@mrhide.de>
   * @since 0.9.0
   */
  public function get_admin_action_form($action, $user_id) {
    if (!is_user_logged_in()) {
      return '';
    }
    ob_start();
?>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="admin_form">

      <input type="hidden" name="action" value="handle_admin_action">

      <?php wp_nonce_field('admin_action', 'admin_action_nonce'); ?>

      <?php if ($action === 'add acorn') { ?>

        <input type="hidden" name="user_id" value="<?php echo esc_attr($user_id); ?>">
        <button type="submit" name="admin_action" value="add acorn"></button>

      <?php } else if ($action === 'remove acorn') { ?>

        <input type="hidden" name="user_id" value="<?php echo esc_attr($user_id); ?>">
        <input type="number" name="index" value="" placeholder="index">
        <button type="submit" name="admin_action" value="remove acorn"></button>

      <?php } else if ($action === 'remove pick-up') { ?>

        <input type="hidden" name="user_id" value="<?php echo esc_attr($user_id); ?>">
        <input type="number" name="index" value="" placeholder="index">
        <button type="submit" name="admin_action" value="remove pick-up"></button>

      <?php } else {
        error_log('Admin-form error: No action provided.');
      } ?>

    </form>

<?php
    return ob_get_clean();
  }

  /**
   * Prints white-space for code html element TODO: Move somewhere else
   *
   * @param integer $count Amount of white-space charactere
   *
   * @return void
   *
   * @author Richard Lemke <richard@mrhide.de>
   * @since 0.9.0
   */
  public function get_white_space($count = 0) {
    $whitespace = '';
    for ($i = 0; $i < $count; $i++) {
      $whitespace .= ' ';
    }
    echo $whitespace;
  }

  /**
   * Returns indent for code html element TODO: Move somewhere else
   * By decimal places
   *
   * @param integer $key Number to indent
   *
   * @return string White-space
   *
   * @author Richard Lemke <richard@mrhide.de>
   * @since 0.9.0
   */
  public function get_white_space_decimal($key = 0) {
    $test_key     = $key;
    $wh_sp_count  = $key === 0 ? 3 : 4;
    while ($test_key >= 1 && $wh_sp_count > 0) {
      $test_key = $test_key / 10;
      $wh_sp_count--;
    }
    $whitespace = '';
    for ($i = 0; $i < $wh_sp_count; $i++) {
      $whitespace .= ' ';
    }
    return $whitespace;
  }

  /**
   * Truncate strings in an array if they exceed the maximum length
   *
   * @param array $array The input array
   * @param int   $max_length Maximum string length
   *
   * @return array The processed array
   *
   * @author Richard Lemke <richard@mrhide.de>
   * @since 0.9.0
   */
  public function truncate_array_strings(array $array, int $max_length = 20): array {

    foreach ($array as $key => $value) {
      if (is_array($value)) {
        $array[$key] = $this->truncate_array_strings($value, $max_length);
      } else if (is_string($value)) {
        $value = strip_tags($value);
        $value = preg_replace('/\s+/u', ' ', $value);
        if (mb_strlen($value) > $max_length) {
          $array[$key] = mb_substr($value, 0, $max_length - 3) . '...';
        } else {
          $array[$key] = $value;
        }
      }
    }

    return $array;
  }
}
