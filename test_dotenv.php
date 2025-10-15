<?php
require_once __DIR__ . '/vendor/autoload.php';

echo "Testing dotenv loading...\n";

try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
    echo "Dotenv loaded successfully\n";
    echo "APP_ENV: " . (getenv('APP_ENV') ?: 'not set') . "\n";
} catch (Exception $e) {
    echo "Dotenv loading failed: " . $e->getMessage() . "\n";
    echo "File exists: " . (file_exists(__DIR__ . '/.env') ? 'YES' : 'NO') . "\n";
    echo "File readable: " . (is_readable(__DIR__ . '/.env') ? 'YES' : 'NO') . "\n";
}
