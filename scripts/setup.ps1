param(
    [string]$AdminEmail = "admin@example.com"
)

$ErrorActionPreference = "Stop"
$ProjectRoot = (Resolve-Path (Join-Path $PSScriptRoot "..")).Path
Set-Location $ProjectRoot

foreach ($command in @("php", "composer", "npm")) {
    if (-not (Get-Command $command -ErrorAction SilentlyContinue)) {
        throw "Perintah '$command' tidak ditemukan. Instal dependency yang tercantum di README terlebih dahulu."
    }
}

if (-not (Test-Path ".env")) {
    Copy-Item ".env.example" ".env"
}

composer install --no-interaction
npm ci
npm --prefix wa-engine ci

if (-not (Test-Path "database/database.sqlite")) {
    New-Item -ItemType File -Path "database/database.sqlite" | Out-Null
}

php artisan key:generate --force

$GatewayKey = php -r "echo 'wag_master_'.bin2hex(random_bytes(32));"
$EngineSecret = php -r "echo bin2hex(random_bytes(32));"
$GeneratedPassword = php -r "echo rtrim(strtr(base64_encode(random_bytes(18)), '+/', '-_'), '=');"
$env:WA_SETUP_PASSWORD = $GeneratedPassword
$AdminHash = php -r "echo password_hash(getenv('WA_SETUP_PASSWORD'), PASSWORD_BCRYPT);"
Remove-Item Env:WA_SETUP_PASSWORD

$Environment = Get-Content -Raw ".env"
$Environment = $Environment -replace '(?m)^WA_GATEWAY_API_KEY=.*$', "WA_GATEWAY_API_KEY=$GatewayKey"
$Environment = $Environment -replace '(?m)^WA_ENGINE_SECRET=.*$', "WA_ENGINE_SECRET=$EngineSecret"
$Environment = $Environment -replace '(?m)^WA_ADMIN_EMAIL=.*$', "WA_ADMIN_EMAIL=$AdminEmail"
$Environment = $Environment -replace '(?m)^WA_ADMIN_PASSWORD_HASH=.*$', "WA_ADMIN_PASSWORD_HASH=$AdminHash"
Set-Content -Path ".env" -Value $Environment -NoNewline

php artisan migrate --force
npm run build
php artisan optimize:clear

Write-Host ""
Write-Host "Setup selesai." -ForegroundColor Green
Write-Host "Admin email    : $AdminEmail"
Write-Host "Admin password : $GeneratedPassword"
Write-Host "Simpan password sekarang; password ini hanya ditampilkan sekali." -ForegroundColor Yellow
Write-Host "Jalankan engine: npm --prefix wa-engine start"
Write-Host "Jalankan Laravel: php artisan serve"
