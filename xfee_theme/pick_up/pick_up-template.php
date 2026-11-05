<?php

/**
 * Pick-up list template
 *
 * @package    xfee_theme
 * @author     Richard Lemke
 * @link       https://mrhide.de
 * @license    GPL-3.0 or later
 */


if (isset($_GET['next_action'])) {
  $action = esc_attr($_GET['next_action']);
} else {
  $action = 'get pick-up';
}
$all_pick_ups     = get_all_pick_ups();
$restore_pick_ups = get_pick_up_trash();
$admin_id         = get_current_user_id();
?>

<div class="wrap" id="admin_pick_up_list">
  <?php
  if (array_key_exists($admin_id, $restore_pick_ups)) {
    echo '<div class="undo_btn_wrap">' . get_pick_up_action_form('undo pick-up') . '</div>';
  }
  ?>
  <h1>Manage pick-ups</h1>
  <?php
  if (is_array($restore_pick_ups) && count($restore_pick_ups) > 0) {
    if (is_array($restore_pick_ups[$admin_id]) && array_key_exists('timestamp', $restore_pick_ups[$admin_id])) {
      $time       = $restore_pick_ups[$admin_id]['timestamp'];
      $in_1_hour  = date('Y-m-d H:i', strtotime($time . '+1 hour'));
      $now        = date('Y-m-d H:i');
      if ($now > $in_1_hour) {
        unset($restore_pick_ups[$admin_id]);
        update_option('pick_up_trash', $restore_pick_ups);
      }
    }
  }

  echo get_pick_up_action_form($action);

  ?>
  <div class="empty_row">
    <input type="checkbox" id="show_all_pick_ups" value="Bike" onchange="jQuery('#admin_pick_up_list_table').fadeToggle()">
    <label for="show_all_pick_ups">show all goodie pick-ups</label>
  </div>

  <div id="admin_pick_up_list_table" class="xfee_admin_table" style="display:none">
    <span>User</span>
    <span>Goodie</span>
    <span>Option</span>
    <span>Pick-up</span>
    <?php
    if (count($all_pick_ups) > 0) {
      foreach ($all_pick_ups as $pick_up) {
    ?>
        <div><?php echo get_user_field($pick_up['user_id'], 'user_login'); ?></div>
        <div><?php echo $pick_up['goodie_name']; ?></div>
        <div><?php echo $pick_up['option']; ?></div>
        <div title="<?php echo $pick_up['code']; ?>">---</div>
    <?php
      }
    } else {
      echo '<div class="empty_row">empty</div>';
    }
    ?>
  </div>

  <h1>Stock overview</h1>
  <?php
  echo get_stock_stats();
  ?>
</div>

</div>