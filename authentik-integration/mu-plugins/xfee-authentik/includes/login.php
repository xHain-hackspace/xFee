<?php

/**
 * Authentik login
 *
 * @package    xfee_authentik
 * @author     Richard Lemke
 * @link       https://mrhide.de
 * @license    GPL-3.0 or later
 */

/**
 * Sign in a WordPress user after successful Authentik authentication.
 *
 * @param WP_User $user The WordPress user to sign in.
 * @return void
 */
function xfee_authentik_sign_in(WP_User $user): void {
    wp_set_current_user($user->ID);

    wp_set_auth_cookie(
        $user->ID,
        true,
        is_ssl()
    );

    do_action('wp_login', $user->user_login, $user);
}
