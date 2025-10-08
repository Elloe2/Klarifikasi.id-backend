<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing Laravel API...\n";

try {
    $response = Illuminate\Support\Facades\Http::post('http://localhost:8000/api/auth/login', [
        'email' => 'test@example.com',
        'password' => 'password123'
    ]);

    echo "Status Code: " . $response->status() . "\n";
    echo "Response: " . $response->body() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
