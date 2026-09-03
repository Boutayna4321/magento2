#!/bin/bash
set -euo pipefail

MAGE_DIR="/var/www/html"
RUN_USER="${RUN_USER:-www-data}"

# Directories Magento must be able to write to, even on a fresh checkout.
mkdir -p \
  "${MAGE_DIR}/pub/media" \
  "${MAGE_DIR}/pub/static" \
  "${MAGE_DIR}/var" \
  "${MAGE_DIR}/generated" \
  "${MAGE_DIR}/app/etc"

TARGET_UID="$(id -u "${RUN_USER}")"
TARGET_GID="$(id -g "${RUN_USER}")"
CURRENT_UID="$(stat -c %u "${MAGE_DIR}")"

# Only normalise ownership when it has actually drifted.
#
# The previous version ran an unconditional `chown -R` plus a
# `find -type d -exec chmod 755 {} \;` across the whole tree on EVERY start.
# With vendor/ present that is ~840MB and tens of thousands of directories,
# one forked chmod per directory, which made container start extremely slow
# and rewrote ownership of the developer's bind-mounted source each time.
if [ "${CURRENT_UID}" != "${TARGET_UID}" ]; then
  echo "[entrypoint] normalising ownership of ${MAGE_DIR} -> ${TARGET_UID}:${TARGET_GID}"
  chown -R "${TARGET_UID}:${TARGET_GID}" "${MAGE_DIR}"
else
  echo "[entrypoint] ownership already correct (${TARGET_UID}:${TARGET_GID}), skipping chown -R"
fi

# Ensure the four runtime trees are owned by the runtime user.
# Subdirectories may have been created by root (e.g. during composer
# install or a previous container start) even when the top-level mount
# ownership is already correct. Without this, PHP-FPM workers cannot
# write generated classes, cache files, or uploads.
for d in pub/media pub/static var generated; do
  chown -R "${TARGET_UID}:${TARGET_GID}" "${MAGE_DIR}/${d}"
done

# Grant owner+group write on the four runtime trees only. Because the container
# user's UID matches the host developer's UID, 775/664 is sufficient -- there is
# no longer any need for world-writable 777.
for d in pub/media pub/static var generated; do
  chmod -R u+rwX,g+rwX,o+rX "${MAGE_DIR}/${d}" 2>/dev/null || true
done

chmod u+x "${MAGE_DIR}/bin/magento" 2>/dev/null || true

exec "$@"
