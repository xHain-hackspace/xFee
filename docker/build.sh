#!/usr/bin/env bash

set -Eeuo pipefail

# Resolve project paths
SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd -- "$SCRIPT_DIR/.." && pwd)"

cd "$PROJECT_ROOT"

# Install theme dependencies
echo "Installing theme dependencies..."
./docker/install-parsedown.sh

# Start the local development environment
echo "Starting local Docker environment..."
docker compose -f docker/compose.yaml up -d

echo "Local environment started successfully."
echo "xFee is available at: http://localhost:8080"