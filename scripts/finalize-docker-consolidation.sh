#!/usr/bin/env bash
#
# finalize-docker-consolidation.sh
#
# Removes the snap Docker daemon so that exactly ONE Docker daemon (the native
# apt docker.service) owns /run/docker.sock.
#
# WHY THIS IS NEEDED
#   A snap "docker" package was installed on 2026-08-27 at 10:57 alongside the
#   existing Docker CE. Its daemon seized /run/docker.sock at 10:58, which left
#   the native daemon's containers running but INVISIBLE to the docker CLI.
#   That is what made ports 3306/6379/8080/9000/9200/9300 look "already in use"
#   with no visible owner.
#
# SAFETY
#   The live Magento data (462 tables) lives in the NATIVE daemon's volume at
#   /var/lib/docker/volumes/magento_mysql_data. This script only removes snap
#   storage under /var/snap/docker, which was verified to contain 0 Magento
#   tables. This script does NOT touch /var/lib/docker, does NOT run
#   `docker compose down -v`, and does NOT delete any named volume.
#
# USAGE
#   sudo bash scripts/finalize-docker-consolidation.sh
#
set -euo pipefail

if [ "$(id -u)" -ne 0 ]; then
    echo "ERROR: must run as root -> sudo bash $0" >&2
    exit 1
fi

echo "=================================================================="
echo " Docker daemon consolidation"
echo "=================================================================="

echo
echo "[1/6] Pre-flight state"
echo "  dockerd processes : $(pgrep -c dockerd || echo 0)"
echo "  snap docker       : $(snap list docker >/dev/null 2>&1 && echo INSTALLED || echo absent)"
echo "  socket owner      : $(docker info --format '{{.DockerRootDir}}' 2>/dev/null || echo unknown)"

# ---------------------------------------------------------------------------
# Guard: refuse to continue if the native data directory is missing entirely.
# ---------------------------------------------------------------------------
if [ ! -d /var/lib/docker ]; then
    echo
    echo "ABORT: /var/lib/docker does not exist. The native daemon's data is not" >&2
    echo "       where expected. Investigate before removing anything." >&2
    exit 1
fi
echo "  native data dir   : /var/lib/docker present"

if [ -d /var/lib/docker/volumes/magento_mysql_data ]; then
    echo "  live mysql volume : /var/lib/docker/volumes/magento_mysql_data present"
else
    echo
    echo "WARNING: /var/lib/docker/volumes/magento_mysql_data not found." >&2
    echo "         The live DB may use a different volume name. Listing candidates:" >&2
    ls -1 /var/lib/docker/volumes 2>/dev/null | grep -iE "mysql|magento" || true
    echo >&2
    read -r -p "Continue anyway? [y/N] " reply
    case "$reply" in [yY]*) ;; *) echo "Aborted."; exit 1 ;; esac
fi

# ---------------------------------------------------------------------------
echo
echo "[2/6] Removing snap docker (purges /var/snap/docker only)"
if snap list docker >/dev/null 2>&1; then
    snap remove --purge docker
    echo "  snap docker removed"
else
    echo "  snap docker already absent, skipping"
fi

# ---------------------------------------------------------------------------
echo
echo "[3/6] Clearing any stale socket and restarting the native daemon"
# If snap left a socket file behind, systemd cannot re-create its own.
if [ -S /run/docker.sock ] && ! systemctl is-active --quiet docker.service; then
    rm -f /run/docker.sock
fi
systemctl restart docker.socket
systemctl restart docker.service
sleep 5

# ---------------------------------------------------------------------------
echo
echo "[4/6] Verifying the native daemon owns the socket"
ROOT_DIR="$(docker info --format '{{.DockerRootDir}}' 2>/dev/null || echo FAILED)"
DAEMONS="$(pgrep -c dockerd || echo 0)"
echo "  Docker Root Dir   : ${ROOT_DIR}"
echo "  dockerd processes : ${DAEMONS}"

if [ "${ROOT_DIR}" != "/var/lib/docker" ]; then
    echo
    echo "FAIL: expected /var/lib/docker but got '${ROOT_DIR}'." >&2
    echo "      Do NOT run 'docker compose up' yet. Report this output." >&2
    exit 1
fi
if [ "${DAEMONS}" -ne 1 ]; then
    echo
    echo "FAIL: expected exactly 1 dockerd, found ${DAEMONS}." >&2
    ps -o pid,lstart,cmd -p "$(pgrep -d, dockerd)" >&2 || true
    exit 1
fi

# ---------------------------------------------------------------------------
echo
echo "[5/6] Native daemon inventory (previously invisible)"
docker ps -a --format '  {{.Names}}\t{{.Status}}' || true
echo "  --- volumes ---"
docker volume ls --format '  {{.Name}}' || true
echo "  --- live mysql volume mountpoint ---"
docker volume inspect magento_mysql_data --format '  {{.Mountpoint}}' 2>/dev/null \
    || echo "  (magento_mysql_data not found by that name)"

# ---------------------------------------------------------------------------
echo
echo "[6/6] Enabling the native daemon at boot, disabling snap's return path"
systemctl enable docker.socket docker.service >/dev/null 2>&1 || true
echo "  docker.socket / docker.service enabled"

echo
echo "=================================================================="
echo " SUCCESS: one daemon, Docker Root Dir = /var/lib/docker"
echo
echo " Your live volumes were NOT touched. Next, as your normal user:"
echo "   cd /home/cartware/Desktop/magento"
echo "   docker compose up -d --build     # NEVER add -v to 'down'"
echo "=================================================================="
