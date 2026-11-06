
#!/usr/bin/env bash

set -Eeuo pipefail

# Pin the Parsedown version
PARSEDOWN_VERSION="1.8.0"
PARSEDOWN_REPO="https://github.com/erusev/parsedown.git"

# Resolve project paths
SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd -- "$SCRIPT_DIR/.." && pwd)"

TARGET_DIR="$PROJECT_ROOT/xfee_theme/libs/parsedown"
TARGET_PARENT="$(dirname "$TARGET_DIR")"

# Define temporary paths
WORK_DIR="$(mktemp -d "${TMPDIR:-/tmp}/parsedown.XXXXXX")"
STAGED_DIR="$TARGET_PARENT/.parsedown-new-$$"
BACKUP_DIR="$TARGET_PARENT/.parsedown-backup-$$"

cleanup() {
    # Restore the previous installation if replacement failed
    if [[ -d "$BACKUP_DIR" && ! -e "$TARGET_DIR" ]]; then
        mv "$BACKUP_DIR" "$TARGET_DIR"
    fi

    rm -rf "$WORK_DIR" "$STAGED_DIR"
}

trap cleanup EXIT

echo "Installing Parsedown $PARSEDOWN_VERSION ..."

mkdir -p "$TARGET_PARENT"

# Clone the exact release tag
git clone \
    --depth 1 \
    --branch "$PARSEDOWN_VERSION" \
    "$PARSEDOWN_REPO" \
    "$WORK_DIR/parsedown"

# Verify that the expected release tag was checked out
ACTUAL_VERSION="$(git -C "$WORK_DIR/parsedown" describe --tags --exact-match HEAD)"

if [[ "$ACTUAL_VERSION" != "$PARSEDOWN_VERSION" ]]; then
    echo "Error: Expected Parsedown $PARSEDOWN_VERSION, got $ACTUAL_VERSION." >&2
    exit 1
fi

# Verify that the main PHP file exists
if [[ ! -f "$WORK_DIR/parsedown/Parsedown.php" ]]; then
    echo "Error: Parsedown.php was not found in the downloaded repository." >&2
    exit 1
fi

# Remove Git metadata from the downloaded dependency
rm -rf "$WORK_DIR/parsedown/.git"

# Stage the new version next to the target directory
cp -R "$WORK_DIR/parsedown" "$STAGED_DIR"

# Back up the existing installation
if [[ -e "$TARGET_DIR" ]]; then
    mv "$TARGET_DIR" "$BACKUP_DIR"
fi

# Install the new version
if ! mv "$STAGED_DIR" "$TARGET_DIR"; then
    echo "Error: Failed to install Parsedown." >&2
    exit 1
fi

# Remove the backup only after successful installation
rm -rf "$BACKUP_DIR"

echo "Parsedown $PARSEDOWN_VERSION installed successfully."
echo "Target: $TARGET_DIR"
