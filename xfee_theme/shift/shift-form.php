<?php

/**
 * Form functions for shifts, like sign-up, cancel, set to done
 * It's handled with admin-ajax
 *
 * @package    xfee_theme
 * @author     Richard Lemke
 * @link       https://mrhide.de
 * @license    GPL-3.0 or later
 */

/**
 * Prints shift forms
 * For: Adding and removing acorns, removing pick-ups
 *
 * @param array $args
 *
 * @return string One form
 *
 * @author Richard Lemke <richard@mrhide.de>
 * @since 0.9.0
 */
function get_shift_action_form(array $args) {
  if (!is_user_logged_in()) return '';

  $defaults = [
    'id'           => 0,
    'date'         => 0,
    'signature'    => '',
    'compensation' => 0,
    'weekday'      => '',
    'done'         => 0,
    'assigned'     => '',
    'status'       => '',
  ];
  $args = wp_parse_args($args, $defaults);

  ob_start();
?>

  <!-- action="<?php echo esc_url(admin_url('admin-post.php')); ?>"  -->
  <form method="post"
    data-ajax_url="<?php echo esc_url(admin_url('admin-ajax.php')); ?>"
    class="sign_up" data-assigned="<?php echo $args['assigned']; ?>" data-status="<?php echo $args['status']; ?>">

    <input type="hidden" name="action" value="handle_shift_action">

    <?php wp_nonce_field('shift_booking', 'shift_booking_nonce'); ?>

    <input type="hidden" name="shift_id" value="<?php echo esc_attr($args['id']); ?>">
    <input type="hidden" name="shift_date" value="<?php echo esc_attr($args['date']); ?>">
    <input type="hidden" name="compensation" value="<?php echo esc_attr($args['compensation']); ?>">
    <input type="hidden" name="weekday" value="<?php echo esc_attr($args['weekday']); ?>">
    <input type="hidden" name="done" value="<?php echo esc_attr($args['done']); ?>">
    <input type="hidden" name="signature" value="<?php echo esc_attr($args['signature']); ?>">

    <?php if ($args['assigned'] === 'booked') { ?>

      <?php if ($args['status'] === 'pre_process' || $args['status'] === 'queue_process' || $args['status'] === 'begin_process') {
        // TODO: Too many stati
      ?>

        <div class="acorn_sign"><?php echo $args['compensation']; ?> <span class="acorn_icon"></span></div>
        <input type="hidden" name="shift_booking_action" value="cancel">
        <button type="submit" data-value="cancel"></button>

      <?php } else if ($args['status'] === 'end_process' || $args['status'] === 'post_process') { ?>

        <input type="hidden" name="shift_booking_action" value="finish">
        <div class="acorn_sign"><?php echo $args['compensation']; ?> <span class="acorn_icon"></span></div>
        <button type="submit" data-value="finish"></button>

      <?php } else if ($args['status'] === 'done') { ?>

        <button class="done success">
          <div class="acorn_sign"><?php echo $args['compensation']; ?> <span class="acorn_icon"></span></div>
        </button>

      <?php } else { ?>

        <button class="done">running</button>

      <?php } ?>

    <?php } else if ($args['assigned'] === 'assigned') { ?>

      <?php if ($args['status'] === 'done') { ?>

        <button class="done success">done</button>

      <?php } else { ?>

        <button class="done">assigned</button>

      <?php } ?>

    <?php } else if ($args['assigned'] === 'unassigned') { ?>

      <?php if ($args['status'] === 'pre_process') { ?>

        <div class="acorn_sign"><?php echo $args['compensation']; ?> <span class="acorn_icon"></span></div>
        <input type="hidden" name="shift_booking_action" value="sign-up">
        <button type="submit" data-value="sign-up"></button>

      <?php } else if ($args['status'] === 'queue_process' || $args['status'] === 'begin_process') { ?>

        <div class="acorn_sign"><?php echo $args['compensation']; ?> <span class="acorn_icon"></span></div>
        <input type="hidden" name="shift_booking_action" value="sign-up">
        <button type="submit" data-value="sign-up !!!"></button>

      <?php } else { ?>

        <button class="done failed">wasn't done</button>
      <?php } ?>

    <?php } else {
      error_log('Shift-form error: $assigned does not match.');
    } ?>

  </form>

<?php
  return ob_get_clean();
}

/**
 * Handles shift actions
 *
 * @return void
 *
 * @author Richard Lemke <richard@mrhide.de>
 * @since 0.9.0
 */

add_action('wp_ajax_handle_shift_action', 'handle_shift_action');
add_action('wp_ajax_nopriv_handle_shift_action', 'handle_shift_action');

// add_action('admin_post_handle_shift_action', 'handle_shift_action'); // Only for login users
function handle_shift_action() {
  error_log('######### handle_shift_action');
  if (!is_user_logged_in()) {
    wp_send_json_error([
      'message' => 'You are not logged in.'
    ], 401);
  }

  $required_fields = [
    'shift_booking_action',
    'shift_id',
    'shift_date',
    'signature',
    'shift_booking_nonce'
  ];

  foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || $_POST[$field] === '') {
      wp_send_json_error([
        'message' => 'Missing field: ' . $field
      ], 400);
    }
  }

  error_log('######### Fehlendes Feld');

  $action = sanitize_text_field(wp_unslash($_POST['shift_booking_action']));
  if (!in_array($action, ['sign-up', 'cancel', 'finish', 'done'], true)) {
    return;
  }

  if (!wp_verify_nonce(
    sanitize_text_field(
      wp_unslash($_POST['shift_booking_nonce'])
    ),
    'shift_booking'
  )) {
    wp_die('Invalid request.');
  }
  error_log('######### wp_verify_nonce');
  $shift_id     = absint($_POST['shift_id']);
  $shift_date   = sanitize_text_field(wp_unslash($_POST['shift_date']));
  $compensation = sanitize_text_field(wp_unslash($_POST['compensation']));
  $signature    = sanitize_text_field(wp_unslash($_POST['signature']));

  // Check shift id
  if (
    !$shift_id
    || get_post_type($shift_id) !== 'shift'
    || get_post_status($shift_id) !== 'publish'
  ) {
    wp_die('Couldn\'t find shift.');
  }
  error_log('######### $shift_id');

  $fields = get_post_meta($shift_id, 'shift_fields', true);
  // Check for occurrences
  if (!is_array($fields['dates']) || !array_key_exists($shift_date, $fields['dates'])) {
    error_log('EEEEEEEEE Couldn\'t find shift occurrence. $shift_date: ' . $shift_date);
  }
  // Compare to date
  if ($fields['dates'][$shift_date]['date'] !== $shift_date) {
    error_log('EEEEEEEEE Couldn\'t find shift occurrence. (date match error)');
  }

  // Compare to signature
  if ($fields['dates'][$shift_date]['signature'] !== $signature) {
    error_log('EEEEEEEEE Couldn\'t find shift occurrence. (signature match error: ' . $fields['dates'][$shift_date]['signature'] . ' != ' . $signature . ')');
  }

  $user_id = get_current_user_id();
  error_log('######### $user_id');
  $next_action = '';

  switch ($action) {
    case 'sign-up':
      if ($fields['dates'][$shift_date]['assign_id'] === 0) {
        $fields['dates'][$shift_date]['assign_id'] = $user_id;
        update_shift_stats();
        $next_action = 'cancel';
      }
      break;

    case 'cancel':
      if ($fields['dates'][$shift_date]['assign_id'] === $user_id) {
        $fields['dates'][$shift_date]['assign_id'] = 0;
        update_shift_stats();
        $next_action = 'sign-up';
      }
      break;

    case 'finish':
      $finished = compensate_shift($user_id, $signature, $compensation);
      if ($finished === true && $fields['dates'][$shift_date]['assign_id'] === $user_id) {
        $fields['dates'][$shift_date]['done'] = true;
        $fields['dates'][$shift_date]['signature'] = '';
        update_shift_stats();
        $next_action = 'done';
      }
      break;
  }

  update_post_meta($shift_id, 'shift_fields', $fields);
  update_shift_stats();
  error_log('######### durch');

  // $redirect_url = wp_get_referer();
  // wp_safe_redirect($redirect_url);
  // exit;
  wp_send_json_success([
    'message' => 'Vielen Dank! Deine Anfrage wurde übermittelt.',
    'next_action' => $next_action
  ]);
}
