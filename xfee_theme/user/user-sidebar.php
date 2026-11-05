<?php

/**
 * User sidebars
 *
 * @package    xfee_theme
 * @author     Richard Lemke
 * @link       https://mrhide.de
 * @license    GPL-3.0 or later
 */

/**
 * Prints the sidebar on goodie template
 *
 * @return void
 *
 * @author Richard Lemke <richard@mrhide.de>
 * @since 0.9.0
 */
function print_user_sidebar() {
  $user_id = get_current_user_id();
  if (basename(get_page_template()) === 'template-goodies.php') {
?>
    <div class="user_stats">
      <h2>Your hoard</h2>
      <div class="acorn_sign"><?php echo get_user_acorn_count($user_id); ?> <span class="acorn_icon"></span></div>
      <?php
      $user_pick_ups = get_user_pick_ups($user_id);
      if (count($user_pick_ups) > 0) {
      ?>
        <h2>Your pick-ups</h2>
      <?php
        foreach ($user_pick_ups as $pick_up) {
          echo '<div>' . $pick_up['goodie_name'] . ' (' . $pick_up['option'] . ')<br>Pick-up code: ' . $pick_up['code'] . '</div>';
        }
      }
      ?>
    </div>
  <?php
  } else if (basename(get_page_template()) === 'template-shifts.php') {
  ?>
    <div class="user_stats">
      <h2>Your shifts</h2>
    </div>
<?php

  }
}
