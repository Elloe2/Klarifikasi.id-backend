<?php

/**
 * Script untuk fix .env dengan konfigurasi MySQL yang benar
 */

$envFile = '.env';
$envContent = file_get_contents($envFile);

// Fix database configuration untuk MySQL
$envContent = preg_replace('/DB_CHARSET=utf8/', 'DB_CHARSET=utf8mb4', $envContent);
$envContent = preg_replace('/DB_SSLMODE=require/', '', $envContent);

// Tambahkan DB_COLLATION jika belum ada
if (strpos($envContent, 'DB_COLLATION') === false) {
    $envContent = str_replace('DB_CHARSET=utf8mb4', "DB_CHARSET=utf8mb4\nDB_COLLATION=utf8mb4_unicode_ci", $envContent);
}

// Hapus komentar PostgreSQL
$envContent = preg_replace('/# Database Configuration \(PostgreSQL\)/', '# Database Configuration (MySQL Laravel Cloud)', $envContent);
$envContent = preg_replace('/# PostgreSQL specific settings/', '# MySQL specific settings', $envContent);

// Write back to file
file_put_contents($envFile, $envContent);

echo "✅ .env file berhasil diperbaiki untuk MySQL\n";
echo "📋 Perubahan yang dilakukan:\n";
echo "   - DB_CHARSET: utf8 → utf8mb4\n";
echo "   - Menghapus DB_SSLMODE (PostgreSQL specific)\n";
echo "   - Menambahkan DB_COLLATION=utf8mb4_unicode_ci\n";
echo "   - Update komentar: PostgreSQL → MySQL Laravel Cloud\n";
echo "\n🚀 Sekarang bisa test koneksi dengan: php test_mysql.php\n";
