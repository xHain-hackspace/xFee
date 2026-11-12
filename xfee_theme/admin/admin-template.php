<?php

/**
 * Admin list template
 *
 * @package    xfee_theme
 * @author     Richard Lemke
 * @link       https://mrhide.de
 * @license    GPL-3.0 or later
 *
 * @var array $all_users List of all users.
 */

// CAREFUL !!!

// DELETE ALL SHIFT OCCURRENCES
// $shift_id = 43;
// $shift_fields = get_post_meta($shift_id, 'shift_fields', true);
// $shift_fields['dates'] = [];
// update_post_meta($shift_id, 'shift_fields', $shift_fields);

// DELETE USER PICK-UPS
// update_user_meta(2, 'user_pick_ups', []);

// DELETE CURRENT PICK_UPS
// update_option('pick_up_trash', []);

// DELETE GOODIE PICK-UPS
// $goodie_id = 61;
// $goodie_fields = get_goodie_fields($goodie_id);
// $goodie_fields['pick_ups'] = [];
// update_post_meta($goodie_id, 'goodie_fields', $goodie_fields);
?>
<div class="wrap" id="admins_download_wrap">

  <h1>Admin list</h1>

  <?php
  $all_user_details = ['<div class="admin_all_user_details">'];
  $target_user = 0;
  if (isset($_GET['target_user']) && (int)$_GET['target_user'] > 0) {
    $target_user = (int)$_GET['target_user'];
  }
  ?>

  <div id="admin_user_list_table" class="xfee_admin_table">
    <span>ID</span>
    <span>Name</span>
    <span>Shifts</span>
    <span>Hoard</span>
    <span>Pick-ups</span>
    <span>Details</span>

    <?php
    // Build details up-front
    foreach ($all_users as $user) {
      $user_html_id = $user['name'] . '-' . $user['id'];

      // Get user shifts
      $user_shifts = get_user_shifts($user['id']);
      $user_shifts_html = ['<code>'];
      if (count($user_shifts) > 0) {
        foreach ($user_shifts as $key => $shift) {
          $shift_name = $shift['name'] . ' (' . $shift['time_from'] . ')';
          $user_shifts_html[] = '<div>' . $shift['date'] . ': ' . $shift_name . '</div>';
        }
      } else {
        $user_shifts_html[] = '<div>empty</div>';
      }
      $user_shifts_html[] = '</code>';

      // Get user acorns
      $user_acorns_html = [];
      if (count($user['hoard']) > 0) {
        $user_acorns_html = ['<code>'];
        foreach ($user['hoard'] as $key => $signature) {
          $user_acorns_html[] = '<div>' . $this->get_white_space_decimal($key) . $key . ': ' . $signature . '</div>';
        }
      } else {
        $user_acorns_html = ['<code style="padding:0.4rem 0.6rem">']; // Handle CSS indent TODO: Add as CSS
        $user_acorns_html[] = '<div style="margin:0">empty</div>';
      }
      $user_acorns_html[] = '</code>';

      // Get user pick_ups
      $user_pick_ups  = get_user_pick_ups($user['id']);
      $user_pick_ups_html = ['<code>'];
      if (is_array($user_pick_ups) && count($user_pick_ups) > 0) {
        foreach ($user_pick_ups as $key => $pick_up) {
          $pick_up_name = $pick_up['goodie_name'] . ' (' . $pick_up['option'] . ')';
          $user_pick_ups_html[] = '<div>' . $key . ': ' . $pick_up_name . '</div>';
        }
      } else {
        $user_pick_ups_html[] = '<div>empty</div>';
      }
      $user_pick_ups_html[] = '</code>';
    ?>

      <div class="admin_user_id"><?php echo $user['id']; ?></div>
      <div><?php echo $user['name']; ?></div>
      <div><?php echo $user['signed_count']; ?></div>
      <div><?php echo $user['acorn']; ?></div>
      <div><?php echo $user['pick_up_count']; ?></div>
      <div>
        <?php
        $value = 'show';
        $visible = '0';
        if ($target_user === $user['id']) {
          $value = 'close';
          $visible = '1';
        }
        ?>
        <button data-user-id="<?php echo $user['id']; ?>" onclick="toggle_admin_user_details(this, '<?php echo $user_html_id; ?>')" value="<?php echo $value; ?>"></button>
      </div>
      <section>
        <div class="admin_user_details" id="<?php echo $user_html_id; ?>" data-visible="<?php echo $visible; ?>">
          <h2>Details</h2>
          <div>
            <h3>User</h3>
            <code>
              <div>name <?php $this->get_white_space(4); ?>=> <?php echo $user['name']; ?></div>
              <div>id <?php $this->get_white_space(6); ?>=> <?php echo $user['id']; ?></div>
              <div>email <?php $this->get_white_space(3); ?>=> <?php echo $user['user_email']; ?></div>
              <div>shifts <?php $this->get_white_space(2); ?>=> <?php echo $user['signed_count']; ?></div>
              <div>hoard <?php $this->get_white_space(3); ?>=> <?php echo $user['acorn']; ?></div>
              <div>pick_ups <?php $this->get_white_space(0); ?>=> <?php echo $user['pick_up_count']; ?></div>

            </code>
          </div>
          <div>
            <h3>Shifts</h3>
            <?php echo join('', $user_shifts_html); ?>
          </div>
          <div id="admin_user_acorn_list">
            <h3>Acôrns</h3>
            <?php echo join('', $user_acorns_html); ?>
            <?php echo $this->get_admin_action_form('add acorn', $user['id']); ?>
            <?php echo $this->get_admin_action_form('remove acorn', $user['id']); ?>
          </div>
          <div>
            <h3>User pick-ups</h3>
            <?php echo join('', $user_pick_ups_html); ?>
            <?php echo $this->get_admin_action_form('remove pick-up', $user['id']); ?>
          </div>
        </div>
      </section>

    <?php
    }
    ?>
  </div>

  <?php
  $all_user_details[] = '</div>';
  echo join('', $all_user_details);
  ?>

</div>

<div>
  <br><br>
  <h3>var_dumps</h3>

  <details>
    <summary>var_dump(get_all_shifts())</summary>
    <?php
    $all_shifts = $this->truncate_array_strings(get_all_shifts());
    echo '<pre style="font-size:.6rem;letter-spacing:-0.4px;">(string shortened, html removed)<br><br>';
    var_dump($all_shifts);
    echo '</pre>';
    ?>
  </details>

  <details>
    <summary>var_dump(get_post_meta(shift_fields))</summary>
    <?php
    $all_shifts = [];

    $shifts = get_posts(array(
      'post_type'       => 'shift',
      'fields'          => 'ids',
      'posts_per_page'  => -1,
      'post_status'     => 'publish',
      'order'           => 'ASC',
    ));

    foreach ($shifts as $shift_id) {
      $shift_fields = get_shift_fields($shift_id);
      $all_shifts[] = $shift_fields;
    }

    $all_shifts = $this->truncate_array_strings($all_shifts);
    echo '<pre style="font-size:.6rem;letter-spacing:-0.4px;">(string shortened, html removed)<br><br>';
    var_dump($all_shifts);
    echo '</pre>';
    ?>
  </details>

  <details>
    <summary>var_dump($all_users)</summary>
    <?php
    echo '<pre style="font-size:.6rem;letter-spacing:-0.4px;">(string shortened, html removed)<br><br>';
    var_dump($all_users);
    echo '</pre>';
    ?>
  </details>

  <details>
    <summary>var_dump(get_all_goodies())</summary>
    <?php
    echo '<pre style="font-size:.6rem;letter-spacing:-0.4px;">(string shortened, html removed)<br><br>';
    var_dump($this->truncate_array_strings(get_all_goodies()));
    echo '</pre>';
    ?>
  </details>
</div>

</div>