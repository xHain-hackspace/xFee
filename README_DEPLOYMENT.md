# Deployment Guide – xFee WordPress

## 1. Overview

This project provides a WordPress installation with a custom theme (`xfee_theme`) and a MariaDB database.

The application is deployed using Docker Compose on an existing Hetzner VPS.

### Architecture

- WordPress runs in its own Docker container.
- MariaDB runs in its own Docker container.
- Database is stored in persistent Docker volumes.
- The custom theme is included in the WordPress Docker image.
- Parsedown is downloaded at build time in a pinned version.
- An existing reverse proxy handles external HTTP/HTTPS traffic.
- WordPress is not exposed directly to the public internet.

## 2. Project Structure

Expected repository structure:

```text
xFee/
├── xfee_theme/
│   └── ...
├── docker/
│   ├── Dockerfile
│   ├── compose.yaml
│   ├── compose.prod.yaml
│   ├── .env.example
│   ├── .env.prod.example
│   ├── install-parsedown.sh
│   ├── build.sh
│   └── build-prod.sh
├── .gitignore
└── README_DEPLOYMENT.md
```

The production environment file (`docker/.env.prod`) must be created on the server and must never be committed to Git.

## 3. Server Requirements

- A supported Linux distribution is installed.
- Docker Engine is installed and running.
- Docker Compose v2 is available.
- Git is installed.
- An existing reverse proxy is available, or an appropriate proxy setup has been agreed upon.
- A domain name points to the server.
- Ports 80 and 443 are available to the existing reverse proxy.

### Inspect the reverse proxy network

Once the correct network has been identified:

```bash
docker network inspect NETWORK_NAME
```

Replace `NETWORK_NAME` with the actual network name.

The WordPress container must eventually join this network so that the reverse proxy can reach it.

The network must be an existing Docker network accessible to the reverse proxy.

## 4. Prepare the Deployment

### Clone the repository

Choose an appropriate deployment directory:

```bash
git clone REPOSITORY_URL xfee
cd xfee
```

Replace `REPOSITORY_URL` with the actual Git repository URL.

### Create the production environment file

```bash
cp docker/.env.prod.example docker/.env.prod
```

Edit the file:

```bash
nano docker/.env.prod
```

Set secure, unique passwords and the actual reverse proxy network name:

```dotenv
DB_NAME=xfee
DB_USER=xfee
DB_PASSWORD=REPLACE_WITH_A_STRONG_PASSWORD
DB_ROOT_PASSWORD=REPLACE_WITH_A_DIFFERENT_STRONG_PASSWORD

PROXY_NETWORK=ACTUAL_PROXY_NETWORK_NAME
```

Do not reuse passwords from other applications.

Ensure that the file is not accessible to other users:

```bash
chmod 600 docker/.env.prod
```

## 5. Build the Production Image

The production build script performs the following operations:

1. Downloads the pinned Parsedown version.
2. Verifies the downloaded dependency.
3. Installs Parsedown into the theme directory.
4. Builds the WordPress Docker image.

Run:

```bash
./docker/build-prod.sh
```

## 6. Start WordPress

Start the production Compose project:

```bash
docker compose \
  --env-file docker/.env.prod \
  -f docker/compose.prod.yaml \
  up -d
```

Check container status:

```bash
docker compose \
  --env-file docker/.env.prod \
  -f docker/compose.prod.yaml \
  ps
```

Check logs:

```bash
docker compose \
  --env-file docker/.env.prod \
  -f docker/compose.prod.yaml \
  logs -f
```

The WordPress container should be running and the MariaDB health check should report healthy.

## 7. Reverse Proxy Configuration

The WordPress container listens internally on port 80.

It does not publish a host port.

The reverse proxy must:

- Be connected to the same external Docker network.
- Forward requests for the WordPress domain to the service `xfee-wordpress`.
- Use port `80` as the upstream port.
- Handle HTTPS and certificate management.
- Forward the appropriate HTTP headers, including the original host and protocol.

The exact configuration depends on the reverse proxy implementation.

## 8. Initial WordPress Setup

Open the configured domain in a browser.

Complete the WordPress installation:

- Select the site language.
- Set the site title.
- Create the WordPress administrator account.
- Use a strong, unique administrator password.

After installation:

- Verify that the `xfee_theme` theme is available.
- Activate the theme.
- Check the frontend.
- Check the WordPress administration area.

## 9. Persistent Data

The production Compose configuration uses the following named volume:

| Volume    | Purpose          |
| --------- | ---------------- |
| `db_data` | MariaDB database |

These volumes must not be deleted during normal deployments.

Never use `docker compose down -v` as part of a routine deployment. This command removes the project's named volumes and can destroy the database and uploaded media.

## 10. Updating the Theme

```bash
git pull

./docker/build-prod.sh

docker compose \
  --env-file docker/.env.prod \
  -f docker/compose.prod.yaml \
  up -d
```

The database remain stored in their persistent volumes.

## 11. Backups

Before the first production deployment, establish a backup strategy.

At minimum, backups must include:

- The MariaDB database.

Backups should be stored outside the Docker volumes and, preferably, outside the VPS.

## 12. Troubleshooting

### Check container status

```bash
docker compose \
  --env-file docker/.env.prod \
  -f docker/compose.prod.yaml \
  ps
```

### Check WordPress logs

```bash
docker compose \
  --env-file docker/.env.prod \
  -f docker/compose.prod.yaml \
  logs --tail=100 xfee-wordpress
```

### Check database logs

```bash
docker compose \
  --env-file docker/.env.prod \
  -f docker/compose.prod.yaml \
  logs --tail=100 xfee-database
```

### Check Docker network connectivity

```bash
docker network inspect NETWORK_NAME
```

Verify that both the reverse proxy and WordPress container are connected to the expected network.

## 14. Deployment Safety Rules

- Do not modify existing Docker services unnecessarily.
- Do not remove existing Docker networks or volumes.
- Do not expose the database port publicly.
- Do not expose WordPress directly to the internet.
- Do not commit production credentials.
- Do not delete persistent volumes.
- Do not replace or reconfigure the existing reverse proxy without reviewing its current setup.
- Always verify container health after deployment.

---

**Deployment status:** Initial deployment procedure prepared. Server-specific reverse proxy integration and production testing are still required.
