# Klarifikasi.id-backend

Backend Laravel untuk aplikasi Klarifikasi.id yang menyediakan endpoint pencarian hoaks.

## Fitur

- Pencarian hoaks melalui layanan Google Custom Search.
- Penyimpanan riwayat pencarian pada basis data.

## Setup Lokal

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

Pastikan variabel lingkungan untuk API key disesuaikan di file `.env`.
