#!/bin/bash
set -e

cd /var/www/html

# Start Apache in the background
apache2-foreground &

# Wait for Apache to become available
echo "Waiting for Apache to start..."
until curl -sSf http://localhost/ > /dev/null; do
  echo "Apache is not ready yet. Retrying in 5 seconds..."
  sleep 5
done
echo "Apache is now available. Running the installer..."

# Run the installer
composer require javanile/http-robot:0.0.2 --with-all-dependencies && php install.php

# Apply patches
echo "Applying patches from /patch..."
if [ -d "patch" ]; then
  for patchfile in patch/*.patch; do
    if [ -f "$patchfile" ]; then
      echo "Running $patchfile..."
      case "$patchfile" in
        *add_related_field.patch*)
          patch --batch -p4 < "$patchfile" || true
          ;;
        *fix_error_id.patch*)
          patch --batch -p4 < "$patchfile" || true
          ;;
        *)
          echo "Unknown patch file: $patchfile, skipping"
          ;;
      esac
    fi
  done
else
  echo "No /patch directory found. Skipping patching step."
fi

# Optional custom script
# php patch/ImportHelloWorld.php

echo "All patches applied. Service is up."
