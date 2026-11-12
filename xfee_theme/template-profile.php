<?php

/**
 * Show all personal user data
 *
 * Template Name: Profile
 *
 * @package    xfee_theme
 * @author     Richard Lemke
 * @link       https://mrhide.de
 * @license    GPL-3.0 or later
 **/

get_header(); ?>

<h1><?php echo get_the_title(); ?></h1>

<div id="goodie_list">
  <?php
  if (!is_user_logged_in()) {
    return '<p>Please log in.</p>';
  }
  $user = wp_get_current_user();
  if (!in_array('author', (array) $user->roles, true) && !in_array('administrator', (array) $user->roles, true)) {
    return '<p>You have no access.</p>';
  }

  $user_shifts = get_user_shifts($user->ID);
  ?>
  <div class="user_data">
    <?php
    $count = get_user_acorn_count($user->ID);
    ?>
    <h2>Your hoard</h2>
    <div id="user_hoard">
      <span><?php echo $count; ?></span><span class="acorn_icon"></span>
    </div>
    <h2>Your current shifts</h2>
    <div class="user_shift_table">
      <?php
      $counter = 0;
      if (count($user_shifts) > 0) {
      ?>
        <?php
        foreach ($user_shifts as $key => $shift) {
          if ($shift['done'] === true) {
            continue;
          }
          $counter++;
        ?>
          <div><?php echo $shift['weekday']; ?> <?php echo $shift['date']; ?></div>
          <div><?php echo $shift['time_from'] . ' - ' . $shift['time_to']; ?></div>
          <div><?php echo $shift['name']; ?></div>
          <div class="action_form_wrap" data-assigned="<?php echo $shift['assigned']; ?>">
            <?php echo get_shift_action_form(
              [
                'id'            => $shift['id'],
                'date'          => $shift['date'],
                'signature'     => $shift['signature'],
                'compensation'  => $shift['compensation'],
                'weekday'       => $shift['weekday'],
                'done'          => $shift['done'],
                'assigned'      => $shift['assigned'],
                'status'        => $shift['status'],
              ]
            ); ?>
          </div>
        <?php
        }
      }
      if ($counter === 0) {
        ?>
        <span>You have no shifts</span>
      <?php
      }
      ?>
    </div>

    <?php
    $pick_ups = get_user_pick_ups($user->ID);
    ?>
    <h2>Your pick-ups</h2>
    <div id="user_pick_ups" class="user_shift_table">
      <?php
      if (count($pick_ups) > 0) {
        foreach ($pick_ups as $key => $pick_up) {
      ?>
          <div><?php echo $pick_up['goodie_name']; ?></div>
          <div><?php echo $pick_up['option']; ?></div>
          <div>Pick-up code: <?php echo $pick_up['code']; ?></div>
          <div><?php echo get_cancel_order_form($pick_up['code']); ?></div>
        <?php
        }
      } else {
        ?>
        <span>You have no pick-ups</span>
      <?php
      }
      ?>
    </div>

  </div>
  <?php


  ?>
</div>

<?php get_footer(); ?>