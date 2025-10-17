<?php

/**
 * Script untuk fix database host dengan credentials yang benar
 */

$envFile = '.env';
$envContent = file_get_contents($envFile);

// Fix database host dengan .public
$envContent = preg_replace('/DB_HOST=.*/', 'DB_HOST=db-a01ccb22-a895-4e6c-83e0-715019c9f1b7.ap-southeast-1.public.db.laravel.cloud', $envContent);

// Update DB_URL dengan host yang benar
$envContent = preg_replace('/DB_URL=.*/', 'DB_URL=mysql://vtx2ltv8hbmwy7ag:aFHjKbQYJP1QTV1RyqNl@db-a01ccb22-a895-4e6c-83e0-715019c9f1b7.ap-southeast-1.public.db.laravel.cloud:3306/main', $envContent);

// Write back to file
file_put_contents($envFile, $envContent);

echo "✅ .env file berhasil diperbaiki dengan host yang benar\n";
echo "📋 Perubahan yang dilakukan:\n";
echo "   - DB_HOST: Ditambahkan .public di host\n";
echo "   - DB_URL: Diupdate dengan host yang benar\n";
echo "\n🚀 Sekarang bisa test koneksi dengan: php test_mysql.php\n";
