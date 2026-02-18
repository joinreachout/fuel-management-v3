<?php
/**
 * Temporary schema diagnostic — DELETE AFTER USE
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Core/Database.php';

use App\Core\Database;

header('Content-Type: application/json');

$result = [];

// Check fuel_types columns
$cols = Database::fetchAll("SHOW COLUMNS FROM fuel_types");
$result['fuel_types_columns'] = array_column($cols, 'Field');

// Check system_parameters structure and content
try {
    $sp = Database::fetchAll("SHOW COLUMNS FROM system_parameters");
    $result['system_parameters_columns'] = array_column($sp, 'Field');
    $result['system_parameters_rows'] = Database::fetchAll("SELECT * FROM system_parameters ORDER BY parameter_name");
} catch (Exception $e) {
    $result['system_parameters_error'] = $e->getMessage();
}

echo json_encode($result, JSON_PRETTY_PRINT);
