<?php

/**
 * Goodie funtions
 *
 * @package    xfee_theme
 * @author     Richard Lemke
 * @link       https://mrhide.de
 * @license    GPL-3.0 or later
 */

/**
 * Get a list with all goodies
 *
 * @return array
 *
 * @author Richard Lemke <richard@mrhide.de>
 * @since 0.9.0
 */
function get_all_goodies() {
  $all_goodies = [];
  $goodies = get_posts(array(
    'post_type'       => 'goodie',
    'fields'          => 'ids',
    'posts_per_page'  => -1,
    'post_status'     => 'publish',
    'order'           => 'ASC',
  ));

  foreach ($goodies as $id) {
    $fields = get_goodie_fields($id);
    $all_goodies[] = [
      'id'            => $id,
      'name'          => $fields['name'] ?? '',
      'details'       => $fields['details_html'] ?? '',
      'image'         => $fields['image_html'] ?? '',
      'options'       => $fields['options'] ?? [],
      'compensation'  => $fields['compensation'] ?? '0',
      'in_stock'      => get_stock_count($fields['options']),
      'pick_ups'      => $fields['pick_ups'] ?? [],
    ];
  }

  return $all_goodies;
}

/**
 * Returns goodie fields
 *
 * @param integer $goodie_id
 *
 * @return array All goodies
 *
 * @author Richard Lemke <richard@mrhide.de>
 * @since 0.9.0
 */
function get_goodie_fields($goodie_id) {
  $goodie_fields = get_post_meta($goodie_id, 'goodie_fields', true);
  if (!is_array($goodie_fields)) $goodie_fields = [];
  return $goodie_fields;
}

/**
 * Updates goodie fields
 *
 * @param integer $goodie_id
 * @param array $goodie_fields
 *
 * @return array All goodies
 *
 * @author Richard Lemke <richard@mrhide.de>
 * @since 0.9.0
 */
function update_goodie_fields($goodie_id, $goodie_fields) {
  if (!is_array($goodie_fields)) $goodie_fields = [];
  $fields['in_stock'] = get_stock_count($goodie_fields['options']);
  update_post_meta($goodie_id, 'goodie_fields', $goodie_fields);
}

/**
 * Sums the stock count
 *
 * @param array $options
 *
 * @return integer Stock count
 *
 * @author Richard Lemke <richard@mrhide.de>
 * @since 0.9.0
 */
function get_stock_count($options) {
  $count = 0;
  foreach ($options as $value) {
    $count += $value;
  }
  return $count;
}

/**
 * Returns the goodie stats
 *
 * @param array $all_goodies
 *
 * @return string HTML
 *
 * @author Richard Lemke <richard@mrhide.de>
 * @since 0.9.0
 */
function get_goodie_stats($all_goodies) {
  $stats = [
    'goodie_count' => 0,
    'out_of_stock'     => 0,
  ];

  foreach ($all_goodies as $key => $goodie) {
    foreach ($goodie['options'] as $name => $count) {
      if ($count === 0) {
        $stats['out_of_stock']++;
      } else {
        $stats['goodie_count'] += $count;
      }
    }
  }

  $goodie_stats = '<div id="site_stats">';
  if ($stats['goodie_count'] > 0) {
    $goodie_stats  .= '<span class="goodies" value="' . $stats['goodie_count'] . '">' . ($stats['goodie_count'] > 0 ? 's' : '') . '</span>';
    if ($stats['out_of_stock'] > 0) $goodie_stats .= ' | ';
  }
  if ($stats['out_of_stock'] > 0) {
    $goodie_stats  .= '<span class="out_of_stock" value="' . $stats['out_of_stock'] . '"></span>';
  }
  $goodie_stats  .= '</div>';
  return $goodie_stats;
}

/**
 * Returns a list of all stock figures
 *
 * @return string HTML
 *
 * @author Richard Lemke <richard@mrhide.de>
 * @since 0.9.0
 */
function get_stock_stats() {
  $all_goodies = get_all_goodies();
  ob_start();
?>
  <div id="stock_stats" class="xfee_admin_table">
    <?php
    foreach ($all_goodies as $key => $goodie) {
      foreach ($goodie['options'] as $name => $count) {
    ?>
        <div><?php echo $goodie['name']; ?></div>
        <div><?php echo $name; ?></div>
        <div class="stock_count" value="<?php echo $count; ?>"><?php echo $count; ?></div>
    <?php
      }
    }
    ?>
  </div>
<?php
  return ob_get_clean();
}

/**
 * Handles user picking a goodie
 *
 * @param array $args
 *
 * @return string Feedback, warnings
 *
 * @author Richard Lemke <richard@mrhide.de>
 * @since 0.9.0
 */
function handle_user_pick(array $args) {
  $defaults = [
    'user_id'       => 0,
    'compensation'  => 0,
    'goodie_id'     => 0,
    'goodie_name'   => '',
    'goodie_option' => '',
  ];
  $args = wp_parse_args($args, $defaults);

  $user_hoard = get_user_hoard($args['user_id']);
  $goodie_fields = get_goodie_fields($args['goodie_id']);
  $user_pick_ups = get_user_pick_ups($args['user_id']);
  $feedback = '';
  $success = 0;

  if ($args['goodie_option'] === '0') {
    return [0, 'Pick an option'];
  }

  $stock_count = $goodie_fields['in_stock'];

  // Checks first
  if (count($user_hoard) === 0) {
    error_log('Transaction error: No Funds! (user_id: ' . $args['user_id'] . ')');
    return [0, 'No funds, please take over shifts to earn some acôrn'];
  }
  if ($stock_count <= 0) {
    error_log('Transaction error: Stock empty! (goodie_id: ' . $args['goodie_id'] . ')');
    return [0, 'Not on stock, please choose other item or option'];
  }

  // Create pick-up code
  $pick_up_code = $args['user_id'] . rand(100, 999);

  // Take acorns from user
  $args['compensation'] = absint($args['compensation']);
  $transfer_acorns = array_splice($user_hoard, 0, $args['compensation']);
  update_user_hoard($args['user_id'], $user_hoard);

  // Add pick-up to goodie
  $goodie_fields['pick_ups'][] = [
    'user_id'     => $args['user_id'],
    'goodie_name' => $args['goodie_name'],
    'option'      => $args['goodie_option'],
    'reserve'     => count($transfer_acorns),
    'code'        => $pick_up_code,
  ];
  // Remove from stock
  $goodie_fields['options'][$args['goodie_option']] = (int)$goodie_fields['options'][$args['goodie_option']] - 1;
  update_goodie_fields($args['goodie_id'], $goodie_fields);

  // Add pick-up to user
  $user_pick_ups[] = [
    'goodie_id'   => $args['goodie_id'],
    'goodie_name' => $args['goodie_name'],
    'option'      => $args['goodie_option'],
    'acorns'      => $transfer_acorns,
    'code'        => $pick_up_code,
  ];
  update_user_pick_ups($args['user_id'], $user_pick_ups);

  $success = 1;
  $feedback = $args['goodie_name'] . ' (' . $args['goodie_option'] . ') ready for pick-up, code: ' . $pick_up_code;

  return [$success, $feedback];
}

/**
 * Handles user canceling a pick
 *
 * @param integer $code
 *
 * @author Richard Lemke <richard@mrhide.de>
 * @since 0.9.0
 */
function handle_user_cancel($code) {
  $user_id = substr($code, 0, -3);
  $goodie_id = 0;
  $transfer_acorns = [];

  // Remove pick-up from user
  $user_pick_ups = get_user_pick_ups($user_id);
  foreach ($user_pick_ups as $key => $pick_up) {
    if ((int)$pick_up['code'] === (int)$code) {
      $goodie_id = $pick_up['goodie_id'];
      $transfer_acorns = $pick_up['acorns'];
      unset($user_pick_ups[$key]);
      break;
    }
  }
  update_user_pick_ups($user_id, $user_pick_ups);

  // Remove pick-up from goodie
  $goodie_option = '';
  $goodie_fields = get_goodie_fields($goodie_id);
  foreach ($goodie_fields['pick_ups'] as $key => $pick_up) {
    if ((int)$pick_up['code'] === (int)$code) {
      $goodie_option = $pick_up['option'];
      unset($goodie_fields['pick_ups'][$key]);
      break;
    }
  }
  $goodie_fields['options'][$goodie_option] = (int)$goodie_fields['options'][$goodie_option] + 1;
  update_goodie_fields($goodie_id, $goodie_fields);

  // Put acorns back
  $user_hoard = get_user_hoard($user_id);
  $user_hoard = array_merge($user_hoard, $transfer_acorns);
  update_user_hoard($user_id, $user_hoard);
}

/**
 * Parses markdown to HTML, levels down headlines
 *
 * @param string $markdown
 *
 * @return string HTML
 *
 * @author Richard Lemke <richard@mrhide.de>
 * @since 0.9.0
 */
function get_html_from_markdown($markdown) {
  $markdown = preg_replace_callback('/^(#{1,5})(?=\s)/m', function ($matches) {
    $currentLvl = strlen($matches[1]);
    $newLvl = min(6, $currentLvl + $GLOBALS['xfee_config']['level_down_md']);
    return str_repeat('#', $newLvl);
  }, $markdown);
  $Parsedown = new Parsedown();
  return $Parsedown->text($markdown);
}
