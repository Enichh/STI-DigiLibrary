<?php
// Load Composer autoloader (required for dependencies like vlucas/phpdotenv)
require_once __DIR__ . '/../../vendor/autoload.php';

// Load environment variables from .env file in the parent directory
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Get a PDO instance connected to the database using environment settings
function getPDO()
{
    // Read database settings from environment variables
    $host = $_ENV['DB_HOST'];
    $port = $_ENV['DB_PORT'];
    $db   = $_ENV['DB_NAME'];
    $user = $_ENV['DB_USER'];
    $pass = $_ENV['DB_PASS'];
    $charset = "utf8mb4";

    // Build DSN string for MySQL connection
    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";

    // Set recommended PDO options for error and fetch handling
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,         // Throw exceptions on error
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,               // Fetch results as associative arrays
        PDO::ATTR_EMULATE_PREPARES   => false,                          // Use real prepared statements
    ];

    // Create and return PDO connection object
    return new PDO($dsn, $user, $pass, $options);
}
