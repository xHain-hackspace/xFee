<?php

/**
 * Validates users
 *
 * @package    xfee_authentik
 * @author     Richard Lemke
 * @link       https://mrhide.de
 * @license    GPL-3.0 or later
 */

/**
 * Validate and normalize the verified OIDC identity claims.
 *
 * @param object $claims Verified ID token claims.
 * @return array|WP_Error Normalized identity data or an error.
 */
function xfee_authentik_validate_identity(object $claims) {
    $subject = $claims->sub ?? null;
    $email = $claims->email ?? null;
    $email_verified = $claims->email_verified ?? false;

    if (!is_string($subject) || $subject === '') {
        return new WP_Error(
            'xfee_authentik_invalid_subject',
            'The OIDC subject identifier is missing or invalid.'
        );
    }

    if (!is_string($email) || !is_email($email)) {
        return new WP_Error(
            'xfee_authentik_invalid_email',
            'The OIDC email address is missing or invalid.'
        );
    }

    if ($email_verified !== true) {
        return new WP_Error(
            'xfee_authentik_unverified_email',
            'Authentik has not verified the email address.'
        );
    }

    $preferred_username = $claims->preferred_username ?? '';

    if (!is_string($preferred_username)) {
        $preferred_username = '';
    }

    $name = $claims->name ?? '';

    if (!is_string($name)) {
        $name = '';
    }

    $display_name = $name !== ''
        ? $name
        : ($preferred_username !== '' ? $preferred_username : $email);

    return [
        'subject' => $subject,
        'email' => sanitize_email($email),
        'preferred_username' => sanitize_user($preferred_username, true),
        'display_name' => sanitize_text_field($display_name),
    ];
}
