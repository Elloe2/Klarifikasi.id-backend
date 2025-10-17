<?php

/**
 * Script untuk fix .env dengan menghapus DB_URL yang salah
 */

$envFile = '.env';
$envContent = file_get_contents($envFile);

// Hapus DB_URL yang salah (PostgreSQL URL)
$envContent = preg_replace('/DB_URL=.*\n/', '', $envContent);

// Tambahkan DB_URL yang benar untuk MySQL
$envContent .= "\n# MySQL specific settings\n";
$envContent .= "DB_URL=mysql://vtx2ltv8hbmwy7ag:aFHjKbQYJP1QTV1RyqNl@db-a01ccb22-a895-4e6c-83e0-715019c9f1b7.ap-southeast-1.db.laravel.cloud:3306/main\n";

// Write back to file
file_put_contents($envFile, $envContent);

echo "✅ .env file berhasil diperbaiki\n";
echo "📋 Perubahan yang dilakukan:\n";
echo "   - Menghapus DB_URL PostgreSQL yang salah\n";
echo "   - Menambahkan DB_URL MySQL yang benar\n";
echo "\n🚀 Sekarang bisa test koneksi dengan: php test_mysql.php\n";
