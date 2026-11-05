<?php

/**
 * Pick-up and stock related functions
 *
 * @package    xfee_theme
 * @author     Richard Lemke
 * @license    GPL-3.0 or later
 * @link       https://mrhide.de
 */

/**
 * Adds an pick-ups page to backend
 *
 * @uses list.php
 *
 * @return void
 *
 * @author Richard Lemke <richard@mrhide.de>
 * @since 0.9.0
 */

// Add pick-up page
function pic_up_page() {
  add_menu_page('Pick-ups', 'Pick-ups', 'delete_others_pages', 'pick_up_list', 'pick_up_list', 'dashicons-products', 3);
}
add_action('admin_menu', 'pic_up_page');

function pick_up_list() {
  require_once(get_template_directory() . '/pick_up/pick_up-template.php');
}

/**
 * Returns undo pick-ups
 *
 * @return array
 *
 * @author Richard Lemke <richard@mrhide.de>
 * @since 0.9.0
 */
function get_pick_up_trash() {
  $restore_pick_ups = get_option('pick_up_trash', []);
  if (!is_array($restore_pick_ups)) $restore_pick_ups = [];
  return $restore_pick_ups;
}

/**
 * Updates undo pick-ups
 *
 * @param array $restore_pick_ups
 *
 * @return void
 *
 * @author Richard Lemke <richard@mrhide.de>
 * @since 0.9.0
 */
function update_pick_up_trash($restore_pick_ups) {
  if (!is_array($restore_pick_ups)) $restore_pick_ups = [];
  update_option('pick_up_trash', $restore_pick_ups);
}

/**
 * Returns all pick-ups
 *
 * @return array
 *
 * @author Richard Lemke <richard@mrhide.de>
 * @since 0.9.0
 */
function  get_all_pick_ups() {
  $all_goodies = get_all_goodies();
  $all_pick_ups = [];
  foreach ($all_goodies as $goodie) {
    if (is_array($goodie['pick_ups']) && count($goodie['pick_ups']) > 0) {
      foreach ($goodie['pick_ups'] as $pick_up) {
        $all_pick_ups[] = $pick_up;
      }
    }
  }
  return $all_pick_ups;
}

/**
 * Checks pick-up code
 *
 * @param integer $user_id
 * @param integer $code Pick-up code
 *
 * @return boolean
 *
 * @author Richard Lemke <richard@mrhide.de>
 * @since 0.9.0
 */
function check_for_pick_up($user_id, $code) {
  error_log('check_for_pick_up, $code: ' . $code . ' (' . gettype($code) . ')');
  $user_pick_ups = get_user_pick_ups($user_id);
  foreach ($user_pick_ups as $pick_up) {
    error_log('check_for_pick_up, $pick_up[code]: ' . $pick_up['code'] . ' (' . gettype($pick_up['code']) . ')');
    if (array_key_exists('code', $pick_up) && (int)$pick_up['code'] === (int)$code) {
      return true;
    }
  }
  return false;
}

/**
 * Confirms a pick-up and moves the pick-up to the trash
 *
 * @param integer $user_id
 * @param integer $code Pick-up code
 *
 * @author Richard Lemke <richard@mrhide.de>
 * @since 0.9.0
 */
function confirm_pick_up($user_id, $code) {
  $goodie_id        = 0;
  $restore_pick_ups = get_option('pick_up_trash', []);
  $current_user_id  = get_current_user_id();

  // Remove pick-up from user
  $user_pick_ups = get_user_pick_ups($user_id);
  $found_pu = false;
  foreach ($user_pick_ups as $key => $pick_up) {
    if ((int)$pick_up['code'] === (int)$code) {
      $restore_pick_ups[$current_user_id]['user'] = $pick_up;
      $goodie_id = $pick_up['goodie_id'];
      unset($user_pick_ups[$key]);
      update_user_pick_ups($user_id, $user_pick_ups);
      $found_pu = true;
      break;
    }
  }
  if (!$found_pu) {
    error_log('xFee Theme Error: Code did not match with user.');
  }

  // Remove pick-up from goodie
  $goodie_fields = get_goodie_fields($goodie_id);
  error_log('BEFORE $goodie_fields ' . print_r($goodie_fields, true));
  $found_gd = false;
  foreach ($goodie_fields['pick_ups'] as $key => $pick_up) {
    if ((int)$pick_up['code'] === (int)$code) {
      $restore_pick_ups[$current_user_id]['goodie'] = $pick_up;
      unset($goodie_fields['pick_ups'][$key]);
      update_goodie_fields($goodie_id, $goodie_fields);
      $found_gd = true;
      break;
    }
  }
  error_log('AFTER $goodie_fields ' . print_r($goodie_fields, true));
  if ($found_gd === false) {
    error_log('xFee Theme Error: Code did not match with goodie.');
  }

  // Put in trash
  $restore_pick_ups[$current_user_id]['timestamp']  = date('Y-m-i H:i');
  $restore_pick_ups[$current_user_id]['goodie_id']  = $goodie_id;
  $restore_pick_ups[$current_user_id]['user_id']    = $user_id;
  update_option('pick_up_trash', $restore_pick_ups);
}

/**
 * Cancels the current pick-up
 *
 * @author Richard Lemke <richard@mrhide.de>
 * @since 0.9.0
 */
function cancel_current_pick_up() {
  // Remove from last pick-ups
  $current_user_id  = get_current_user_id();
  $restore_pick_ups = get_pick_up_trash();
  if (!array_key_exists($current_user_id, $restore_pick_ups)) return;
  $restore_pick_ups[$current_user_id] = [];
  update_pick_up_trash($restore_pick_ups);
}

/**
 * Restores the last get pick-up
 *
 * @author Richard Lemke <richard@mrhide.de>
 * @since 0.9.0
 */
function restore_last_pick_up() {
  // Get last pick-up
  $current_user_id  = get_current_user_id();
  $restore_pick_ups = get_pick_up_trash();
  if (!array_key_exists($current_user_id, $restore_pick_ups)) {
    wp_die('No matching pick-up to restore.');
  }

  // Restore to user
  $user_id          = $restore_pick_ups[$current_user_id]['user_id'];
  $user_pick_ups    = get_user_pick_ups($restore_pick_ups[$current_user_id]['user_id']);
  $user_pick_ups[]  = $restore_pick_ups[$current_user_id]['user'];
  update_user_pick_ups($user_id, $user_pick_ups);

  // Restore to goodie
  $goodie_id      = $restore_pick_ups[$current_user_id]['goodie_id'];
  $goodie_fields  = get_goodie_fields($goodie_id);
  $goodie_fields['pick_ups'][]  = $restore_pick_ups[$current_user_id]['goodie'];
  update_goodie_fields($goodie_id, $goodie_fields);

  // Remove pick-up
  unset($restore_pick_ups[$current_user_id]);
  update_pick_up_trash($restore_pick_ups);
}
