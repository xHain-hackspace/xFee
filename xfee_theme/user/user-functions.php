<?php

/**
 * User functions
 *
 * @package    xfee_theme
 * @author     Richard Lemke
 * @link       https://mrhide.de
 * @license    GPL-3.0 or later
 */

/**
 * Returns a wordpress field for a user
 *
 * @param integer $user_id
 * @param string $field
 * @return mixed Chosen field
 * @since 0.9.0
 */
function get_user_field($user_id, $field) {
  $user = get_user_by('id', $user_id);
  if ($user === false) return false;
  return $user->data->$field;
}

/**
 * Returns the amount of user acorns
 *
 * @param integer $user_id
 * @return integer
 * @since 0.9.0
 */
function get_user_acorn_count($user_id) {
  $user_hoard = get_user_hoard($user_id);
  return count($user_hoard);
}

/**
 * Returns user acorns
 *
 * @param integer $user_id
 * @return array All acorn signatures
 * @since 0.9.0
 */
function get_user_hoard($user_id) {
  $user_hoard = get_user_meta($user_id, 'user_hoard', true);
  if (!is_array($user_hoard)) $user_hoard = [];
  return $user_hoard;
}

/**
 * Updates user acorns
 *
 * @param integer $user_id
 * @param array $user_hoard
 * @return array All acorn signatures
 * @since 0.9.0
 */
function update_user_hoard($user_id, $user_hoard) {
  update_user_meta($user_id, 'user_hoard', $user_hoard);
}

/**
 * Returns user pick-up fields
 *
 * @param integer $user_id
 * @return array All user pick-ups
 * @since 0.9.0
 */
function get_user_pick_ups($user_id) {
  $user_pick_ups = get_user_meta($user_id, 'user_pick_ups', true);
  if (!is_array($user_pick_ups)) $user_pick_ups = [];
  return $user_pick_ups;
}

/**
 * Updates user pick-up fields
 *
 * @param integer $user_id
 * @param array $user_pick_ups
 * @return array All user pick-ups
 * @since 0.9.0
 */
function update_user_pick_ups($user_id, $user_pick_ups) {
  if (!is_array($user_pick_ups)) $user_pick_ups = [];
  update_user_meta($user_id, 'user_pick_ups', $user_pick_ups);
}

/**
 * Returns user shift count field
 *
 * @param integer $user_id
 * @return integer Quantity of signed shifts
 * @since 0.9.2
 */
function get_user_shift_count($user_id) {
  $user_shift_count = get_user_meta($user_id, 'user_shift_count', true);
  if (!is_numeric($user_shift_count)) $user_shift_count = 0;
  return $user_shift_count;
}

/**
 * Updates user shift count field
 *
 * @param integer $user_id
 * @param integer $user_shift_count Quantity of signed shifts
 * @return void
 * @since 0.9.2
 */
function update_user_shift_count($user_id, $user_shift_count) {
  if (!is_numeric($user_shift_count)) $user_shift_count = 0;
  update_user_meta($user_id, 'user_shift_count', $user_shift_count);
}

/**
 * Returns false, if the shift limit is reached
 *
 * @param integer $user_id
 * @return bool
 * @since 0.9.2
 */
function user_has_shifts_left($user_id) {
  $user_shift_count = get_user_shift_count($user_id);
  if ($user_shift_count >= $GLOBALS['xfee_config']['max_shift_per_user']) {
    return false;
  }
  return true;
}

/**
 * Returns an array with all shifts, can be filtered by $user_id
 *
 * @param integer $user_id optional
 * @return array All user shifts
 * @since 0.9.0
 */
function get_user_shifts($user_id) {
  $user_shifts = [];

  $all_shifts = get_posts(array(
    'post_type'      => 'shift',
    'fields'         => 'ids',
    'posts_per_page' => -1,
    'post_status'    => 'publish',
    'order'          => 'ASC',
  ));

  foreach ($all_shifts as $shift_id) {

    $shift_fields = get_shift_fields($shift_id);
    foreach ($shift_fields['dates'] as $key => $field) { // Check all occurrences
      if ($field['assign_id'] === $user_id) {

        // Add status
        [$assigned, $status] = get_shift_status([
          'date'      => $field['date'],
          'assign_id' => $field['assign_id'] ?? 0,
          'time_from' => $shift_fields['time_from'],
          'time_to'   => $shift_fields['time_to'],
          'done'      => $field['done'] ?? false,
        ]);

        $user_shifts[] = [
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
  }

  // Sort by date
  uasort($user_shifts, function ($a, $b) {
    $result = strcmp($a['date'], $b['date']);
    if ($result === 0) {
      $result = strcmp($a['time_from'], $b['time_from']);
    }
    return $result;
  });

  return $user_shifts;
}

/**
 * Returns true if user has at least 1 shift
 *
 * @param integer $user_id
 * @param array $all_shifts
 * @return bool
 * @since 0.9.0
 */
function user_has_shifts($user_id, $all_shifts) {
  foreach ($all_shifts as $shift) {
    if ($shift['assign_id'] === $user_id) return true;
  }
  return false;
}
