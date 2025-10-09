<?php
/**
 * Laravel Serverless API Initialization untuk Vercel
 * File ini akan dipanggil oleh setiap API endpoint
 */

// Load environment configuration
require_once __DIR__ . '/_env.php';

// Set error reporting untuk production
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', '0');

// Set timezone
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'Asia/Jakarta');

// Load bootstrap untuk serverless environment
require_once __DIR__ . '/../bootstrap/serverless.php';

// Handle CORS preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Content-Type: application/json');
    http_response_code(200);
    echo json_encode(['status' => 'ok']);
    exit;
}

// Set CORS headers untuk semua requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Handle request body untuk JSON
if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'PUT') {
    $input = file_get_contents('php://input');
    if (!empty($input)) {
        $_POST = json_decode($input, true) ?? [];
    }
}
