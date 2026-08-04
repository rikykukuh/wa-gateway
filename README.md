# WA Gateway Laravel

Gateway WhatsApp multi-device berbasis Laravel 12 dan Baileys. Aplikasi menyediakan panel web, Swagger/OpenAPI, multi-user dengan approval admin, API key berbeda per client, isolasi device/pesan, riwayat pesan, pembatasan pengiriman, dan engine WhatsApp terpisah.

> Proyek menggunakan protokol WhatsApp Web tidak resmi. Gunakan hanya untuk penerima yang memberi izin dan jangan digunakan untuk spam. Perubahan pada WhatsApp dapat memengaruhi koneksi. Untuk kebutuhan resmi berskala besar, gunakan WhatsApp Business Platform.

## Fitur

- Banyak akun WhatsApp dalam satu gateway.
- Pairing QR dan penyimpanan sesi per device.
- Register user dengan aktivasi/nonaktif oleh admin.
- API key unik dan data terisolasi untuk setiap client.
- Kirim pesan serta simpan device, tujuan, isi, status, dan error.
- Limit pesan harian dan jeda minimum per client.
- Ganti password admin dan user.
- Dokumentasi Swagger di `/docs`.
- SQLite secara default; struktur database dibuat dari Laravel migrations.

## Kebutuhan sistem

- PHP 8.2 atau lebih baru beserta extension `pdo_sqlite`, `sqlite3`, `mbstring`, `openssl`, `curl`, dan `fileinfo`.
- Composer 2.
- Node.js 20 atau lebih baru dan npm.
- Git.
- Docker Desktop bersifat opsional.

## Instalasi otomatis

Clone repository lalu jalankan satu script. Script akan menginstal dependency Laravel, frontend, dan engine; membuat `.env`; menghasilkan secret acak; membuat SQLite; menjalankan seluruh migration; serta membangun aset frontend.

### Windows PowerShell

```powershell
git clone URL_REPOSITORY wa_gateway
cd wa_gateway
Set-ExecutionPolicy -Scope Process Bypass
.\scripts\setup.ps1 -AdminEmail "admin@example.com"
```

### Linux/macOS

```bash
git clone URL_REPOSITORY wa_gateway
cd wa_gateway
chmod +x scripts/*.sh
./scripts/setup.sh admin@example.com
```

Password admin acak akan ditampilkan satu kali setelah setup. Simpan password tersebut.

## Menjalankan aplikasi

Buka dua terminal dari folder proyek.

Terminal pertama—engine WhatsApp:

```bash
npm --prefix wa-engine start
```

Terminal kedua—Laravel:

```bash
php artisan serve
```

Buka:

- Panel: `http://127.0.0.1:8000`
- Register: `http://127.0.0.1:8000/register`
- Swagger: `http://127.0.0.1:8000/docs`

Untuk frontend development, jalankan `npm run dev`. Untuk production, gunakan `npm run build`.

## Menjalankan dengan Docker

Docker Compose pada proyek ini menjalankan engine WhatsApp. Laravel tetap dapat dijalankan melalui PHP/FPM atau `php artisan serve`.

```bash
docker compose up -d --build
php artisan serve
```

Port engine hanya di-bind ke `127.0.0.1:3100` agar tidak terbuka langsung ke internet.

## Database dan struktur tabel

Default database adalah SQLite di `database/database.sqlite`. Tidak perlu mengimpor file SQL: seluruh struktur tersimpan sebagai migration yang versioned di `database/migrations`.

Untuk menyiapkan atau memperbarui database saja:

```powershell
.\scripts\database.ps1
```

atau:

```bash
./scripts/database.sh
```

Perintah tersebut menjalankan `php artisan migrate --force` dan membuat tabel berikut:

| Tabel | Fungsi utama |
|---|---|
| `users` | Akun login client dan relasi ke API client |
| `api_clients` | API key hash/enkripsi, status, limit harian, dan jeda |
| `devices` | Device WhatsApp dan status koneksi |
| `messages` | Tujuan, isi, status, provider ID, error, dan waktu kirim |
| `app_settings` | Override konfigurasi aman, termasuk password admin |
| `sessions` | Session login web |
| `cache`, `cache_locks` | Cache dan rate limiter |
| `jobs`, `job_batches`, `failed_jobs` | Infrastruktur antrean Laravel |

Untuk MySQL, ubah bagian `DB_*` di `.env`, buat database kosong, kemudian jalankan `php artisan migrate --force`.

## Konfigurasi penting

```dotenv
WA_GATEWAY_API_KEY=master-key-admin
WA_ENGINE_URL=http://127.0.0.1:3100
WA_ENGINE_SECRET=secret-internal-engine
WA_ADMIN_EMAIL=admin@example.com
WA_ADMIN_PASSWORD_HASH=hash-bcrypt
```

Jangan commit `.env`, database SQLite, atau folder `wa-engine/sessions`. Semuanya sudah dimasukkan ke `.gitignore`.

## Alur akun

1. User mendaftar melalui `/register`.
2. Akun dibuat dalam status nonaktif.
3. Admin login dan mengaktifkannya melalui menu **API Clients**.
4. User login dan hanya dapat mengakses device serta pesan miliknya.
5. Admin dapat menonaktifkan akun atau merotasi API key kapan saja.

## Contoh API

Gunakan header `Authorization: Bearer API_KEY` atau `X-API-Key: API_KEY`.

```bash
curl -X POST http://127.0.0.1:8000/api/v1/devices \
  -H "Authorization: Bearer API_KEY" \
  -H "Content-Type: application/json" \
  -d '{"name":"Customer Service"}'
```

Setelah memperoleh `DEVICE_ID`:

```bash
curl -X POST http://127.0.0.1:8000/api/v1/devices/DEVICE_ID/connect \
  -H "Authorization: Bearer API_KEY"

curl http://127.0.0.1:8000/api/v1/devices/DEVICE_ID/qr \
  -H "Authorization: Bearer API_KEY"

curl -X POST http://127.0.0.1:8000/api/v1/devices/DEVICE_ID/messages \
  -H "Authorization: Bearer API_KEY" \
  -H "Content-Type: application/json" \
  -d '{"recipient":"628123456789","message":"Halo dari WA Gateway"}'
```

Nomor tujuan memakai kode negara tanpa tanda `+`.

## Endpoint utama

| Method | Endpoint | Fungsi |
|---|---|---|
| `GET/POST` | `/api/v1/clients` | Daftar/buat client (admin) |
| `PATCH` | `/api/v1/clients/{id}` | Ubah status dan limit client (admin) |
| `POST` | `/api/v1/clients/{id}/regenerate-key` | Rotasi API key (admin) |
| `GET/POST` | `/api/v1/devices` | Daftar/buat device |
| `POST` | `/api/v1/devices/{id}/connect` | Mulai pairing |
| `GET` | `/api/v1/devices/{id}/qr` | Ambil QR |
| `POST` | `/api/v1/devices/{id}/disconnect` | Putus sementara |
| `POST` | `/api/v1/devices/{id}/logout` | Logout dan hapus sesi lokal |
| `DELETE` | `/api/v1/devices/{id}` | Hapus device |
| `GET` | `/api/v1/messages` | Riwayat pesan sesuai pemilik key |
| `POST` | `/api/v1/devices/{id}/messages` | Kirim pesan |

## Scheduler pengiriman aman

Request pengiriman tidak lagi diteruskan langsung ke WhatsApp. Pesan disimpan
dengan status `queued`, lalu Laravel Scheduler mengirim maksimal satu pesan per
device pada setiap putaran dengan jeda tetap 30 detik. Batasnya tetap maksimal
20 pesan per jam dan 60 pesan per hari per device.

Jalankan scheduler lokal dengan:

```bash
php artisan schedule:work
```

Di server, pasang satu cron yang berjalan setiap menit:

```cron
* * * * * cd /path/ke/wa-gateway && php artisan schedule:run >> /dev/null 2>&1
```

Sesuaikan batas melalui `.env`:

```env
WA_SEND_DELAY_SECONDS=30
WA_SEND_HOURLY_LIMIT=20
WA_SEND_DAILY_LIMIT=60
WA_SEND_MAX_ATTEMPTS=3
```

Jika engine mengembalikan indikasi pembatasan seperti `463`, `restricted`, atau
`new chat limit`, seluruh antrean device tersebut diubah menjadi `paused` agar
sistem tidak terus mencoba. Throttling mengurangi lonjakan, tetapi tidak dapat
menjamin WhatsApp tidak membatasi akun, khususnya untuk chat baru otomatis.

## Testing dan pemeriksaan kode

```bash
php artisan test
vendor/bin/pint --test
npm run build
```

## Deployment production

1. Set document root ke folder `public`.
2. Gunakan HTTPS.
3. Isi `.env` production dan set `APP_DEBUG=false`.
4. Jalankan `composer install --no-dev --optimize-autoloader`.
5. Jalankan `npm ci && npm run build`.
6. Jalankan `php artisan migrate --force && php artisan optimize`.
7. Jalankan engine sebagai process manager/service dengan restart otomatis.
8. Backup database dan folder sesi engine secara rutin.

Jangan mengekspos port engine atau membagikan master API key kepada client.
