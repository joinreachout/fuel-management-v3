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

// Check if system_parameters exists
try {
    $sp = Database::fetchAll("SHOW COLUMNS FROM system_parameters");
    $result['system_parameters_columns'] = array_column($sp, 'Field');
    $result['system_parameters_exists'] = true;
    $result['system_parameters_count'] = Database::fetchAll("SELECT COUNT(*) as c FROM system_parameters")[0]['c'];
} catch (Exception $e) {
    $result['system_parameters_exists'] = false;
    $result['system_parameters_error'] = $e->getMessage();
}

echo json_encode($result, JSON_PRETTY_PRINT);
