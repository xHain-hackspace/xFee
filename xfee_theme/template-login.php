<?php

/**
 * Login form for visitors
 * Redirect to shifts after login
 *
 * Template Name: Login
 *
 * @package    xfee_theme
 * @author     Richard Lemke
 * @link       https://mrhide.de
 * @license    GPL-3.0 or later
 **/

if (
  is_user_logged_in()
  && (!isset($_GET['action']) || $_GET['action'] !== 'logout')
) {
  wp_safe_redirect(home_url('/shifts/'));
  exit;
}

get_header(); ?>

<div class="login_page">

  <p><?php echo get_option('blogdescription'); ?></p>

  <div class="login_form">
    <h2><?php echo get_the_title(); ?></h2>
    <?php
    wp_login_form([
      'redirect'       => home_url('/shifts/'),
      'remember'       => true,
      'label_username' => 'user or email',
      'label_password' => 'password',
      'label_remember' => 'stay logged in',
      'label_log_in'   => 'log in',
    ]);
    ?>

    <div class="authentik-login">
      <a href="<?php echo esc_url(xfee_authentik_login_url()); ?>">
        Sign in with Authentik
      </a>
    </div>
  </div>
</div>

<?php get_footer(); ?>