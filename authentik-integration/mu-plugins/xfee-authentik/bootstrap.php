<?php

/**
 * Bootstrap the xFee Authentik integration
 *
 * @package    xfee_authentik
 * @author     Richard Lemke
 * @link       https://mrhide.de
 * @license    GPL-3.0 or later
 */

defined('ABSPATH') || exit;

/**
 * Configure secure PHP session settings for the Authentik integration.
 */
function xfee_authentik_configure_session(): void {
    if (session_status() === PHP_SESSION_ACTIVE) {
        error_log(
            'XFee Authentik: Session was already active before configuration.'
        );
        return;
    }

    $settings = [
        'session.use_strict_mode' => '1',
        'session.use_only_cookies' => '1',
        'session.cookie_httponly' => '1',
        'session.cookie_samesite' => 'Lax',
        'session.cookie_lifetime' => '0',
    ];

    foreach ($settings as $setting => $value) {
        if (ini_set($setting, $value) === false) {
            error_log(
                'XFee Authentik: Could not configure PHP session setting: '
                    . $setting
            );
        }
    }
}

xfee_authentik_configure_session();

/**
 * Load Composer dependencies.
 */
$autoload_file = __DIR__ . '/vendor/autoload.php';

if (is_readable($autoload_file)) {
    require_once $autoload_file;
} else {
    error_log('xFee Authentik: Composer autoloader not found.');
}

// Load WordPress user management functions.
require_once __DIR__ . '/includes/user.php';

// Load OIDC identity validation functions.
require_once __DIR__ . '/includes/identity.php';

// Load WordPress login functions.
require_once __DIR__ . '/includes/login.php';

/**
 * Load the Authentik configuration.
 *
 * The configuration file path is provided through an environment variable.
 * The actual configuration file must not be stored in the public Git repository.
 */
function xfee_authentik_load_config(): ?array {
    $config_file = getenv('XFEE_AUTHENTIK_CONFIG_FILE');

    if (!$config_file || !is_readable($config_file)) {
        error_log('xFee Authentik: Configuration file is missing or unreadable.');
        return null;
    }

    $config = require $config_file;

    if (!is_array($config)) {
        error_log('xFee Authentik: Configuration file must return an array.');
        return null;
    }

    $required_keys = [
        'issuer',
        'client_id',
        'client_secret',
        'redirect_uri',
        'scopes',
    ];

    foreach ($required_keys as $key) {
        if (!array_key_exists($key, $config)) {
            error_log('xFee Authentik: Configuration is missing a required key.');
            return null;
        }
    }

    return $config;
}

/**
 * Store the loaded configuration for later use.
 */
$GLOBALS['xfee_authentik_config'] = xfee_authentik_load_config();

/**
 * Extend the OIDC client with explicit PKCE capability checks.
 */
class XFee_Authentik_OpenIDConnectClient extends \Jumbojett\OpenIDConnectClient {
    /**
     * Check whether the provider advertises PKCE S256 support.
     */
    public function supportsPkceS256(): bool {
        $methods = $this->getProviderConfigValue(
            'code_challenge_methods_supported',
            []
        );

        return is_array($methods)
            && in_array('S256', $methods, true);
    }
}

/**
 * Create a configured OpenID Connect client.
 */
function xfee_authentik_create_client(): ?XFee_Authentik_OpenIDConnectClient {
    $config = $GLOBALS['xfee_authentik_config'] ?? null;

    if (!is_array($config)) {
        return null;
    }

    $client = new XFee_Authentik_OpenIDConnectClient(
        $config['issuer'],
        $config['client_id'],
        $config['client_secret'],
        $config['issuer']
    );

    $client->setRedirectURL($config['redirect_uri']);

    // Require PKCE using SHA-256.
    $client->setCodeChallengeMethod('S256');
    if (!$client->supportsPkceS256()) {
        throw new RuntimeException(
            'The OIDC provider does not support PKCE S256.'
        );
    }

    $client->addScope($config['scopes']);

    return $client;
}

/**
 * Generate the URL for starting Authentik authentication.
 */
function xfee_authentik_login_url(): string {
    return add_query_arg(
        'action',
        'xfee_authentik_login',
        admin_url('admin-post.php')
    );
}

/**
 * Start the OpenID Connect login flow.
 */
function xfee_authentik_start_login(): void {
    if (is_user_logged_in()) {
        wp_safe_redirect(home_url('/shifts/'));
        exit;
    }

    try {
        $client = xfee_authentik_create_client();

        if (!$client) {
            throw new RuntimeException('Authentik client is unavailable.');
        }

        // Start the PHP session with the configured security settings.
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $client->authenticate();
    } catch (\Throwable $exception) {
        error_log('XFee Authentik: Login initialization failed: ' . $exception->getMessage());

        wp_die(
            'Could not start Authentik login.',
            'Authentication error',
            ['response' => 500]
        );
    }
}


/**
 * Handle the OpenID Connect callback.
 */

/**
 * Handle the OpenID Connect callback.
 */
function xfee_authentik_handle_callback(): void {
    // Ensure that the PHP session is available.
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    // Validate state before exchanging the authorization code.
    $received_state = $_GET['state'] ?? null;
    $stored_state = $_SESSION['openid_connect_state'] ?? null;

    if (
        !is_string($received_state)
        || !is_string($stored_state)
        || !hash_equals($stored_state, $received_state)
    ) {
        unset(
            $_SESSION['openid_connect_state'],
            $_SESSION['openid_connect_nonce'],
            $_SESSION['openid_connect_code_verifier']
        );

        session_write_close();

        wp_die(
            'Invalid authentication response.',
            'Authentication error',
            ['response' => 400]
        );
    }

    $authenticated_user = null;
    $error_message = 'Authentik authentication could not be completed.';
    $response_code = 401;

    try {
        $client = xfee_authentik_create_client();

        if (!$client) {
            throw new RuntimeException('Authentik client is unavailable.');
        }

        // Validate the OIDC response using the library.
        if (!$client->authenticate()) {
            throw new RuntimeException('Authentication failed.');
        }

        // Read the verified ID token claims.
        $claims = $client->getVerifiedClaims();

        if (!is_object($claims)) {
            throw new RuntimeException('Verified ID token claims are unavailable.');
        }

        // Require a valid nonce.
        if (
            !isset($claims->nonce)
            || !is_string($claims->nonce)
            || $claims->nonce === ''
        ) {
            throw new RuntimeException('Missing or invalid nonce.');
        }

        // Require an expiration timestamp that is still valid.
        if (
            !isset($claims->exp)
            || !is_int($claims->exp)
            || $claims->exp <= time()
        ) {
            throw new RuntimeException('Missing or expired ID token.');
        }

        // Validate and normalize the verified identity.
        $identity = xfee_authentik_validate_identity($claims);

        if (is_wp_error($identity)) {
            throw new RuntimeException($identity->get_error_message());
        }

        // Find or create the corresponding WordPress user.
        $user = xfee_authentik_resolve_user($identity);

        if (is_wp_error($user)) {
            throw new RuntimeException($user->get_error_message());
        }

        if (!$user instanceof WP_User) {
            throw new RuntimeException('WordPress user resolution failed.');
        }

        $authenticated_user = $user;
    } catch (\Throwable $exception) {
        error_log(
            'XFee Authentik: Callback failed: '
                . $exception->getMessage()
        );

        if (str_contains($exception->getMessage(), 'unavailable')) {
            $response_code = 503;
            $error_message = 'Authentik login is currently unavailable.';
        }
    } finally {
        // Clean up OIDC session data after processing the callback.
        unset(
            $_SESSION['openid_connect_state'],
            $_SESSION['openid_connect_nonce'],
            $_SESSION['openid_connect_code_verifier']
        );

        session_write_close();
    }

    if ($authenticated_user instanceof WP_User) {
        // Create the normal WordPress login session.
        xfee_authentik_sign_in($authenticated_user);

        wp_safe_redirect(home_url('/shifts/'));
        exit;
    }

    wp_die(
        $error_message,
        'Authentication error',
        ['response' => $response_code]
    );
}



// Register login handlers.
add_action('admin_post_nopriv_xfee_authentik_login', 'xfee_authentik_start_login');
add_action('admin_post_xfee_authentik_login', 'xfee_authentik_start_login');

// Register callback handlers.
add_action('admin_post_nopriv_xfee_authentik_callback', 'xfee_authentik_handle_callback');
add_action('admin_post_xfee_authentik_callback', 'xfee_authentik_handle_callback');
