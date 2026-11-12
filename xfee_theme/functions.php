<?php

/**
 * Set theme options here
 *
 * @package    xfee_theme
 * @author     Richard Lemke
 * @link       https://mrhide.de
 * @license    GPL-3.0 or later
 */

/**
 *
 * ╱╱╱╱╭━━━╮╱╱╱╱╱╱╱╱╱╱╭━━━╮╱╱╱╱╱╭╮
 * ╱╱╱╱┃╭━━╯╱╱╱╱╱╱╱╱╱╱┃╭━╮┃╱╱╱╱╭╯╰╮
 * ╭╮╭╮┃╰━━╮╭━━╮╭━━╮╱╱┃┃╱┃┃╭━━╮╰╮╭╯╭╮╭━━╮╭━╮╱╭━━╮
 * ╰╋╋╯┃╭━━╯┃┃━┫┃┃━┫╱╱┃┃╱┃┃┃╭╮┃╱┃┃╱┣┫┃╭╮┃┃╭╮╮┃━━┫
 * ╭╋╋╮┃┃╱╱╱┃┃━┫┃┃━┫╱╱┃╰━╯┃┃╰╯┃╱┃╰╮┃┃┃╰╯┃┃┃┃┃┣━━┃
 * ╰╯╰╯╰╯╱╱╱╰━━╯╰━━╯╱╱╰━━━╯┃╭━╯╱╰━╯╰╯╰━━╯╰╯╰╯╰━━╯
 * ╱╱╱╱╱╱╱╱╱╱╱╱╱╱╱╱╱╱╱╱╱╱╱╱┃┃
 * ╱╱╱╱╱╱╱╱╱╱╱╱╱╱╱╱╱╱╱╱╱╱╱╱╰╯
 *
 */

date_default_timezone_set('Europe/Berlin');

$GLOBALS['xfee_config'] = [

    'debug' => false,
    // Debug mode, not yet in use !

    'max_acorn_per_shift' => 4,
    // Maximum how much to earn in one shift

    'max_shift_per_user' => 4,
    // Maximum amount of shifts a user can sign-up for

    'shifts_for_weeks' => 6,
    // How many weeks are shown in the shift overview

    'cancel_after_start_min' => 15,
    // How long after shift-start can a shift be canceled

    'finish_before_end_min' => 10,
    // How early before shift-end can a shift be finished

    'level_down_md' => 2,
    // Parsing markdown fields also levels down the headlines, e.g. h1 to h3

];


/* That's all, stop editing! */

require_once get_template_directory() . '/inc/class-xfee-theme.php';

new xFee_Theme();
