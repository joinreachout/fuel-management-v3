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

// Load .env manually — no dependency on backend classes
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) continue;
        [$k, $v] = explode('=', $line, 2);
        putenv(trim($k) . '=' . trim($v));
    }
}

try {
    $dsn = sprintf(
        "mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4",
        getenv('DB_HOST') ?: 'localhost',
        getenv('DB_PORT') ?: '3306',
        getenv('DB_NAME')
    );
    $pdo = new PDO($dsn, getenv('DB_USER'), getenv('DB_PASSWORD'), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    // Check if columns already exist
    $cols = $pdo->query("SHOW COLUMNS FROM orders LIKE 'cancelled_reason'")->fetchAll();
    if (!empty($cols)) {
        echo json_encode([
            'success' => true,
            'message' => 'Migration already applied — cancelled_reason column already exists.'
        ]);
        exit;
    }

    // Run migration 007
    $pdo->exec("
        ALTER TABLE orders
          ADD COLUMN cancelled_reason VARCHAR(500) NULL AFTER notes,
          ADD COLUMN cancelled_at DATETIME NULL AFTER cancelled_reason
    ");

    // Verify
    $added = $pdo->query("SHOW COLUMNS FROM orders WHERE Field IN ('cancelled_reason', 'cancelled_at')")
                 ->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode([
        'success' => true,
        'message' => 'Migration 007 applied successfully',
        'columns_added' => $added
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
