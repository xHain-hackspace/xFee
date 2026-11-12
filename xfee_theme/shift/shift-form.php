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
 * @return string One form
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

  <form method="post"
    data-ajax_url="<?php echo esc_url(admin_url('admin-ajax.php')); ?>"
    class="sign_up" data-assigned="<?php echo $args['assigned']; ?>" data-status="<?php echo $args['status']; ?>">
    <?php // TODO: Remove data-assigned and data-status, check js and css first
    ?>

    <?php wp_nonce_field('shift_booking', 'shift_booking_nonce'); ?>

    <input type="hidden" name="action" value="handle_shift_action">
    <input type="hidden" name="shift_id" value="<?php echo esc_attr($args['id']); ?>">
    <input type="hidden" name="shift_date" value="<?php echo esc_attr($args['date']); ?>">
    <input type="hidden" name="compensation" value="<?php echo esc_attr($args['compensation']); ?>">
    <input type="hidden" name="weekday" value="<?php echo esc_attr($args['weekday']); ?>">
    <input type="hidden" name="done" value="<?php echo esc_attr($args['done']); ?>">
    <input type="hidden" name="signature" value="<?php echo esc_attr($args['signature']); ?>">

    <input type="hidden" data-condition="form_sign" name="shift_booking_action" value="sign-up">
    <button type="submit" data-condition="form_sign" aria-label="sign-up"></button>

    <input type="hidden" data-condition="form_sign_urgent" name="shift_booking_action" value="sign-up" disabled>
    <button type="submit" data-condition="form_sign_urgent" aria-label="sign-up" disabled></button>

    <input type="hidden" data-condition="form_cancel" name="shift_booking_action" value="cancel" disabled>
    <button type="submit" data-condition="form_cancel" aria-label="cancel" disabled></button>

    <input type="hidden" data-condition="form_finish" name="shift_booking_action" value="finish" disabled>
    <button type="submit" data-condition="form_finish" aria-label="finish" disabled></button>

    <button class="done" data-condition="form_assigned" aria-label="assigned" disabled></button>

    <button class="done" data-condition="form_running" aria-label="running" disabled></button>

    <button class="done success" data-condition="form_done" aria-label="done" disabled></button>

    <button class="done failed" data-condition="form_failed" aria-label="was not done" disabled></button>

  </form>

<?php
  return ob_get_clean();
}

/**
 * Handles shift actions
 *
 * @return void
 * @since 0.9.0
 */

add_action('wp_ajax_handle_shift_action', 'handle_shift_action');
add_action('wp_ajax_nopriv_handle_shift_action', 'handle_shift_action');

function handle_shift_action() {
  if (!is_user_logged_in()) {
    wp_send_json_error([
      'message' => 'You are not logged in.'
    ], 401);
  }

  $success_msg = 'Thank you very much! Your request has been submitted.';

  // Field check
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
        'message' => 'Something went wrong, missing the field ' . $field . '.'
      ], 400);
    }
  }

  $action = sanitize_text_field(wp_unslash($_POST['shift_booking_action']));
  if (!in_array($action, ['sign-up', 'cancel', 'finish', 'done'], true)) {
    wp_send_json_error([
      'message' => 'Something went wrong, action did not match.'
    ], 401);
  }

  if (!wp_verify_nonce(
    sanitize_text_field(wp_unslash($_POST['shift_booking_nonce'])),
    'shift_booking'
  )) {
    wp_send_json_error([
      'message' => 'Something went wrong, it did not pass the test.'
    ], 401);
  }
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
    wp_send_json_error(['message' => 'Something went wrong, could not find a shift with the ID ' . $shift_id . '.'], 401);
  }

  $fields = get_post_meta($shift_id, 'shift_fields', true);
  // Check for occurrences
  if (!is_array($fields['dates']) || !array_key_exists($shift_date, $fields['dates'])) {
    wp_send_json_error(['message' => 'Something went wrong, could not find a shift occurrence for ' . $shift_date . '.'], 401);
  }
  // Compare to date
  if ($fields['dates'][$shift_date]['date'] !== $shift_date) {
    wp_send_json_error(['message' => 'Something went wrong, could not find a shift occurrence for ' . $shift_date . '.'], 401);
  }

  // Compare to signature
  if ($fields['dates'][$shift_date]['signature'] !== $signature) {
    wp_send_json_error(['message' => 'Something went wrong, the signatures did not match.'], 401);
  }

  $user_id = get_current_user_id();
  $next_action = '';

  switch ($action) {
    case 'sign-up':
      if ($fields['dates'][$shift_date]['assign_id'] === 0) {
        if (!user_has_shifts_left($user_id)) {
          wp_send_json_error(['message' => 'Your shift maximum is reached, finish a shift before you sign-up for a new one.'], 401);
        } else {
          $fields['dates'][$shift_date]['assign_id'] = $user_id;
          update_shift_stats($user_id);
          $next_action = 'cancel';
          $success_msg = 'You have signed a shift.';
        }
      } else {
        wp_send_json_error(['message' => 'Shift is already assigned, you were probably a bit too slow, better luck next time.'], 401);
      }
      break;

    case 'cancel':
      if ($fields['dates'][$shift_date]['assign_id'] === $user_id) {
        $fields['dates'][$shift_date]['assign_id'] = 0;
        update_shift_stats($user_id);
        $next_action = 'sign-up';
        $success_msg = 'You have canceled a shift.';
      }
      break;

    case 'finish':
      $finished = compensate_shift($user_id, $signature, $compensation);
      if ($finished === true && $fields['dates'][$shift_date]['assign_id'] === $user_id) {
        $fields['dates'][$shift_date]['done'] = true;
        $fields['dates'][$shift_date]['signature'] = '';
        update_shift_stats($user_id);
        $next_action = 'done';
        $success_msg = 'You have finished a shift. Awesome, thank you!';
      }
      break;
  }

  update_post_meta($shift_id, 'shift_fields', $fields);
  update_shift_stats();

  wp_send_json_success([
    'message' => $success_msg,
    'next_action' => $next_action
  ]);
}
