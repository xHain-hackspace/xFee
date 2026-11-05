<?php

/**
 * Show all goodies
 * Init pick, pick-up and cancel
 *
 * Template Name: Goodies
 *
 * @package    xfee_theme
 * @author     Richard Lemke
 * @link       https://mrhide.de
 * @license    GPL-3.0 or later
 **/

$feedback = '';
$success  = '';
if (isset($_GET['feedback']) && isset($_GET['success'])) {
  $feedback = $_GET['feedback'];
  $success  = $_GET['success'];
}

get_header();
$goodies = get_all_goodies();
$goodie_stats = get_goodie_stats($goodies);
$user_id = get_current_user_id();
?>

<h1><?php echo get_the_title() . $goodie_stats; ?></h1>

<div id="goodie_list">
  <?php
  if ($feedback != '' && $success != '') {
    echo '<div id="form_warning" data-success="' . $success . '">' . $feedback . '</div>';
  }


  foreach ($goodies as $key => $goodie) { ?>

    <h2><?php echo $goodie['name']; ?><?php echo get_editor_link($goodie['id']); ?></h2>

    <div class="img_wrap"><?php echo $goodie['image']; ?></div>

    <?php echo get_order_form($goodie['id'], $goodie['name'], $goodie['options'], $user_id, $goodie['compensation'], $goodie['in_stock']); ?>

    <button data-detail-id="<?php echo $key; ?>">Details</button>

    <div data-detail-id="<?php echo $key; ?>" class="details">
      <div><?php echo $goodie['details']; ?></div>
    </div>

  <?php
  }
  ?>
</div>

<?php get_footer(); ?>