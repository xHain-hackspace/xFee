<?php

/**
 * Registers, updates and prints shift meta box
 *
 *
 * @package    xfee_theme
 * @author     Richard Lemke
 * @link       https://mrhide.de
 * @license    GPL-3.0 or later
 */

/**
 * Registers shift meta box
 *
 * @param WP_Post $post
 * @return void
 * @since 0.9.0
 */
add_action('admin_menu', 'shift_add_metabox');
function shift_add_metabox() {
  add_meta_box(
    'shift_fields',
    'Shifts',
    'shift_meta_box_form',
    'shift',
    'normal',
    'high'
  );
}

/**
 * Prints shift meta box
 *
 * @param WP_Post $post
 * @return void
 * @since 0.9.0
 */
function shift_meta_box_form($post) {
  $shift_fields = get_shift_meta($post->ID);
  $row_template = get_occurrence_row_template();

  wp_nonce_field('save_shift_fields', 'shift_fields_nonce');
?>

  <div class="cmb_form" id="shift_option_form">
    <h3>Details</h3>
    <div style="display: none;" id="occurrence_row_template"><?php echo join("", $row_template); ?></div>
    <label for="shift_name">Name</label>
    <input class="wide_column" type="text" id="shift_name" name="shift_fields[name]"
      value="<?php echo esc_attr($shift_fields['name']); ?>">

    <input type="hidden" name="shift_fields[id]" value="<?php echo esc_attr($post->ID); ?>">

    <label for="shift_details">Details</label>
    <textarea class="wide_column" rows="15" id="shift_details" name="shift_fields[details_markdown]"><?php echo esc_attr($shift_fields['details_markdown']); ?></textarea>

    <label for="shift_compensation">Compensation</label>
    <input class="wide_column" type="number" id="shift_compensation" min="0" max="<?php echo $GLOBALS['xfee_config']['max_acorn_per_shift']; ?>" name="shift_fields[compensation]"
      value="<?php echo esc_attr($shift_fields['compensation']); ?>">

    <label for="shift_time_from">Time from</label>
    <input class="wide_column" type="time" id="shift_time_from" name="shift_fields[time_from]"
      value="<?php echo esc_attr($shift_fields['time_from']); ?>">

    <label for="shift_time_to">Time to</label>
    <input class="wide_column" type="time" id="shift_time_to" name="shift_fields[time_to]"
      value="<?php echo esc_attr($shift_fields['time_to']); ?>">

    <h3>Occurrences</h3>
    <p>Occurrences older than a month are deleted automatically.</p>
    <?php
    $current_key = 0;
    // Remove shifts older than 1 month
    $shift_fields['dates'] = shift_remove_old_ones($shift_fields['dates']);

    if (count($shift_fields['dates']) > 0) {
      foreach ($shift_fields['dates'] as $shift) {
        $selected_user_id = $shift['assign_id'] != 0 ? absint($shift['assign_id']) : 0;
        $date             = $shift['date'];
        $signature        = $shift['signature'] ?? get_new_signature($post->ID, $shift['date']);
        $done             = $shift['done'];
        $weekday          = $shift['weekday'];
        if ($weekday === '') {
          $weekday = wp_date('l', strtotime($shift['date']));
        }
        echo fill_occurrence_row_template($row_template, $date, $weekday, $signature, $selected_user_id, $done);

        $current_key = $date;
      }
    } else {
      echo '<p>No occurrences</p>';
    }
    ?>
    <div id="shift_rhythms">
      <button type="button" id="add_new_shift_day">+ 1 day</button>
      <button type="button" id="add_new_shift_week">+ 1 week</button>
      <button type="button" id="add_new_shift_month">+ 1 month</button>
    </div>
    <p>Add new:</p>
    <input type="date" id="add_new_shift_date" class="wide_column">
    <button type="button" id="add_new_shift" data-date="<?php echo $current_key; ?>">+</button>
  </div>
<?php
}

/**
 * Updates shift meta box
 *
 * @param integer $shift_id  Wordpress object
 * @param WP_Post $post
 * @return void
 * @since 0.9.0
 */
add_action('save_post', 'shift_save_meta', 10, 2);
function shift_save_meta($shift_id, $post) {
  if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
  if (! isset($_POST['shift_fields_nonce']) || ! wp_verify_nonce($_POST['shift_fields_nonce'], 'save_shift_fields')) return;
  if (!is_user_logged_in()) return;
  if ('shift' !== $post->post_type || !isset($_POST['shift_fields'])) return;

  $raw_fields = wp_unslash($_POST['shift_fields']);

  $fields = [];
  $fields['name']             = sanitize_text_field($raw_fields['name'] ?? '');
  $fields['id']               = absint($raw_fields['id'] ?? 0);
  $fields['details_markdown'] = sanitize_textarea_field($raw_fields['details_markdown'] ?? '');
  $fields['details_html']     = get_html_from_markdown($fields['details_markdown']);
  $fields['compensation']     = (int) $raw_fields['compensation'] ?? 0;
  $fields['time_from']        = sanitize_text_field($raw_fields['time_from'] ?? '');
  $fields['time_to']          = sanitize_text_field($raw_fields['time_to'] ?? '');
  $fields['dates']            = [];

  if ($fields['compensation'] > $GLOBALS['xfee_config']['max_acorn_per_shift']) $fields['compensation'] = $GLOBALS['xfee_config']['max_acorn_per_shift'];

  if (isset($raw_fields['dates']) && is_array($raw_fields['dates'])) {

    foreach ($raw_fields['dates'] as $key => $shift) {
      if (!is_array($shift)) {
        continue;
      }

      if (array_key_exists('weekday', $shift)) {
        $weekday = $shift['weekday'];
      } else {
        $weekday = '';
      }

      if ($weekday === '') {
        $weekday = wp_date(
          'l',
          strtotime($shift['date'])
        );
      }

      $fields['dates'][$key] = [
        'date'      => sanitize_text_field($shift['date'] ?? ''),
        'signature' => sanitize_text_field($shift['signature'] ?? ''),
        'assign_id' => absint($shift['assign_id'] ?? 0),
        'weekday'   => sanitize_text_field($weekday ?? ''),
        'done'      => $shift['done'],
      ];
    }
  }

  // Remove shifts older than 1 month
  $fields['dates'] = shift_remove_old_ones($fields['dates']);

  // Sort by date
  uasort($fields['dates'], function ($a, $b) {
    return strcmp($a['date'], $b['date']);
  });

  update_shift_fields($shift_id, $fields);
}

/**
 * Returns clean shift meta box fields
 * Only used in shift_meta_box_form()
 *
 * @param integer $shift_id
 * @return array Shift fields
 * @since 0.9.0
 */
function get_shift_meta($shift_id) {
  $fields = get_post_meta($shift_id, 'shift_fields', true);
  if (!is_array($fields)) $fields = [];
  $fields['name']             = $fields['name'] ?? '';
  $fields['id']               = $fields['id'] ?? 0;
  $fields['details_markdown'] = $fields['details_markdown'] ?? '';
  $fields['details_html']     = $fields['details_html'] ?? '';
  $fields['compensation']     = $fields['compensation'] ?? 0;
  $fields['time_from']        = $fields['time_from'] ?? '';
  $fields['time_to']          = $fields['time_to'] ?? '';
  $fields['dates']            = $fields['dates'] ?? [];
  return $fields;
}
