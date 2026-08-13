#!/usr/bin/env bash

set -Eeuo pipefail

APP_PATH="${HOME}/public_html/spp"
DEPLOY_MARKER="${APP_PATH}/storage/app/.cpanel-deployed-sha"
DEPENDENCY_MARKER="${APP_PATH}/storage/app/.composer-lock-sha256"

cd "${APP_PATH}"

if [[ ! -f .env ]]; then
    echo "ERROR: ${APP_PATH}/.env belum tersedia. Buat melalui File Manager cPanel sebelum deployment."
    exit 1
fi

CURRENT_SHA="$(git rev-parse HEAD)"
if [[ -f "${DEPLOY_MARKER}" ]] && [[ "$(<"${DEPLOY_MARKER}")" == "${CURRENT_SHA}" ]]; then
    echo "Commit ${CURRENT_SHA} sudah pernah dideploy."
    exit 0
fi

PHP_BIN=""
for candidate in \
    /usr/local/bin/ea-php82 \
    /opt/cpanel/ea-php82/root/usr/bin/php \
    /usr/local/bin/php \
    /usr/bin/php
do
    if [[ -x "${candidate}" ]]; then
        PHP_BIN="${candidate}"
        break
    fi
done

if [[ -z "${PHP_BIN}" ]]; then
    echo "ERROR: PHP CLI 8.2 tidak ditemukan. Hubungi penyedia hosting untuk path PHP CLI."
    exit 1
fi

COMPOSER_BIN=""
for candidate in \
    /usr/local/bin/composer \
    /opt/cpanel/composer/bin/composer \
    /usr/bin/composer
do
    if [[ -f "${candidate}" || -x "${candidate}" ]]; then
        COMPOSER_BIN="${candidate}"
        break
    fi
done

mkdir -p \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

chmod -R ug+rwX storage bootstrap/cache

LOCK_SHA=""
if [[ -f composer.lock ]]; then
    if command -v sha256sum >/dev/null 2>&1; then
        LOCK_SHA="$(sha256sum composer.lock | awk '{print $1}')"
    elif command -v shasum >/dev/null 2>&1; then
        LOCK_SHA="$(shasum -a 256 composer.lock | awk '{print $1}')"
    fi
fi

if [[ -n "${COMPOSER_BIN}" ]]; then
    "${PHP_BIN}" "${COMPOSER_BIN}" install \
        --no-dev \
        --optimize-autoloader \
        --no-interaction \
        --prefer-dist

    [[ -n "${LOCK_SHA}" ]] && printf '%s\n' "${LOCK_SHA}" > "${DEPENDENCY_MARKER}"
elif [[ -f vendor/autoload.php ]]; then
    if [[ -n "${LOCK_SHA}" ]] && [[ -f "${DEPENDENCY_MARKER}" ]] \
        && [[ "$(<"${DEPENDENCY_MARKER}")" != "${LOCK_SHA}" ]]; then
        echo "ERROR: composer.lock berubah, tetapi Composer tidak tersedia di hosting."
        echo "Unggah ulang vendor production yang sesuai sebelum deployment dilanjutkan."
        exit 1
    fi

    echo "PERINGATAN: Composer tidak tersedia; menggunakan folder vendor production yang sudah diunggah."
    [[ -n "${LOCK_SHA}" ]] && printf '%s\n' "${LOCK_SHA}" > "${DEPENDENCY_MARKER}"
else
    echo "ERROR: Composer dan vendor/autoload.php tidak tersedia."
    exit 1
fi

if command -v npm >/dev/null 2>&1; then
    npm ci --no-audit --no-fund
    npm run build
else
    echo "PERINGATAN: Node.js/NPM tidak tersedia; build Vite dilewati."
fi

"${PHP_BIN}" artisan optimize:clear

if ! grep -qE '^APP_KEY=.+$' .env; then
    "${PHP_BIN}" artisan key:generate --force
fi

"${PHP_BIN}" artisan package:discover --ansi
"${PHP_BIN}" artisan migrate --force

KOPERASI_WORKBOOK="${APP_PATH}/barang2 dikoperasi. stok tgl 30.7.26.xlsx"
if [[ -f "${KOPERASI_WORKBOOK}" ]]; then
    "${PHP_BIN}" artisan koperasi:import-excel "${KOPERASI_WORKBOOK}"
fi

if [[ ! -e public/storage ]] && [[ ! -L public/storage ]]; then
    /bin/ln -s "${APP_PATH}/storage/app/public" "${APP_PATH}/public/storage"
fi

"${PHP_BIN}" artisan optimize

printf '%s\n' "${CURRENT_SHA}" > "${DEPLOY_MARKER}"
echo "Deployment commit ${CURRENT_SHA} selesai."
