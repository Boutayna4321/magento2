#!/bin/bash
set -e

MAGE_DIR="/var/www/html"

chown -R www-data:www-data "${MAGE_DIR}"
find "${MAGE_DIR}" -type d -exec chmod 755 {} \;
chmod -R 777 "${MAGE_DIR}/pub/media"
chmod -R 777 "${MAGE_DIR}/pub/static"
chmod -R 777 "${MAGE_DIR}/var"
chmod -R 777 "${MAGE_DIR}/generated"
chmod u+x "${MAGE_DIR}/bin/magento"

exec "$@"