
#!/usr/bin/env bash

set -Eeuo pipefail

# Resolve project paths
SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd -- "$SCRIPT_DIR/.." && pwd)"

cd "$PROJECT_ROOT"

ENV_FILE="docker/.env.prod"
COMPOSE_FILE="docker/compose.prod.yaml"

# Check production environment configuration
if [[ ! -f "$ENV_FILE" ]]; then
    echo "Error: Production environment file not found: $ENV_FILE" >&2
    echo "Create it from docker/.env.prod.example first." >&2
    exit 1
fi

# Install theme dependencies
echo "Installing theme dependencies..."
./docker/install-parsedown.sh

# Build production Docker image
echo "Building production Docker image..."
docker compose \
    --env-file "$ENV_FILE" \
    -f "$COMPOSE_FILE" \
    build

echo "Production build completed successfully."
