<?php
require_once __DIR__ . '/env.php';

// Database configuration
define('DB_HOST', getenv('DB_HOST'));
define('DB_USER', getenv('DB_USER'));
define('DB_PASS', getenv('DB_PASS'));
define('DB_NAME', getenv('DB_NAME'));

// Create connection
function getDBConnection() {
    static $conn = null;
    
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        // Check connection
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        // Set charset to utf8mb4
        $conn->set_charset("utf8mb4");
    }

    return $conn;
}

// Function to safely close the database connection
function closeDBConnection($conn) {
    if ($conn) {
        $conn->close();
    }
} 