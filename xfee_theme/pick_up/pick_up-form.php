<?php

/**
 * Pick-up form and actions
 *
 * @package    xfee_theme
 * @author     Richard Lemke
 * @link       https://mrhide.de
 * @license    GPL-3.0 or later
 */

/**
 * Prints pick-up forms
 * For: Adding and removing acorns, removing pick-ups
 *
 * @param string $action
 * @return string One form
 * @since 0.9.0
 */
function get_pick_up_action_form($action) {
  if (!is_user_logged_in()) return '';

  $user_name  = '';
  $user_id    = 0;
  if (isset($_GET['code'])) {
    $code       = $_GET['code'];
    $user_id    = substr($code, 0, -3);
    $user_name  = get_user_field($user_id, 'user_login');
  }

  $user_pick_ups = get_user_pick_ups($user_id);
  $pick_up = [];
  foreach ($user_pick_ups as $user_pick_up) {
    if (array_key_exists('code', $user_pick_up) && (int)$user_pick_up['code'] === (int)$code) {
      $pick_up = $user_pick_up;
    }
  }

  ob_start();
?>

  <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="admin_form">
    <input type="hidden" name="action" value="handle_pick_up_action">

    <?php wp_nonce_field('pick_up_action', 'pick_up_action_nonce'); ?>

    <?php if ($action === 'get pick-up') { ?>

      <input type="number" name="code" value="" placeholder="code">
      <button type="submit" name="pick_up_action" value="get pick-up"></button>

    <?php } else if ($action === 'wrong code') { ?>

      <div id="ready_for_pick_up">Code does not match.</div>
      <input type="number" name="code" value="" placeholder="code">
      <button type="submit" name="pick_up_action" value="get pick-up"></button>

    <?php } else if ($action === 'confirm pick-up') { ?>

      <div id="ready_for_pick_up"><span>Pick-up for <?php echo $user_name; ?></span><span><?php echo $pick_up['goodie_name']; ?> <?php echo $pick_up['option']; ?></span></div>
      <input type="hidden" name="user_id" value="<?php echo esc_attr($user_id); ?>">
      <input type="hidden" name="code" value="<?php echo esc_attr($code); ?>">
      <button type="submit" name="pick_up_action" value="confirm pick-up"></button>
      <button type="submit" name="pick_up_action" value="cancel"></button>

    <?php } else if ($action === 'undo pick-up') { ?>

      <button type="submit" name="pick_up_action" value="undo pick-up"></button>

    <?php } else {
      error_log('Pick-up-form error: No matching action provided. $action: ' . $action);
    } ?>

  </form>

<?php
  return ob_get_clean();
}

/**
 * Handles pick-up actions
 *
 * @return void
 * @since 0.9.0
 */
add_action('admin_post_handle_pick_up_action', 'handle_pick_up_action'); // Only for login users
function handle_pick_up_action() {

  if (
    !is_user_logged_in()
    || !isset(
      $_POST['pick_up_action'],
      $_POST['pick_up_action_nonce']
    )
  ) {
    return;
  }

  $action = sanitize_text_field(
    wp_unslash($_POST['pick_up_action'])
  );

  if (!in_array($action, ['get pick-up', 'confirm pick-up', 'undo pick-up', 'cancel'], true)) {
    return;
  }

  if (!wp_verify_nonce(
    sanitize_text_field(
      wp_unslash($_POST['pick_up_action_nonce'])
    ),
    'pick_up_action'
  )) {
    wp_die('Invalid request.');
  }

  $code     = '';
  $user_id  = 0;
  if (isset($_POST['code'])) {
    $code     = absint(wp_unslash($_POST['code']));
    $user_id  = substr($code, 0, -3);
  } else if (isset($_POST['user_id'])) {
    $user_id  = absint(wp_unslash($_POST['user_id']));
  }
  $next_action = '';
  error_log('$action: ' . $action);
  switch ($action) {

    case 'get pick-up':
      $code     = absint(wp_unslash($_POST['code']));
      $success  = check_for_pick_up($user_id, $code);
      if ($success) {
        $next_action = 'confirm pick-up';
      } else {
        $next_action = 'wrong code';
      }
      break;

    case 'confirm pick-up':
      $code = absint(wp_unslash($_POST['code']));
      confirm_pick_up($user_id, $code);
      $next_action = 'get pick-up';
      break;

    case 'undo pick-up':
      restore_last_pick_up();
      $next_action = 'get pick-up';
      break;

    case 'cancel':
      error_log('cancel');
      cancel_current_pick_up();
      $next_action = 'get pick-up';
      break;
  }

  $redirect_url = wp_get_referer();
  $redirect_url = add_query_arg([
    'next_action' => $next_action,
    'code' => $code,
  ], $redirect_url);


  wp_safe_redirect($redirect_url);
  exit;
}
