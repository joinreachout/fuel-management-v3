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

use App\Core\Response;
use App\Models\Station;

// Simple router
try {
    // Get request URI and method
    $requestUri = $_SERVER['REQUEST_URI'];
    $requestMethod = $_SERVER['REQUEST_METHOD'];

    // Remove query string
    $path = parse_url($requestUri, PHP_URL_PATH);

    // Remove /backend/public prefix if present
    $path = str_replace('/backend/public', '', $path);

    // Route matching
    if ($requestMethod === 'GET' && $path === '/api/stations') {
        // GET /api/stations - Get all stations
        $stations = Station::all();
        Response::success($stations);

    } elseif ($requestMethod === 'GET' && preg_match('#^/api/stations/(\d+)$#', $path, $matches)) {
        // GET /api/stations/{id} - Get single station
        $stationId = (int) $matches[1];
        $station = Station::find($stationId);

        if ($station) {
            Response::success($station);
        } else {
            Response::error('Station not found', 404);
        }

    } elseif ($requestMethod === 'GET' && preg_match('#^/api/stations/(\d+)/stock$#', $path, $matches)) {
        // GET /api/stations/{id}/stock - Get stock levels for station
        $stationId = (int) $matches[1];
        $station = Station::find($stationId);

        if (!$station) {
            Response::error('Station not found', 404);
            exit;
        }

        $stockLevels = Station::getStockLevels($stationId);
        Response::success([
            'station' => $station,
            'stock_levels' => $stockLevels
        ]);

    } elseif ($requestMethod === 'GET' && preg_match('#^/api/stations/(\d+)/depots$#', $path, $matches)) {
        // GET /api/stations/{id}/depots - Get depots for station
        $stationId = (int) $matches[1];
        $station = Station::find($stationId);

        if (!$station) {
            Response::error('Station not found', 404);
            exit;
        }

        $depots = Station::getDepots($stationId);
        Response::success([
            'station' => $station,
            'depots' => $depots
        ]);

    } elseif ($requestMethod === 'POST' && $path === '/api/stations') {
        // POST /api/stations - Create new station
        $input = json_decode(file_get_contents('php://input'), true);

        // Validation
        if (empty($input['name'])) {
            Response::error('Station name is required', 400);
            exit;
        }
        if (empty($input['region_id'])) {
            Response::error('Region ID is required', 400);
            exit;
        }

        $stationId = Station::create($input);
        $station = Station::find($stationId);

        Response::success($station, 201);

    } elseif ($requestMethod === 'PUT' && preg_match('#^/api/stations/(\d+)$#', $path, $matches)) {
        // PUT /api/stations/{id} - Update station
        $stationId = (int) $matches[1];
        $input = json_decode(file_get_contents('php://input'), true);

        $station = Station::find($stationId);
        if (!$station) {
            Response::error('Station not found', 404);
            exit;
        }

        Station::update($stationId, $input);
        $updatedStation = Station::find($stationId);

        Response::success($updatedStation);

    } else {
        // 404 Not Found
        Response::error('Endpoint not found', 404);
    }

} catch (Exception $e) {
    // Handle any unexpected errors
    Response::error($e->getMessage(), 500);
}
