<?php

/**
 * Actions and form for goodies
 *
 * @package    xfee_theme
 * @author     Richard Lemke
 * @link       https://mrhide.de
 * @license    GPL-3.0 or later
 */


/**
 * Gets goodie form for pick
 *
 * @param integer $goodie_id
 * @param string $goodie_name
 * @param array $goodie_options
 * @param integer $user_id
 * @param integer $compensation
 * @param integer $in_stock
 *
 * @return string HTML
 *
 * @author Richard Lemke <richard@mrhide.de>
 * @since 0.9.0
 */
function get_order_form($goodie_id, $goodie_name, $goodie_options, $user_id, $compensation, $in_stock) {
  if (!is_user_logged_in()) {
    return '';
  }
  $funds = get_user_acorn_count($user_id);

  ob_start();
?>

  <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="order_form">

    <input type="hidden" name="action" value="handle_goodie_pick">

    <?php wp_nonce_field('goodie_booking', 'goodie_order_nonce'); ?>

    <input type="hidden" name="goodie_id" value="<?php echo esc_attr($goodie_id); ?>">
    <input type="hidden" name="goodie_name" value="<?php echo esc_attr($goodie_name); ?>">
    <input type="hidden" name="compensation" value="<?php echo esc_attr($compensation); ?>">
    <input type="hidden" name="user_id" value="<?php echo esc_attr($user_id); ?>">

    <div class="acorn_sign"><?php echo $compensation; ?> <span class="acorn_icon"></span></div>

    <?php if ($compensation > $funds) { ?>

      <button class="done">someday</button>

    <?php } else if ($in_stock <= 0) { ?>

      <button class="done">out of stock</button>

    <?php } else if ($in_stock > 0) { ?>

      <span>Option:</span>

      <select name="goodie_option">
        <option value="0">choose</option>
        <?php foreach ($goodie_options as $option_name => $count) {
          printf(
            '<option value="%s" %s>%s</option>',
            $option_name,
            $count === 0 ? 'disabled' : '',
            esc_html($option_name)
          );
        } ?>
      </select>

      <button type="submit" name="order_action" value="pick">pick</button>

    <?php } ?>

  </form>

<?php
  return ob_get_clean();
}

/**
 * Handles goodie actions: pick, cancel, pick up
 *
 * @param string $action from $_POST['order_action']
 * @param integer $goodie_id from $_POST['goodie_id']
 * @param string $goodie_name from $_POST['goodie_name']
 * @param string $goodie_option from $_POST['goodie_option']
 * @param integer $compensation from $_POST['compensation']
 * @param integer $user_id from $_POST['user_id']
 *
 * @return void
 *
 * @author Richard Lemke <richard@mrhide.de>
 * @since 0.9.0
 */
add_action('admin_post_handle_goodie_pick', 'handle_goodie_pick'); // Only for login users
function handle_goodie_pick() {
  if (!is_user_logged_in()) return;
  if (
    !isset(
      $_POST['order_action'],
      $_POST['goodie_id'],
      $_POST['goodie_name'],
      $_POST['goodie_option'],
      $_POST['compensation'],
      $_POST['user_id'],
      $_POST['goodie_order_nonce']
    ) && !isset(
      $_POST['order_action'],
      $_POST['code'],
      $_POST['goodie_order_nonce']
    )
  ) {
    return;
  }

  $action = sanitize_text_field(
    wp_unslash($_POST['order_action'])
  );

  error_log('$action: ' . $action);

  if (!in_array($action, ['pick', 'cancel'], true)) {
    return;
  }

  if (!wp_verify_nonce(
    sanitize_text_field(
      wp_unslash($_POST['goodie_order_nonce'])
    ),
    'goodie_booking'
  )) {
    wp_die('Invalid request.');
  }

  $success = 1;
  $feedback = '';

  if ($action === 'pick') {

    $goodie_id      = absint(wp_unslash($_POST['goodie_id'] ?? 0));
    $goodie_name    = sanitize_text_field(wp_unslash($_POST['goodie_name'] ?? ''));
    $goodie_option  = sanitize_text_field(wp_unslash($_POST['goodie_option'] ?? ''));
    $user_id        = sanitize_text_field(wp_unslash($_POST['user_id'] ?? 0));
    $compensation   = absint((wp_unslash($_POST['compensation'] ?? 0)));

    if (!$goodie_id || get_post_type($goodie_id) !== 'goodie' || get_post_status($goodie_id) !== 'publish') {
      wp_die('Couldn\'t find goodie.');
    }

    [$success, $feedback] = handle_user_pick([
      'user_id'       => $user_id,
      'compensation'  => $compensation,
      'goodie_id'     => $goodie_id,
      'goodie_name'   => $goodie_name,
      'goodie_option' => $goodie_option,
    ]);
  } else if ($action === 'cancel') {

    $code = absint((wp_unslash($_POST['code'] ?? 0)));
    handle_user_cancel($code);
  }

  $redirect_url = wp_get_referer();

  if ($feedback != '') {
    $redirect_url = add_query_arg(
      array(
        'feedback'  => $feedback,
        'success'   => $success,
      ),
      $redirect_url
    );
  } else {
    remove_query_arg(array('feedback', 'success'));
  }

  wp_safe_redirect($redirect_url);
  exit;
}

/**
 * Gets goodie form for cancel
 *
 * @param integer $code
 *
 * @return string HTML
 *
 * @author Richard Lemke <richard@mrhide.de>
 * @since 0.9.0
 */
function get_cancel_order_form($code) {
  if (!is_user_logged_in()) return '';

  ob_start();
?>

  <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="order_form">
    <?php wp_nonce_field('goodie_booking', 'goodie_order_nonce'); ?>
    <input type="hidden" name="action" value="handle_goodie_pick">
    <input type="hidden" name="code" value="<?php echo esc_attr($code); ?>">
    <button type="submit" name="order_action" value="cancel">cancel</button>
  </form>

<?php
  return ob_get_clean();
}
