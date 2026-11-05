<?php

/**
 * Prints the header
 *
 * @package    xfee_theme
 * @author     Richard Lemke
 * @link       https://mrhide.de
 * @license    GPL-3.0 or later
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
  <title><?php bloginfo('name');
          wp_title(); ?></title>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="theme-color" content="#272123">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="profile" href="http://gmpg.org/xfn/11">
  <?php wp_head(); ?>
</head>

<body <?php body_class(); ?> id="top">

  <!-- HEADER -->
  <header>
    <a id="page_title" href="<?php echo get_home_url(); ?>">
      <div id="page_logo"><?php echo get_anim_logo(); ?></div>
      <h1>
        <?php echo get_bloginfo('name'); ?>
      </h1>
      <div id="title_pronunciation" data-pronunciation="[ɪksˈfeː]"></div>
    </a>

    <?php print_xfee_nav_menu(); ?>

    <div id="page_tag_line"><?php echo get_option('blogdescription'); ?></div>
  </header>

  <!-- SIDE-BAR -->
  <aside>
    <?php print_user_sidebar(); ?>
  </aside>

  <!-- CONTENT -->
  <main>