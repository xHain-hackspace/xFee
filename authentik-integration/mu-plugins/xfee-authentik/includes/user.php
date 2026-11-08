<?php

/**
 * Authentik user functions
 *
 * @package    xfee_authentik
 * @author     Richard Lemke
 * @link       https://mrhide.de
 * @license    GPL-3.0 or later
 */

/**
 * Find a WordPress user by their Authentik subject identifier.
 *
 * @param string $subject The OIDC subject identifier.
 * @return WP_User|WP_Error|null The matching user, an error if ambiguous, or null if not found.
 */
function xfee_authentik_find_user_by_subject(string $subject) {
    if ($subject === '') {
        return null;
    }

    $users = get_users([
        'meta_key' => 'xfee_authentik_subject',
        'meta_value' => $subject,
        'number' => 2,
    ]);

    if (count($users) > 1) {
        return new WP_Error(
            'xfee_authentik_ambiguous_subject',
            'Multiple WordPress accounts are linked to this Authentik identity.'
        );
    }

    return $users[0] ?? null;
}

/**
 * Create a new WordPress user linked to an Authentik subject.
 *
 * @param string $subject The OIDC subject identifier.
 * @param string $email The verified email address.
 * @param string $preferred_username The preferred username.
 * @return WP_User|WP_Error The created user or an error.
 */
function xfee_authentik_create_user(
    string $subject,
    string $email,
    string $preferred_username
) {
    if ($subject === '' || !is_email($email)) {
        return new WP_Error(
            'xfee_authentik_invalid_identity',
            'The Authentik identity is incomplete or invalid.'
        );
    }

    // Prevent duplicate Authentik subject mappings.
    $existing_user = xfee_authentik_find_user_by_subject($subject);

    if (is_wp_error($existing_user)) {
        return $existing_user;
    }

    if ($existing_user) {
        return new WP_Error(
            'xfee_authentik_user_exists',
            'A WordPress account is already linked to this Authentik identity.'
        );
    }

    // Never link an existing account by email automatically.
    if (email_exists($email)) {
        return new WP_Error(
            'xfee_authentik_email_exists',
            'A WordPress account with this email address already exists.'
        );
    }

    $username = sanitize_user($preferred_username, true);

    if ($username === '') {
        $username = sanitize_user(
            current(explode('@', $email)),
            true
        );
    }

    if ($username === '') {
        return new WP_Error(
            'xfee_authentik_invalid_username',
            'A valid WordPress username could not be generated.'
        );
    }

    if (username_exists($username)) {
        return new WP_Error(
            'xfee_authentik_username_exists',
            'The requested WordPress username is already in use.'
        );
    }

    $temporary_password = wp_generate_password(64, true, true);

    $user_id = wp_insert_user([
        'user_login' => $username,
        'user_email' => $email,
        'user_pass' => $temporary_password,
        'role' => 'author',
    ]);

    if (is_wp_error($user_id)) {
        return $user_id;
    }

    $meta_saved = update_user_meta(
        $user_id,
        'xfee_authentik_subject',
        $subject
    );

    if (!$meta_saved && get_user_meta($user_id, 'xfee_authentik_subject', true) !== $subject) {
        require_once ABSPATH . 'wp-admin/includes/user.php';
        wp_delete_user($user_id);

        return new WP_Error(
            'xfee_authentik_subject_save_failed',
            'The Authentik identity could not be linked to the WordPress account.'
        );
    }

    return get_user_by('id', $user_id);
}


/**
 * Update the profile data of an existing WordPress user.
 *
 * The user's existing role and capabilities are preserved.
 *
 * @param WP_User $user The existing WordPress user.
 * @param string $email The verified email address.
 * @param string $display_name The display name.
 * @return WP_User|WP_Error The updated user or an error.
 */
function xfee_authentik_update_user(
    WP_User $user,
    string $email,
    string $display_name
) {
    if (!is_email($email)) {
        return new WP_Error(
            'xfee_authentik_invalid_email',
            'The verified email address is invalid.'
        );
    }

    $existing_user = get_user_by('email', $email);

    if ($existing_user && (int) $existing_user->ID !== (int) $user->ID) {
        return new WP_Error(
            'xfee_authentik_email_conflict',
            'The email address is already assigned to another WordPress account.'
        );
    }

    $result = wp_update_user([
        'ID' => $user->ID,
        'user_email' => $email,
        'display_name' => sanitize_text_field($display_name),
    ]);

    if (is_wp_error($result)) {
        return $result;
    }

    return get_user_by('id', $user->ID);
}

/**
 * Find or create a WordPress user for a verified Authentik identity.
 *
 * @param array $identity The normalized OIDC identity.
 * @return WP_User|WP_Error The WordPress user or an error.
 */
function xfee_authentik_resolve_user(array $identity) {
    $subject = $identity['subject'] ?? '';
    $email = $identity['email'] ?? '';
    $preferred_username = $identity['preferred_username'] ?? '';
    $display_name = $identity['display_name'] ?? '';

    if (
        !is_string($subject) || $subject === ''
        || !is_string($email) || !is_email($email)
    ) {
        return new WP_Error(
            'xfee_authentik_invalid_identity',
            'The Authentik identity is incomplete or invalid.'
        );
    }

    $user = xfee_authentik_find_user_by_subject($subject);

    if (is_wp_error($user)) {
        return $user;
    }

    if ($user instanceof WP_User) {
        return xfee_authentik_update_user(
            $user,
            $email,
            $display_name
        );
    }

    return xfee_authentik_create_user(
        $subject,
        $email,
        $preferred_username
    );
}
