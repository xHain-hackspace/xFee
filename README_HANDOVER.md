# XFee – Technical Handover

## 1. Project Overview

This repository contains a custom WordPress theme and a custom OpenID Connect integration for Authentik.

The intended deployment is a Docker-based WordPress installation on an existing Hetzner VPS. Authentik is already installed and used by other services on that server.

The WordPress installation is intended to use Authentik for single sign-on (SSO), while retaining WordPress users and roles for normal WordPress functionality.

## 2. Repository Structure

The repository contains:

- `xfee_theme/` – Custom WordPress theme.
- `authentik-integration/` – Custom Authentik integration.
- `docker/` – Local and production Docker configuration and build scripts.
- `README_DEPLOYMENT.md` – Deployment instructions.
- `README_HANDOVER.md` – This handover document.

WordPress core and the local WordPress database are not part of the Git repository.

## 3. Implementation Status

### Implemented

- Custom WordPress theme.
- Local Docker development environment.
- Separate production Docker configuration.
- Custom WordPress MU-plugin for Authentik integration.
- OpenID Connect client library (`jumbojett/openid-connect-php`).
- External configuration file for Authentik credentials.
- WordPress user mapping using the stable OIDC `sub` claim.
- Automatic creation of new WordPress users with the `author` role.
- Existing WordPress roles are preserved.
- State validation using `hash_equals()`.
- Nonce validation, including an explicit requirement for a nonce claim.
- PKCE configured to use S256.
- Session settings for strict mode, cookie-only sessions, HttpOnly, SameSite=Lax and session-only cookie lifetime.

### Technically reviewed, but not tested against a live Authentik instance

- PKCE challenge and verifier generation in the installed OIDC library version.
- Provider capability check for PKCE S256.
- State validation in the callback.
- Nonce validation in the OIDC library.
- PHP session settings in the local WordPress environment.

### Not yet verified

- Successful end-to-end login against the actual Authentik instance.
- Actual production HTTPS and reverse-proxy behavior.
- Secure session cookie configuration in production.
- Logout behavior and session termination.
- User creation under concurrent login attempts.
- Full review of theme form handlers and AJAX security.
- Production deployment and rollback procedure.

## 4. Information Required from the Server / Authentik Administrator

Please provide or confirm the following:

### Authentik

- OIDC issuer URL.
- Client ID.
- Client secret.
- Approved redirect URI:
  `https://<wordpress-domain>/wp-admin/admin-post.php?action=xfee_authentik_callback`
- Confirmation that the provider supports PKCE with S256.
- Confirmation that the `openid`, `profile` and `email` scopes are available.
- Confirmation that the ID token includes `sub`, `email`, `email_verified` and `nonce`.

Credentials must be transferred securely and must not be committed to Git.

### Server and reverse proxy

- Existing Docker network name used by the reverse proxy.
- Reverse proxy software and configuration approach.
- Public WordPress hostname.
- HTTPS termination details.
- Whether the reverse proxy forwards the original HTTPS protocol correctly.
- Whether the WordPress container is expected to run as a single instance or multiple replicas.

The production configuration currently expects an existing external Docker network. No reverse proxy configuration or network name has been assumed.

## 5. User and Role Behavior

- Existing WordPress users are matched using their stored Authentik subject (`sub`).
- New users are created with the WordPress role `author`.
- Existing WordPress roles are not automatically changed.
- Users with an existing email address but no matching Authentik subject are not automatically linked.
- Email verification is required.

The `author` role currently includes the standard WordPress upload capability. This should be reviewed if media uploads are not intended.

## 6. Production Configuration and Deployment

The production environment requires:

- A production environment file (`docker/.env.prod`).
- A production Authentik configuration file containing the actual credentials.
- The existing external Docker proxy network.
- A secure way to provide the Authentik configuration to the container.

The production Authentik configuration must remain outside Git and should be mounted read-only or supplied through an equivalent secure mechanism.

The deployment procedure is documented in `README_DEPLOYMENT.md`.

## 7. Recommended Next Steps

1. Administrator supplies the Authentik and reverse-proxy configuration details.
2. Confirm the production hostname and redirect URI.
3. Configure the production secrets and environment.
4. Review HTTPS detection and enable secure session cookies.
5. Review remaining user-management and theme security issues.
6. Deploy to a test environment.
7. Perform an end-to-end login, user creation, existing-user and logout test.
8. Approve production deployment only after successful testing.

## 8. Current Readiness

The implementation is prepared for administrator review and configuration.

It is **not yet approved for production use**. A successful end-to-end authentication test against the actual Authentik instance and the remaining security reviews are required before production approval.
