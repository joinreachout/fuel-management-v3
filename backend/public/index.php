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

// Load Models
require_once __DIR__ . '/../src/Models/Station.php';
require_once __DIR__ . '/../src/Models/Depot.php';
require_once __DIR__ . '/../src/Models/FuelType.php';
require_once __DIR__ . '/../src/Models/DepotTank.php';
require_once __DIR__ . '/../src/Models/Supplier.php';
require_once __DIR__ . '/../src/Models/Order.php';
require_once __DIR__ . '/../src/Models/Transfer.php';

// Load Controllers
require_once __DIR__ . '/../src/Controllers/StationController.php';
require_once __DIR__ . '/../src/Controllers/DepotController.php';
require_once __DIR__ . '/../src/Controllers/FuelTypeController.php';
require_once __DIR__ . '/../src/Controllers/SupplierController.php';
require_once __DIR__ . '/../src/Controllers/OrderController.php';
require_once __DIR__ . '/../src/Controllers/TransferController.php';

use App\Core\Response;
use App\Controllers\StationController;
use App\Controllers\DepotController;
use App\Controllers\FuelTypeController;
use App\Controllers\SupplierController;
use App\Controllers\OrderController;
use App\Controllers\TransferController;

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
    $fuelTypeController = new FuelTypeController();
    $supplierController = new SupplierController();
    $orderController = new OrderController();
    $transferController = new TransferController();

    // ==================== STATIONS ====================
    if ($requestMethod === 'GET' && $path === '/api/stations') {
        $stationController->index();

    } elseif ($requestMethod === 'GET' && preg_match('#^/api/stations/(\d+)$#', $path, $matches)) {
        $stationController->show((int) $matches[1]);

    } elseif ($requestMethod === 'GET' && preg_match('#^/api/stations/(\d+)/depots$#', $path, $matches)) {
        $stationController->depots((int) $matches[1]);

    // ==================== DEPOTS ====================
    } elseif ($requestMethod === 'GET' && $path === '/api/depots') {
        $depotController->index();

    } elseif ($requestMethod === 'GET' && preg_match('#^/api/depots/(\d+)$#', $path, $matches)) {
        $depotController->show((int) $matches[1]);

    } elseif ($requestMethod === 'GET' && preg_match('#^/api/depots/(\d+)/tanks$#', $path, $matches)) {
        $depotController->tanks((int) $matches[1]);

    } elseif ($requestMethod === 'GET' && preg_match('#^/api/depots/(\d+)/stock$#', $path, $matches)) {
        $depotController->stock((int) $matches[1]);

    } elseif ($requestMethod === 'GET' && preg_match('#^/api/depots/(\d+)/forecast$#', $path, $matches)) {
        $depotController->forecast((int) $matches[1]);

    // ==================== FUEL TYPES ====================
    } elseif ($requestMethod === 'GET' && $path === '/api/fuel-types') {
        $fuelTypeController->index();

    } elseif ($requestMethod === 'GET' && preg_match('#^/api/fuel-types/(\d+)$#', $path, $matches)) {
        $fuelTypeController->show((int) $matches[1]);

    } elseif ($requestMethod === 'GET' && preg_match('#^/api/fuel-types/(\d+)/stock$#', $path, $matches)) {
        $fuelTypeController->stock((int) $matches[1]);

    // ==================== SUPPLIERS ====================
    } elseif ($requestMethod === 'GET' && $path === '/api/suppliers') {
        $supplierController->index();

    } elseif ($requestMethod === 'GET' && $path === '/api/suppliers/active') {
        $supplierController->active();

    } elseif ($requestMethod === 'GET' && preg_match('#^/api/suppliers/(\d+)$#', $path, $matches)) {
        $supplierController->show((int) $matches[1]);

    } elseif ($requestMethod === 'GET' && preg_match('#^/api/suppliers/(\d+)/orders$#', $path, $matches)) {
        $supplierController->orders((int) $matches[1]);

    } elseif ($requestMethod === 'GET' && preg_match('#^/api/suppliers/(\d+)/stats$#', $path, $matches)) {
        $supplierController->stats((int) $matches[1]);

    // ==================== ORDERS ====================
    } elseif ($requestMethod === 'GET' && $path === '/api/orders') {
        $orderController->index();

    } elseif ($requestMethod === 'GET' && $path === '/api/orders/pending') {
        $orderController->pending();

    } elseif ($requestMethod === 'GET' && $path === '/api/orders/summary') {
        $orderController->summary();

    } elseif ($requestMethod === 'GET' && $path === '/api/orders/recent') {
        $orderController->recent();

    } elseif ($requestMethod === 'GET' && preg_match('#^/api/orders/(\d+)$#', $path, $matches)) {
        $orderController->show((int) $matches[1]);

    // ==================== TRANSFERS ====================
    } elseif ($requestMethod === 'GET' && $path === '/api/transfers') {
        $transferController->index();

    } elseif ($requestMethod === 'GET' && $path === '/api/transfers/pending') {
        $transferController->pending();

    } elseif ($requestMethod === 'GET' && $path === '/api/transfers/recent') {
        $transferController->recent();

    } elseif ($requestMethod === 'GET' && preg_match('#^/api/transfers/(\d+)$#', $path, $matches)) {
        $transferController->show((int) $matches[1]);

    } else {
        // 404 Not Found
        Response::json([
            'success' => false,
            'error' => 'Endpoint not found',
            'path' => $path,
            'method' => $requestMethod
        ], 404);
    }

} catch (Exception $e) {
    // Handle any unexpected errors
    Response::json([
        'success' => false,
        'error' => $e->getMessage()
    ], 500);
}
