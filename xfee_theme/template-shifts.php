<?php

/**
 * Show all future shifts
 * Init sign-up, done and cancel
 *
 * Template Name: Shifts
 *
 * @package    xfee_theme
 * @author     Richard Lemke
 * @link       https://mrhide.de
 * @license    GPL-3.0 or later
 **/

get_header();

$shifts = get_all_shifts();
$shift_stats = get_shift_stats();
?>

<h1><?php echo get_the_title() . $shift_stats; ?></h1>

<div id="shift_list">
  <?php

  $running_empty = false;
  $start         = new DateTimeImmutable('today');
  $end           = $start->modify('+' . $GLOBALS['xfee_config']['shifts_for_weeks'] . ' weeks');

  for ($date = $start; $date <= $end; $date = $date->modify('+1 day')) {
    // Print year
    if ($date->format('m-d') === '01-01') {
      echo '<h3>' . esc_html($date->format('Y')) . '</h3>';
    }

    $html         = '';
    $shifts_today = find_shift_ids_for_date($date->format('Y-m-d'), $shifts);
    $item_class   = '';
    if (count($shifts_today) > 0) {
      foreach ($shifts_today as $key => $time_from) {
        if ($shifts[$key]['assign_id'] === 0) $item_class = 'has_shift';
      }
    } else {
      if ($running_empty === false) {
        $running_empty = true;
        echo '<div class="empty_days">';
      }
    }
    $template_uri = get_template_directory_uri();

    if (count($shifts_today) > 0 && $running_empty === true) {
      $running_empty = false;
      echo '</div>';
    }  ?>

    <div class="date <?php echo $item_class; ?>">
      <div class="day"><?php echo esc_html($date->format('d')); ?> <span><?php echo esc_html($date->format('D')); ?></span></div>
      <div class="month"><?php echo esc_html($date->format('F')); ?></div>
    </div>

    <?php
    if (count($shifts_today) > 0) {
      foreach ($shifts_today as $key => $time_from) {
    ?>
        <div class='time'><?php echo $shifts[$key]['time_from']; ?><br>|<br><?php echo $shifts[$key]['time_to']; ?></div>

        <h2><?php echo $shifts[$key]['name']; ?><?php echo get_editor_link($shifts[$key]['id']); ?></h2>

        <div class="action_form_wrap" data-assigned="<?php echo $shifts[$key]['assigned']; ?>" data-status="<?php echo $shifts[$key]['status']; ?>">

          <div class="acorn_sign"><?php echo $shifts[$key]['compensation']; ?> <span class="acorn_icon"></span></div>
          <?php
          echo get_shift_action_form(
            [
              'id'           => $shifts[$key]['id'],
              'date'         => $shifts[$key]['date'],
              'signature'    => $shifts[$key]['signature'],
              'compensation' => $shifts[$key]['compensation'],
              'weekday'      => $shifts[$key]['weekday'],
              'done'         => $shifts[$key]['done'],
              'assigned'     => $shifts[$key]['assigned'],
              'status'       => $shifts[$key]['status'],
            ]
          );
          if ($shifts[$key]['assigned'] != 'assigned') {
            $timestamp_begin = $shifts[$key]['date'] . ' ' . $shifts[$key]['time_from'];
            $timestamp_end   = $shifts[$key]['date'] . ' ' . $shifts[$key]['time_to'];
            echo render_countdown($timestamp_begin, $timestamp_end);
          }
          ?>
        </div>

        <button data-detail-id="<?php echo $key; ?>">Details</button>

        <div data-detail-id="<?php echo $key; ?>" class="details">
          <div><?php echo $shifts[$key]['details_html']; ?></div>
        </div>
  <?php
      }
    }
  }
  ?>
</div>

<?php get_footer(); ?>