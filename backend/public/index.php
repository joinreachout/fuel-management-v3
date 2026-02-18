<?php
/**
 * API Entry Point
 * Handles all incoming HTTP requests and routes to appropriate controllers
 */

// Load configuration first (defines env() function)
require_once __DIR__ . '/../config/database.php';

// Set error reporting based on environment
error_reporting(E_ALL);
ini_set('display_errors', env('APP_DEBUG', '0'));

// Set JSON content type
header('Content-Type: application/json');

// Handle OPTIONS requests for CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Load core files
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

// Load Services
require_once __DIR__ . '/../src/Services/ForecastService.php';
require_once __DIR__ . '/../src/Services/AlertService.php';
require_once __DIR__ . '/../src/Services/ReportService.php';
require_once __DIR__ . '/../src/Services/CostAnalysisService.php';
require_once __DIR__ . '/../src/Services/TransferService.php';
require_once __DIR__ . '/../src/Services/RegionalComparisonService.php';
require_once __DIR__ . '/../src/Services/StationTanksService.php';
require_once __DIR__ . '/../src/Services/FuelStockService.php';
require_once __DIR__ . '/../src/Services/ProcurementAdvisorService.php';
require_once __DIR__ . '/../src/Services/ParametersService.php';
require_once __DIR__ . '/../src/Services/InfrastructureService.php';

// Load Controllers
require_once __DIR__ . '/../src/Controllers/StationController.php';
require_once __DIR__ . '/../src/Controllers/DepotController.php';
require_once __DIR__ . '/../src/Controllers/FuelTypeController.php';
require_once __DIR__ . '/../src/Controllers/SupplierController.php';
require_once __DIR__ . '/../src/Controllers/OrderController.php';
require_once __DIR__ . '/../src/Controllers/TransferController.php';
require_once __DIR__ . '/../src/Controllers/DashboardController.php';
require_once __DIR__ . '/../src/Controllers/ReportController.php';
require_once __DIR__ . '/../src/Controllers/CostAnalysisController.php';
require_once __DIR__ . '/../src/Controllers/RegionalComparisonController.php';
require_once __DIR__ . '/../src/Controllers/ProcurementAdvisorController.php';
require_once __DIR__ . '/../src/Controllers/ParametersController.php';
require_once __DIR__ . '/../src/Controllers/InfrastructureController.php';

use App\Core\Response;
use App\Controllers\StationController;
use App\Controllers\DepotController;
use App\Controllers\FuelTypeController;
use App\Controllers\SupplierController;
use App\Controllers\OrderController;
use App\Controllers\TransferController;
use App\Controllers\DashboardController;
use App\Controllers\ReportController;
use App\Controllers\CostAnalysisController;
use App\Controllers\RegionalComparisonController;
use App\Controllers\ProcurementAdvisorController;
use App\Controllers\ParametersController;
use App\Controllers\InfrastructureController;

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
    $dashboardController = new DashboardController();
    $reportController = new ReportController();
    $costAnalysisController = new CostAnalysisController();
    $regionalComparisonController = new RegionalComparisonController();
    $procurementAdvisorController = new ProcurementAdvisorController();
    $parametersController = new ParametersController();
    $infrastructureController = new InfrastructureController();

    // ==================== STATIONS ====================
    if ($requestMethod === 'GET' && $path === '/api/stations') {
        $stationController->index();

    } elseif ($requestMethod === 'GET' && preg_match('#^/api/stations/(\d+)$#', $path, $matches)) {
        $stationController->show((int) $matches[1]);

    } elseif ($requestMethod === 'GET' && preg_match('#^/api/stations/(\d+)/depots$#', $path, $matches)) {
        $stationController->depots((int) $matches[1]);

    } elseif ($requestMethod === 'GET' && preg_match('#^/api/stations/(\d+)/tanks$#', $path, $matches)) {
        $stationController->tanks((int) $matches[1]);

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

    } elseif ($requestMethod === 'GET' && preg_match('#^/api/fuel-types/(\d+)/stations$#', $path, $matches)) {
        $fuelTypeController->stations((int) $matches[1]);

    } elseif ($requestMethod === 'GET' && preg_match('#^/api/fuel-types/(\d+)/regions$#', $path, $matches)) {
        $fuelTypeController->regions((int) $matches[1]);

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
        $transferController->getTransfers();

    } elseif ($requestMethod === 'GET' && $path === '/api/transfers/pending') {
        $transferController->pending();

    } elseif ($requestMethod === 'GET' && $path === '/api/transfers/recent') {
        $transferController->recent();

    } elseif ($requestMethod === 'GET' && preg_match('#^/api/transfers/(\d+)$#', $path, $matches)) {
        $transferController->show((int) $matches[1]);

    // ==================== DASHBOARD ====================
    } elseif ($requestMethod === 'GET' && $path === '/api/dashboard/summary') {
        $dashboardController->summary();

    } elseif ($requestMethod === 'GET' && $path === '/api/dashboard/alerts') {
        $dashboardController->alerts();

    } elseif ($requestMethod === 'GET' && $path === '/api/dashboard/alerts/summary') {
        $dashboardController->alertSummary();

    } elseif ($requestMethod === 'GET' && $path === '/api/dashboard/critical-tanks') {
        $dashboardController->criticalTanks();

    } elseif ($requestMethod === 'GET' && $path === '/api/dashboard/forecast') {
        $dashboardController->forecast();

    // ==================== REPORTS ====================
    } elseif ($requestMethod === 'GET' && $path === '/api/reports/daily-stock') {
        $reportController->dailyStock();

    } elseif ($requestMethod === 'GET' && $path === '/api/reports/inventory-summary') {
        $reportController->inventorySummary();

    } elseif ($requestMethod === 'GET' && $path === '/api/reports/station-performance') {
        $reportController->stationPerformance();

    } elseif ($requestMethod === 'GET' && $path === '/api/reports/low-stock') {
        $reportController->lowStock();

    } elseif ($requestMethod === 'GET' && $path === '/api/reports/capacity-utilization') {
        $reportController->capacityUtilization();

    // ==================== COST ANALYSIS ====================
    } elseif ($requestMethod === 'GET' && $path === '/api/cost-analysis') {
        $costAnalysisController->getCostAnalysis();

    // ==================== REGIONAL COMPARISON ====================
    } elseif ($requestMethod === 'GET' && $path === '/api/regional-comparison') {
        $regionalComparisonController->getRegionalComparison();

    // ==================== PROCUREMENT ADVISOR ====================
    } elseif ($requestMethod === 'GET' && $path === '/api/procurement/upcoming-shortages') {
        $procurementAdvisorController->getUpcomingShortages();

    } elseif ($requestMethod === 'GET' && $path === '/api/procurement/summary') {
        $procurementAdvisorController->getSummary();

    } elseif ($requestMethod === 'GET' && $path === '/api/procurement/supplier-recommendations') {
        $procurementAdvisorController->getSupplierRecommendations();

    // ==================== PARAMETERS ====================
    } elseif ($requestMethod === 'GET' && $path === '/api/parameters/system') {
        $parametersController->getSystemParameters();

    } elseif ($requestMethod === 'PUT' && preg_match('#^/api/parameters/system/([a-z0-9_]+)$#', $path, $matches)) {
        $parametersController->updateSystemParameter($matches[1]);

    } elseif ($requestMethod === 'GET' && $path === '/api/parameters/fuel-types') {
        $parametersController->getFuelTypes();

    } elseif ($requestMethod === 'PUT' && preg_match('#^/api/parameters/fuel-types/(\d+)$#', $path, $matches)) {
        $parametersController->updateFuelType((int)$matches[1]);

    } elseif ($requestMethod === 'GET' && $path === '/api/parameters/sales-params') {
        $parametersController->getSalesParams();

    } elseif ($requestMethod === 'PUT' && preg_match('#^/api/parameters/sales-params/(\d+)$#', $path, $matches)) {
        $parametersController->updateSalesParam((int)$matches[1]);

    } elseif ($requestMethod === 'GET' && $path === '/api/parameters/stock-policies') {
        $parametersController->getStockPolicies();

    } elseif ($requestMethod === 'PUT' && preg_match('#^/api/parameters/stock-policies/(\d+)$#', $path, $matches)) {
        $parametersController->updateStockPolicy((int)$matches[1]);

    } elseif ($requestMethod === 'GET' && $path === '/api/parameters/supplier-offers') {
        $parametersController->getSupplierOffers();

    } elseif ($requestMethod === 'PUT' && preg_match('#^/api/parameters/supplier-offers/(\d+)$#', $path, $matches)) {
        $parametersController->updateSupplierOffer((int)$matches[1]);

    } elseif ($requestMethod === 'GET' && $path === '/api/parameters/depot-tanks') {
        $parametersController->getDepotTanks();

    // ==================== INFRASTRUCTURE ====================
    } elseif ($requestMethod === 'GET' && $path === '/api/infrastructure/hierarchy') {
        $infrastructureController->getHierarchy();

    } elseif ($requestMethod === 'PUT' && preg_match('#^/api/infrastructure/stations/(\d+)$#', $path, $matches)) {
        $infrastructureController->updateStation((int)$matches[1]);

    } elseif ($requestMethod === 'PUT' && preg_match('#^/api/infrastructure/depots/(\d+)$#', $path, $matches)) {
        $infrastructureController->updateDepot((int)$matches[1]);

    } elseif ($requestMethod === 'PUT' && preg_match('#^/api/infrastructure/tanks/(\d+)$#', $path, $matches)) {
        $infrastructureController->updateTank((int)$matches[1]);

    } elseif ($requestMethod === 'POST' && $path === '/api/infrastructure/tanks') {
        $infrastructureController->addTank();

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
