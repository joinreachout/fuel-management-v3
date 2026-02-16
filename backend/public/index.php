<?php
/**
 * API Entry Point
 * Handles all incoming HTTP requests and routes to appropriate controllers
 */

// Set error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set JSON content type
header('Content-Type: application/json');

// Handle OPTIONS requests for CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Load configuration and core files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Core/Response.php';
require_once __DIR__ . '/../src/Core/Database.php';
require_once __DIR__ . '/../src/Models/Station.php';
require_once __DIR__ . '/../src/Models/Depot.php';
require_once __DIR__ . '/../src/Models/FuelType.php';
require_once __DIR__ . '/../src/Models/DepotTank.php';
require_once __DIR__ . '/../src/Controllers/StationController.php';
require_once __DIR__ . '/../src/Controllers/DepotController.php';

use App\Core\Response;
use App\Controllers\StationController;
use App\Controllers\DepotController;

// Simple router
try {
    // Get request URI and method
    $requestUri = $_SERVER['REQUEST_URI'];
    $requestMethod = $_SERVER['REQUEST_METHOD'];

    // Remove query string
    $path = parse_url($requestUri, PHP_URL_PATH);

    // Remove /rev3/backend/public or /backend/public prefix if present
    $path = preg_replace('#^/rev3/backend/public#', '', $path);
    $path = preg_replace('#^/backend/public#', '', $path);

    // Initialize controllers
    $stationController = new StationController();
    $depotController = new DepotController();

    // Route matching
    if ($requestMethod === 'GET' && $path === '/api/stations') {
        // GET /api/stations - Get all stations
        $stationController->index();

    } elseif ($requestMethod === 'GET' && preg_match('#^/api/stations/(\d+)$#', $path, $matches)) {
        // GET /api/stations/{id} - Get single station
        $stationId = (int) $matches[1];
        $stationController->show($stationId);

    } elseif ($requestMethod === 'GET' && preg_match('#^/api/stations/(\d+)/depots$#', $path, $matches)) {
        // GET /api/stations/{id}/depots - Get depots for station
        $stationId = (int) $matches[1];
        $stationController->depots($stationId);

    } elseif ($requestMethod === 'GET' && $path === '/api/depots') {
        // GET /api/depots - Get all depots
        $depotController->index();

    } elseif ($requestMethod === 'GET' && preg_match('#^/api/depots/(\d+)$#', $path, $matches)) {
        // GET /api/depots/{id} - Get single depot
        $depotId = (int) $matches[1];
        $depotController->show($depotId);

    } elseif ($requestMethod === 'GET' && preg_match('#^/api/depots/(\d+)/tanks$#', $path, $matches)) {
        // GET /api/depots/{id}/tanks - Get tanks for depot
        $depotId = (int) $matches[1];
        $depotController->tanks($depotId);

    } elseif ($requestMethod === 'GET' && preg_match('#^/api/depots/(\d+)/stock$#', $path, $matches)) {
        // GET /api/depots/{id}/stock - Get stock for depot
        $depotId = (int) $matches[1];
        $depotController->stock($depotId);

    } elseif ($requestMethod === 'GET' && preg_match('#^/api/depots/(\d+)/forecast$#', $path, $matches)) {
        // GET /api/depots/{id}/forecast - Get forecast for depot
        $depotId = (int) $matches[1];
        $depotController->forecast($depotId);

    } else {
        // 404 Not Found
        Response::json([
            'success' => false,
            'error' => 'Endpoint not found'
        ], 404);
    }

} catch (Exception $e) {
    // Handle any unexpected errors
    Response::json([
        'success' => false,
        'error' => $e->getMessage()
    ], 500);
}
