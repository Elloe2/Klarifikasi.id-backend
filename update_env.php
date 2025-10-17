<?php

/**
 * Script untuk update .env dengan credentials Laravel Cloud MySQL
 */

$envFile = '.env';
$envContent = file_get_contents($envFile);

// Update database configuration
$envContent = preg_replace('/DB_CONNECTION=.*/', 'DB_CONNECTION=mysql', $envContent);
$envContent = preg_replace('/DB_HOST=.*/', 'DB_HOST=db-a01ccb22-a895-4e6c-83e0-715019c9f1b7.ap-southeast-1.db.laravel.cloud', $envContent);
$envContent = preg_replace('/DB_PORT=.*/', 'DB_PORT=3306', $envContent);
$envContent = preg_replace('/DB_DATABASE=.*/', 'DB_DATABASE=main', $envContent);
$envContent = preg_replace('/DB_USERNAME=.*/', 'DB_USERNAME=vtx2ltv8hbmwy7ag', $envContent);
$envContent = preg_replace('/DB_PASSWORD=.*/', 'DB_PASSWORD=aFHjKbQYJP1QTV1RyqNl', $envContent);

// Update APP_KEY
$envContent = preg_replace('/APP_KEY=.*/', 'APP_KEY=base64:AL0sxBe3jHY7Gxv/TlrHNtidF8u2q7qGcSsfukP9l8E=', $envContent);

// Update log configuration
$envContent = preg_replace('/LOG_CHANNEL=.*/', 'LOG_CHANNEL=laravel-cloud-socket', $envContent);

// Add new configurations if not exists
if (strpos($envContent, 'LOG_STDERR_FORMATTER') === false) {
    $envContent .= "\nLOG_STDERR_FORMATTER=Monolog\\Formatter\\JsonFormatter";
}

if (strpos($envContent, 'SCHEDULE_CACHE_DRIVER') === false) {
    $envContent .= "\nSCHEDULE_CACHE_DRIVER=database";
}

// Update session driver
$envContent = preg_replace('/SESSION_DRIVER=.*/', 'SESSION_DRIVER=cookie', $envContent);

// Update cache store
$envContent = preg_replace('/CACHE_STORE=.*/', 'CACHE_STORE=database', $envContent);

// Write back to file
file_put_contents($envFile, $envContent);

echo "✅ .env file berhasil diupdate dengan credentials Laravel Cloud MySQL\n";
echo "📋 Perubahan yang dilakukan:\n";
echo "   - DB_CONNECTION: pgsql → mysql\n";
echo "   - DB_HOST: Neon → Laravel Cloud MySQL\n";
echo "   - DB_DATABASE: neondb → main\n";
echo "   - APP_KEY: Updated dengan key Laravel Cloud\n";
echo "   - LOG_CHANNEL: Updated untuk Laravel Cloud\n";
echo "   - SESSION_DRIVER: database → cookie\n";
echo "\n🚀 Sekarang bisa test koneksi dengan: php test_mysql.php\n";
