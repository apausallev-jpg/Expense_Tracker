<?php
// ============================================
// Database Configuration - PostgreSQL
// ============================================
// Update these values to match your environment

define('DB_HOST',     'localhost');
define('DB_PORT',     '5432');
define('DB_NAME',     'expense_tracker_db');
define('DB_USER',     'postgres');
define('DB_PASSWORD', '2006');

/**
 * Returns a PDO connection to the PostgreSQL database.
 * Uses a singleton pattern to avoid multiple connections.
 */
function getDB(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'pgsql:host=%s;port=%s;dbname=%s',
            DB_HOST, DB_PORT, DB_NAME
        );
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASSWORD, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
            exit;
        }
    }

    return $pdo;
}

// JSON response helper
function jsonResponse(mixed $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    echo json_encode($data);
    exit;
}
