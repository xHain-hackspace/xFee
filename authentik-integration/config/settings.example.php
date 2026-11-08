<?php

/**
 * Example configuration for the xFee Authentik integration.
 *
 * Never commit the actual settings.php file.
 */

return [
    'issuer' => 'https://auth.example.com/application/o/xfee/',

    'client_id' => 'replace-with-client-id',

    'client_secret' => 'replace-with-client-secret',

    'redirect_uri' => 'http://localhost:8080/wp-admin/admin-post.php?action=xfee_authentik_callback',

    'scopes' => [
        'openid',
        'profile',
        'email',
    ],
];
