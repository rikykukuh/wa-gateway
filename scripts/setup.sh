#!/usr/bin/env bash
set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$PROJECT_ROOT"

for command in php composer npm; do
    command -v "$command" >/dev/null 2>&1 || { echo "Perintah '$command' tidak ditemukan."; exit 1; }
done

ADMIN_EMAIL="${1:-admin@example.com}"
[ -f .env ] || cp .env.example .env

composer install --no-interaction
npm ci
npm --prefix wa-engine ci
mkdir -p database
touch database/database.sqlite
php artisan key:generate --force

GATEWAY_KEY="$(php -r "echo 'wag_master_'.bin2hex(random_bytes(32));")"
ENGINE_SECRET="$(php -r "echo bin2hex(random_bytes(32));")"
ADMIN_PASSWORD="$(php -r "echo rtrim(strtr(base64_encode(random_bytes(18)), '+/', '-_'), '=');")"
export WA_SETUP_PASSWORD="$ADMIN_PASSWORD"
ADMIN_HASH="$(php -r "echo password_hash(getenv('WA_SETUP_PASSWORD'), PASSWORD_BCRYPT);")"
unset WA_SETUP_PASSWORD

SETUP_GATEWAY_KEY="$GATEWAY_KEY" SETUP_ENGINE_SECRET="$ENGINE_SECRET" SETUP_ADMIN_EMAIL="$ADMIN_EMAIL" SETUP_ADMIN_HASH="$ADMIN_HASH" php -r '
$path = ".env";
$env = file_get_contents($path);
$values = [
    "WA_GATEWAY_API_KEY" => getenv("SETUP_GATEWAY_KEY"),
    "WA_ENGINE_SECRET" => getenv("SETUP_ENGINE_SECRET"),
    "WA_ADMIN_EMAIL" => getenv("SETUP_ADMIN_EMAIL"),
    "WA_ADMIN_PASSWORD_HASH" => getenv("SETUP_ADMIN_HASH"),
];
foreach ($values as $key => $value) {
    $env = preg_replace("/^".preg_quote($key, "/")."=.*$/m", $key."=".$value, $env);
}
file_put_contents($path, $env);
'

php artisan migrate --force
npm run build
php artisan optimize:clear

echo
echo "Setup selesai."
echo "Admin email    : $ADMIN_EMAIL"
echo "Admin password : $ADMIN_PASSWORD"
echo "Simpan password sekarang; password ini hanya ditampilkan sekali."
echo "Jalankan engine : npm --prefix wa-engine start"
echo "Jalankan Laravel: php artisan serve"
