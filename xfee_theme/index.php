<?php

/**
 * Prints default template
 *
 * @package    xfee_theme
 * @author     Richard Lemke
 * @link       https://mrhide.de
 * @license    GPL-3.0 or later
 */

get_header();

if (have_posts()) {
	while (have_posts()) {
		the_post();
		the_title();
		the_content();
	}
}

get_footer();
