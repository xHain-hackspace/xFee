<?php

/**
 * Register and prints goodie meta box
 *
 * @package    xfee_theme
 * @author     Richard Lemke
 * @link       https://mrhide.de
 * @license    GPL-3.0 or later
 */

/**
 * Registers goodie meta box
 *
 * @param WP_Post $post
 * @return void
 * @since 0.9.0
 */
add_action('admin_menu', 'goodie_add_metabox');
function goodie_add_metabox() {
  add_meta_box(
    'goodie_fields',
    'Goodies',
    'goodie_meta_box_form',
    'goodie',
    'normal',
    'high'
  );
}

/**
 * Prints goodie meta box
 *
 * @param WP_Post $post
 * @return void
 * @since 0.9.0
 */
function goodie_meta_box_form($post) {
  $goodie_fields = get_goodie_meta($post->ID);
  wp_nonce_field('save_goodie_fields', 'goodie_fields_nonce');
?>
  <div class="cmb_form" id="goodie_option_form">
    <h3>Details</h3>
    <label for="add_goddie_name">Name</label>
    <input type="text" name="goodie_fields[goodie_name]" id="add_goddie_name" placeholder="name"
      value="<?php echo esc_attr($goodie_fields['name']); ?>">
    <label for="goodie_compensation">Acorns</label>
    <input type="number" id="goodie_compensation" name="goodie_fields[compensation]"
      value="<?php echo esc_attr($goodie_fields['compensation']); ?>">
    <label for="add_goddie_name">Image url</label>
    <input type="text" name="goodie_fields[image_url]" id="add_goddie_name" placeholder="url"
      value="<?php echo esc_attr($goodie_fields['image_url']); ?>">
    <label for="goodie_details">Details</label>
    <textarea class="wide_column" rows="15" id="goodie_details" name="goodie_fields[details_markdown]"><?php echo esc_attr($goodie_fields['details_markdown']); ?></textarea>
    <h3>Options</h3>
    <?php foreach ($goodie_fields['options'] as $option => $count) {
      $html_id = sanitize_title($option); ?>
      <label for="<?php echo $html_id; ?>"><?php echo $option; ?></label>
      <input type="number" id="<?php echo $html_id; ?>" name="goodie_fields[options][<?php echo $option; ?>]"
        value="<?php echo esc_html($count); ?>">
      <button type="button" data-goodie="<?php echo $html_id; ?>" onclick="remove_goodie_option(this)"
        value="-" title="remove option"></button>
    <?php } ?>
    <input type="text" id="add_goodie_option_name" placeholder="option name">
    <button type="button" id="add_goodie_option" value="+" title="add option"></button>
  </div>

<?php
}

/**
 * Updates goodie meta box
 *
 * @param integer $post_id
 * @param WP_Post $post
 * @return void
 * @since 0.9.0
 */
add_action('save_post', 'goodie_save_meta', 10, 2);
function goodie_save_meta($post_id, $post) {
  if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
  if (! isset($_POST['goodie_fields_nonce']) || ! wp_verify_nonce($_POST['goodie_fields_nonce'], 'save_goodie_fields')) return;
  if ('goodie' !== $post->post_type || !isset($_POST['goodie_fields'])) return;

  $raw_fields = wp_unslash($_POST['goodie_fields']);
  if (!is_array($raw_fields)) return;

  $fields = [];
  $fields['compensation'] = is_numeric($raw_fields['compensation'] ?? null) ? max(0, (int) $raw_fields['compensation']) : 0;
  $fields['in_stock'] = 0;
  $fields['name'] = sanitize_text_field($raw_fields['goodie_name']);
  $fields['details_markdown'] = sanitize_textarea_field($raw_fields['details_markdown'] ?? '');
  $fields['details_html'] = get_html_from_markdown($fields['details_markdown']);
  $fields['image_url'] = sanitize_textarea_field($raw_fields['image_url'] ?? '');
  $fields['image_html'] = get_html_from_markdown('![](' . $fields['image_url'] . ')');

  foreach ($raw_fields['options'] as $name => $count) {
    if ($name === '') continue;
    $fields['options'][$name] = is_numeric($count ?? null) ? max(0, (int) $count) : 0;
    $fields['in_stock'] += $fields['options'][$name];
  }

  update_post_meta($post_id, 'goodie_fields', $fields);
}

/**
 * Returns goodie meta box fields, adds missing fields
 * Only used in goodie_meta_box_form()
 *
 * @param integer $goodie_id
 * @return array Goodie fields
 * @since 0.9.0
 */
function get_goodie_meta($goodie_id) {
  $fields = get_post_meta($goodie_id, 'goodie_fields', true);
  if (!is_array($fields)) $fields = [];
  $fields['name']             = $fields['name'] ?? '';
  $fields['in_stock']         = $fields['in_stock'] ?? 0;
  $fields['details_markdown'] = $fields['details_markdown'] ?? '';
  $fields['details_html']     = $fields['details_html'] ?? '';
  $fields['image_html']       = $fields['image_html'] ?? '';
  $fields['image_url']        = $fields['image_url'] ?? '';
  $fields['compensation']     = $fields['compensation'] ?? 0;
  $fields['options']          = $fields['options'] ?? [];
  $fields['pick_ups']         = $fields['pick_ups'] ?? [];
  return $fields;
}
