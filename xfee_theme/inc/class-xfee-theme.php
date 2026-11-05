<?php

/**
 * xFee Theme base class
 *
 * @package    xfee_theme
 * @author     Richard Lemke
 * @link       https://mrhide.de
 * @license    GPL-3.0 or later
 */
class xFee_Theme {

  public function __construct() {
    $this->load_theme_files();
    $this->load_libraries();
    $this->load_module_files();
    $this->initialize_admin();
  }

  private function load_theme_files() {
    $files = [
      '/inc/enqueue.php',
      '/inc/wp-shrink.php',
      '/inc/theme-support.php',
      // '/inc/ajax.php',
      '/assets/img/logo-anim.php',
      '/inc/custom-post-columns.php',
      '/inc/nav-menu.php',
    ];
    $this->require_files($files);
  }

  private function load_libraries() {
    $files = [
      '/libs/parsedown/Parsedown.php',
    ];
    $this->require_files($files);
  }

  private function load_module_files() {
    $files = [
      '/shift/shift-row-template.php',
      '/shift/shift-functions.php',
      '/shift/shift-meta-box.php',
      '/shift/shift-post-type.php',
      '/shift/shift-form.php',
      '/shift/shift-stats.php',

      '/goodie/goodie-functions.php',
      '/goodie/goodie-post-type.php',
      '/goodie/goodie-meta-box.php',
      '/goodie/goodie-form.php',

      '/pick_up/pick_up-functions.php',
      '/pick_up/pick_up-form.php',

      '/editor/editor-functions.php',

      '/user/user-sidebar.php',
      '/user/user-functions.php',
    ];
    $this->require_files($files);
  }

  private function initialize_admin() {
    require_once get_template_directory() . '/admin/class-xfee-admin.php';
    new xFee_Admin();
  }

  private function require_files(array $files) {
    $theme_path = get_template_directory();
    foreach ($files as $file) {
      $path = $theme_path . $file;
      if (file_exists($path)) {
        require_once $path;
      }
    }
  }
}
