<?php
/**
 * Temporary migration runner for migration 007
 * DELETE THIS FILE after use
 */

// Simple security token
$token = $_GET['token'] ?? '';
if ($token !== 'fuel007migrate') {
    http_response_code(403);
    die(json_encode(['error' => 'Forbidden']));
}

header('Content-Type: application/json');

require_once __DIR__ . '/backend/config/database.php';
require_once __DIR__ . '/backend/src/Core/Database.php';

use App\Core\Database;

$results = [];

try {
    // Check if columns already exist
    $cols = Database::fetchAll("SHOW COLUMNS FROM orders LIKE 'cancelled_reason'");
    if (!empty($cols)) {
        echo json_encode(['success' => true, 'message' => 'Migration already applied — cancelled_reason column exists.']);
        exit;
    }

    // Run migration
    Database::execute("
        ALTER TABLE orders
          ADD COLUMN cancelled_reason VARCHAR(500) NULL AFTER notes,
          ADD COLUMN cancelled_at DATETIME NULL AFTER cancelled_reason
    ");

    // Verify
    $verify = Database::fetchAll("SHOW COLUMNS FROM orders WHERE Field IN ('cancelled_reason', 'cancelled_at')");

    echo json_encode([
        'success' => true,
        'message' => 'Migration 007 applied successfully',
        'columns_added' => array_column($verify, 'Field')
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
