<?php
/**
 * Environment Configuration untuk Laravel Serverless
 * Mengatur environment variables untuk production deployment
 */

// Set default environment jika belum ada
if (!isset($_ENV['APP_ENV'])) {
    $_ENV['APP_ENV'] = 'production';
}

if (!isset($_ENV['APP_DEBUG'])) {
    $_ENV['APP_DEBUG'] = 'false';
}

if (!isset($_ENV['APP_URL'])) {
    $_ENV['APP_URL'] = 'https://klarifikasi-backend.vercel.app';
}

// Set timezone untuk Indonesia
if (!isset($_ENV['APP_TIMEZONE'])) {
    $_ENV['APP_TIMEZONE'] = 'Asia/Jakarta';
}

// Database configuration untuk Laravel Cloud MySQL
if (!isset($_ENV['DB_CONNECTION'])) {
    $_ENV['DB_CONNECTION'] = 'mysql';
}

// Laravel Cloud MySQL Database Configuration
if (!isset($_ENV['DB_HOST'])) {
    $_ENV['DB_HOST'] = 'laravel-cloud-mysql-host'; // Akan di-set di Laravel Cloud
}

if (!isset($_ENV['DB_PORT'])) {
    $_ENV['DB_PORT'] = '3306';
}

if (!isset($_ENV['DB_DATABASE'])) {
    $_ENV['DB_DATABASE'] = 'laravel_cloud_db'; // Akan di-set di Laravel Cloud
}

if (!isset($_ENV['DB_USERNAME'])) {
    $_ENV['DB_USERNAME'] = 'laravel_cloud_user'; // Akan di-set di Laravel Cloud
}

if (!isset($_ENV['DB_PASSWORD'])) {
    $_ENV['DB_PASSWORD'] = 'laravel_cloud_password'; // Akan di-set di Laravel Cloud
}

// Google Custom Search API (gunakan yang sudah ada)
if (!isset($_ENV['GOOGLE_CSE_KEY'])) {
    $_ENV['GOOGLE_CSE_KEY'] = 'AIzaSyAFOdoaMwgurnjfnhGKn5GFy6_m2HKiGtA';
}

if (!isset($_ENV['GOOGLE_CSE_CX'])) {
    $_ENV['GOOGLE_CSE_CX'] = '6242f5825dedb4b59';
}

// Gemini AI API Key
if (!isset($_ENV['GEMINI_API_KEY'])) {
    $_ENV['GEMINI_API_KEY'] = 'AIzaSyAvjaMWecq2PeHB8Vv4HBV8bBkKzzD9PmI';
}

// Session configuration untuk serverless
if (!isset($_ENV['SESSION_DRIVER'])) {
    $_ENV['SESSION_DRIVER'] = 'database';
}

if (!isset($_ENV['CACHE_DRIVER'])) {
    $_ENV['CACHE_DRIVER'] = 'database';
}

// Queue configuration
if (!isset($_ENV['QUEUE_CONNECTION'])) {
    $_ENV['QUEUE_CONNECTION'] = 'database';
}
