<?php

/**
 * Shifts functions
 *
 * @package    xfee_theme
 * @author     Richard Lemke
 * @link       https://mrhide.de
 * @license    GPL-3.0 or later
 */

/**
 * Returns shift fields
 *
 * @param integer $shift_id
 * @return array All shifts
 * @since 0.9.0
 */
function get_shift_fields($shift_id) {
  $shift_fields = get_post_meta($shift_id, 'shift_fields', true);
  if (!is_array($shift_fields)) $shift_fields = [];
  return $shift_fields;
}

/**
 * Updates shift fields
 *
 * @param integer $shift_id
 * @param array $shift_fields
 * @return array All shifts
 * @since 0.9.0
 */
function update_shift_fields($shift_id, $shift_fields) {
  if (!is_array($shift_fields)) $shift_fields = [];
  update_post_meta($shift_id, 'shift_fields', $shift_fields);
}

/**
 * Returns an array with all shifts, meaning all occurrences
 *
 * @return array Each item is an occurence of a shift.
 * @since 0.9.0
 */
function get_all_shifts() {
  $all_shifts = [];

  $shifts = get_posts(array(
    'post_type'      => 'shift',
    'fields'         => 'ids',
    'posts_per_page' => -1,
    'post_status'    => 'publish',
    'order'          => 'ASC',
  ));

  foreach ($shifts as $shift_id) {
    $shift_fields = get_shift_fields($shift_id);

    foreach ($shift_fields['dates'] as $field) {

      [$assigned, $status] = get_shift_status([
        'date'      => $field['date'],
        'assign_id' => $field['assign_id'] ?? 0,
        'time_from' => $shift_fields['time_from'],
        'time_to'   => $shift_fields['time_to'],
        'done'      => $field['done'] ?? false,
      ]);

      $all_shifts[] = [
        'id'           => $shift_id,
        'name'         => $shift_fields['name'],
        'details_html' => $shift_fields['details_html'],
        'time_from'    => $shift_fields['time_from'],
        'time_to'      => $shift_fields['time_to'],
        'compensation' => $shift_fields['compensation'],
        'date'         => $field['date'],
        'signature'    => $field['signature'],
        'weekday'      => $field['weekday'],
        'assign_id'    => $field['assign_id'],
        'done'         => $field['done'],
        'assigned'     => $assigned,
        'status'       => $status,
      ];
    }
  }

  if (count($all_shifts) > 0) {

    uasort($all_shifts, function ($a, $b) {
      $result = strcmp($a['date'], $b['date']);

      if ($result === 0) {
        $result = strcmp($a['time_from'], $b['time_from']);
      }

      return $result;
    });

    return $all_shifts;
  } else {
    return [];
  }
}

/**
 * Return an array with all shift IDs for given date
 *
 * @param string $date
 * @param array $shifts
 * @return array All found shift ids
 * @since 0.9.0
 */
function find_shift_ids_for_date($date, $shifts) {
  $found_keys = [];
  foreach ($shifts as $key => $shift) {
    if ($date === $shift['date']) {
      $found_keys[$key] = $shift['time_from'];
    }
  }
  // Sort by time
  uasort($found_keys, function ($a, $b) {
    return strcmp($a, $b);
  });
  return $found_keys;
}

/**
 * Returns shift status for assignment and status
 * Assignments to other users are hidden
 * $assigned = unassigned, assigned, booked (current user is assigned)
 * $status = pre_process, queue_process, begin_process, in_process, end_process, post_process, done
 *
 * @param array $args
 * @return array [$assigned, $status]
 * @since 0.9.0
 */
function get_shift_status(array $args) {
  $defaults = [
    'date'      => '',
    'assign_id' => 0,
    'time_from' => '',
    'time_to'   => '',
    'done'      => false,
  ];
  $args = wp_parse_args($args, $defaults);

  $user_id  = get_current_user_id();
  $assigned = '';
  $status   = '';

  // Set assign
  if ($args['assign_id'] == null) {
    $assigned = 'unassigned';
  } else {
    if ($args['assign_id'] === $user_id) {
      $assigned = 'booked';
    } else {
      $assigned = 'assigned';
    }
  }

  // Set status // TODO: Change to switch, or use early returns
  $today = date('Y-m-d');
  if ($args['done'] === true) {
    $status = 'done';
  } else if ($today < $args['date']) {
    $status = 'pre_process';
  } else if ($today === $args['date']) {
    $now = date('H:i');
    $finish_before_end_min = date('H:i', strtotime('+' . $GLOBALS['xfee_config']['finish_before_end_min'] . ' minutes'));
    $cancel_after_start_min = date('H:i', strtotime('-' . $GLOBALS['xfee_config']['cancel_after_start_min'] . ' minutes'));
    if ($now < $args['time_from']) {
      $status = 'queue_process';
    } else {
      if ($now < $args['time_to']) {
        if ($cancel_after_start_min < $args['time_from']) {
          $status = 'begin_process';
        } else if ($finish_before_end_min > $args['time_to']) {
          $status = 'end_process';
        } else {
          $status = 'in_process';
        }
      } else {
        $status = 'post_process';
      }
    }
  } else {
    $status = 'post_process';
  }

  return [$assigned, $status];
}

/**
 * Returns an shift signature, format: {date}-{shift_id}-{random}"
 *
 * @param integer $shift_id
 * @param integer $date
 * @return string
 * @since 0.9.0
 */
function get_new_signature($shift_id, $date) {
  $random = rand(1, 1000);
  return $shift_id . '-' . $date . '-' . $random;
}

/**
 * Transfer acorns from shift to user
 *
 * @param integer $user_id
 * @param string $signature
 * @param integer $compensation
 * @return bool
 * @since 0.9.0
 */
function compensate_shift($user_id, $signature, $compensation) {
  $user_hoard = get_user_hoard($user_id);
  if (in_array($signature, $user_hoard)) {
    error_log('Shift-function error: Adding a non-unique signature to user hoard, rejected. User: ' . $user_id . ';');
    return false;
  }
  if ($compensation === 1) {
    $user_hoard[] = $signature;
  } else {
    $signature = substr($signature, 0, -1);
    while ($compensation > 0) {
      $user_hoard[] = $signature . $compensation;
      $compensation -= 1;
    }
  }
  $success = update_user_meta($user_id, 'user_hoard', $user_hoard);
  if ($success) {
    return true;
  } else {
    error_log('Shift-function error: Update of user hoard failed. User: ' . $user_id . ';');
    return false;
  }
}

/**
 * Removes shift occurrences that are older than 1 month
 * But only if set to done
 *
 * @param array $shifts  List with shifts
 * @return array Filtered list
 * @since 0.9.0
 */
function shift_remove_old_ones($shifts) {
  $cutoff = (new DateTimeImmutable('today'))
    ->modify('-1 month')
    ->format('Y-m-d');
  $shifts = array_filter(
    $shifts,
    function ($shift) use ($cutoff) {
      return !empty($shift['date'])
        && $shift['date'] >= $cutoff && $shift['done'] !== false;
    }
  );
  return $shifts;
}

/**
 * Renders a countdown element for a given date and time
 *
 * @param string $datetime Target date and time in "Y-m-d H:i" format
 * @param string $datetime_end End date and time
 * @return string HTML countdown element or an error message
 * @since 0.9.0
 */
function render_countdown($datetime, $datetime_end) {
  $target = DateTimeImmutable::createFromFormat(
    '!Y-m-d H:i',
    $datetime
    // ,
    // wp_timezone()
  );
  $end = DateTimeImmutable::createFromFormat(
    '!Y-m-d H:i',
    $datetime_end
    // ,
    // wp_timezone()
  );

  $errors = DateTimeImmutable::getLastErrors();
  if (!$target || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
    error_log('Invalid date or time');
    return 'Invalid date or time';
  }
  return sprintf(
    '<span class="countdown" data-target="%d" data-end="%d">Loading countdown...</span>',
    $target->getTimestamp(),
    $end->getTimestamp()
  );
}
