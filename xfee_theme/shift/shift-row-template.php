<?php

/**
 * Shift row template
 * Available in JS via #occurrence_row_template
 * For admin only
 *
 * @package    xfee_theme
 * @author     Richard Lemke
 * @link       https://mrhide.de
 * @license    GPL-3.0 or later
 */


/**
 * Returns an array with THE shift row template
 *
 * @return array Template parts, [0] => label & hidden, [1] => user select, [2] => remove button
 *
 * @author Richard Lemke <richard@mrhide.de>
 * @since 0.9.0
 */
function get_occurrence_row_template() {
  $template = ['<label data-date="{date}">{date} ({weekday})</label>
            <input type="hidden" data-date="{date}" {blocker}"shift_fields[dates][{date}][date]" value="{date}">
            <input type="hidden" data-date="{date}" {blocker}"shift_fields[dates][{date}][signature]" value="{signature}">
            <input type="hidden" data-date="{date}" {blocker}"shift_fields[dates][{date}][weekday]" value="{weekday}">
            <input type="hidden" data-date="{date}" {blocker}"shift_fields[dates][{date}][done]" value="{done}">'];
  $template[] = get_user_select_list(false);
  $template[] = '<button type="button" data-date="{date}" onclick="remove_shift_occurrence(this)" value="-"></button>';
  return $template;
}

/**
 * Fills out the occurence row template
 *
 * @param array $template
 * @param string $date
 * @param string $weekday
 * @param string $signature
 * @param integer $selected_user_id
 * @param string $status Options: pre_process, queue_process, begin_process, end_process, post_process or done
 *
 * @return string HTML
 *
 * @author Richard Lemke <richard@mrhide.de>
 * @since 0.9.0
 */
function fill_occurrence_row_template($template, $date, $weekday, $signature, $selected_user_id, $done = false) {
  $html = $template[0];
  $html .= get_user_select_list($done, $date, $selected_user_id);
  $html .= $template[2];

  $html = strtr($html, [
    "{date}"      => $date,
    "{signature}" => $signature,
    "{weekday}"   => $weekday,
    "{done}"      => $done ? 1 : 0,
    "{blocker}"   => "name=" // Remove form blocker
  ]);

  return $html;
}

/**
 * Returns a user list select input
 *
 * @param string $status Options: pre_process, queue_process, begin_process, end_process, post_process or done
 * @param integer $shift_date
 * @param integer $selected_user_id
 *
 * @return string HTML
 *
 * @author Richard Lemke <richard@mrhide.de>
 * @since 0.9.0
 */
function get_user_select_list($status, $shift_date = 0, $selected_user_id = 0) {
  $users = get_users([
    'role__in' => array('author', 'editor', 'administrator'),
    'orderby'  => 'display_name',
    'order'    => 'ASC',
    'fields'   => ['ID', 'display_name'],
  ]);

  if ($shift_date === 0) {
    $key_date = '{date}';
    $form_block = '{blocker}';
  } else {
    $key_date = $shift_date;
    $form_block = 'name=';
  }
  $assigned = $selected_user_id != 0 ? 1 : 0;
  ob_start();
?>
  <select data-assigned="<?php echo $assigned; ?>" data-status="<?php echo $status; ?>" <?php echo $form_block; ?>"shift_fields[dates][<?php echo $key_date; ?>][assign_id]" data-date="<?php echo $key_date; ?>">
    <option value="0">unassigned</option>

    <?php foreach ($users as $user) {
      printf(
        '<option value="%d" %s>%s</option>',
        (int) $user->ID,
        selected((int) $selected_user_id, (int) $user->ID, false),
        esc_html($user->display_name)
      );
    } ?>

  </select>
<?php
  return ob_get_clean();
}
