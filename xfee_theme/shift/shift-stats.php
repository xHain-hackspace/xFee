<?php

/**
 * Stat functions for shifts
 *
 * @package    xfee_theme
 * @author     Richard Lemke
 * @link       https://mrhide.de
 * @license    GPL-3.0 or later
 */

/**
 * Writes stats
 *
 * @return void
 *
 * @author Richard Lemke <richard@mrhide.de>
 * @since 0.9.0
 */
add_action('save_post_shift', 'update_shift_stats', 20, 3);
function update_shift_stats() {
  $current_shifts = [
    'open'     => 0,
    'assigned' => 0,
  ];

  $shift_ids = get_posts([
    'post_type'      => 'shift',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'fields'         => 'ids',
  ]);

  $todaynow = date("Y-m-d H:i");

  foreach ($shift_ids as $shift_id) {
    $fields    = get_post_meta($shift_id, 'shift_fields', true);
    $dates     = $fields['dates'] ?? [];
    $time_from = $fields['time_from'] ?? '';
    $time_to   = $fields['time_to'] ?? '';

    if (!is_array($dates)) {
      continue;
    }

    foreach ($dates as $date) {
      // Ignore finished shifts
      if ($date['done'] === true) {
        continue;
      }

      $timestamp = $date['date'] . ' ' . $time_to;

      $end_of_calendar = date('Y-m-d H:i', strtotime($todaynow . ' + ' . $GLOBALS['xfee_config']['shifts_for_weeks'] . ' weeks'));
      if ($timestamp >= $todaynow && $timestamp <= $end_of_calendar) {
        if ($date['assign_id'] != 0) {
          $current_shifts['assigned']++;
        } else {
          $current_shifts['open']++;
        }
      }
    }
  }

  update_option('shift_stats', $current_shifts, false);
}

/**
 * Returns the shift stats
 *
 * @return string HTML
 *
 * @author Richard Lemke <richard@mrhide.de>
 * @since 0.9.0
 */
function get_shift_stats() {
  $stats       = get_option('shift_stats', []);
  if (count($stats) === 0) return '';
  $shift_stats = '<div id="site_stats">';
  if ($stats['open'] > 0) {
    $shift_stats  .= '<span class="open" value="' . $stats['open'] . '"></span>';
    if ($stats['assigned'] > 0) $shift_stats .= ' | ';
  }
  if ($stats['assigned'] > 0) {
    $shift_stats  .= '<span class="assigned" value="' . $stats['assigned'] . '"></span>';
  }
  $shift_stats  .= '</div>';
  return $shift_stats;
}
