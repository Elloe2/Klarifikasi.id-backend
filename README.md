# Klarifikasi.id-backend

Backend Laravel untuk aplikasi Klarifikasi.id. Menyediakan REST API pencarian hoaks dan pencatatan riwayat.

## Struktur Direktori
- `app/`
  - `Http/Controllers/`: `SearchController` berisi logic pencarian & histori.
  - `Models/`: `SearchHistory` merepresentasikan tabel riwayat.
  - `Providers/`: konfigurasi service Laravel.
- `config/`: file konfigurasi Laravel (app, database, services, dll.).
- `database/`
  - `migrations/`: definisi skema (mis. `2025_10_04_000000_create_search_histories_table.php`).
  - `seeders/`: stub untuk pengisian data awal.
- `routes/`: definisi routing (`api.php` untuk endpoint REST, `web.php` untuk routing web).
- `resources/`: view, asset, dan file bahasa bila dibutuhkan.
- `public/`: entry point HTTP (`index.php`) dan asset publik.
- `storage/`: cache, log, dan file terunggah.
- `tests/`: test feature/unit Laravel.

## Fitur Utama
- Endpoint `POST /api/search` meneruskan query ke Google Custom Search dan menyimpan hasil utama.
- Endpoint `GET /api/history` mengembalikan riwayat pencarian dengan pagination.