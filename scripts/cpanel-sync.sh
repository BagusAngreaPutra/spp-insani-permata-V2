#!/usr/bin/env bash

set -Eeuo pipefail

REPOSITORY_ROOT="${HOME}/public_html/spp"
LOCK_FILE="${HOME}/.spp-cpanel-sync.lock"

if command -v flock >/dev/null 2>&1; then
    exec 9>"${LOCK_FILE}"
    flock -n 9 || exit 0
fi

if [[ -x /usr/local/cpanel/bin/uapi ]]; then
    UAPI=/usr/local/cpanel/bin/uapi
elif [[ -x /usr/bin/uapi ]]; then
    UAPI=/usr/bin/uapi
else
    echo "ERROR: perintah UAPI cPanel tidak ditemukan."
    exit 1
fi

echo "[$(date '+%Y-%m-%d %H:%M:%S')] Memeriksa pembaruan branch main..."

BEFORE_SHA="$(git -C "${REPOSITORY_ROOT}" rev-parse HEAD)"

"${UAPI}" --output=json VersionControl update \
    repository_root="${REPOSITORY_ROOT}" \
    branch=main

# Pembaruan Git dikerjakan melalui antrean tugas cPanel. Tunggu hingga HEAD
# berubah. Jika remote memang belum berubah, deployment akan dilewati oleh
# marker commit di cpanel-deploy.sh.
for _attempt in {1..12}; do
    sleep 5
    AFTER_SHA="$(git -C "${REPOSITORY_ROOT}" rev-parse HEAD)"
    [[ "${AFTER_SHA}" != "${BEFORE_SHA}" ]] && break
done

/bin/bash "${REPOSITORY_ROOT}/scripts/cpanel-deploy.sh"

echo "[$(date '+%Y-%m-%d %H:%M:%S')] Sinkronisasi selesai."
