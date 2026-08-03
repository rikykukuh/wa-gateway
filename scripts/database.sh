#!/usr/bin/env bash
set -euo pipefail
PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$PROJECT_ROOT"
mkdir -p database
touch database/database.sqlite
php artisan migrate --force
echo "Database dan seluruh tabel berhasil disiapkan."
