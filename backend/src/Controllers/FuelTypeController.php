<?php

namespace App\Controllers;

use App\Core\Response;
use App\Models\FuelType;
use App\Services\FuelStockService;

/**
 * FuelType Controller
 * Handles HTTP requests for fuel type resources
 */
class FuelTypeController
{
    /**
     * GET /api/fuel-types
     * Get all fuel types
     */
    public function index(): void
    {
        try {
            $fuelTypes = FuelType::all();

            Response::json([
                'success' => true,
                'data' => $fuelTypes,
                'count' => count($fuelTypes)
            ]);
        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'error' => 'Failed to fetch fuel types: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/fuel-types/{id}
     * Get single fuel type by ID
     */
    public function show(int $id): void
    {
        try {
            $fuelType = FuelType::find($id);

            if (!$fuelType) {
                Response::json([
                    'success' => false,
                    'error' => 'Fuel type not found'
                ], 404);
                return;
            }

            Response::json([
                'success' => true,
                'data' => $fuelType
            ]);
        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'error' => 'Failed to fetch fuel type: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST /api/fuel-types
     * Create a new fuel type
     */
    public function create(): void
    {
        try {
            $body = json_decode(file_get_contents('php://input'), true) ?? [];
            $name    = trim($body['name']    ?? '');
            $code    = trim($body['code']    ?? '');
            $density = isset($body['density']) ? (float) $body['density'] : 0.0;

            if ($name === '') {
                Response::json(['success' => false, 'error' => 'Name is required'], 400);
                return;
            }
            if ($code === '') {
                Response::json(['success' => false, 'error' => 'Code is required'], 400);
                return;
            }
            if ($density <= 0) {
                Response::json(['success' => false, 'error' => 'Density must be greater than 0'], 400);
                return;
            }

            $id = FuelType::create($name, $code, $density);

            Response::json([
                'success' => true,
                'data'    => ['id' => $id, 'name' => $name, 'code' => $code, 'density' => $density],
            ], 201);
        } catch (\Exception $e) {
            Response::json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/fuel-types/{id}/stock
     * Get total stock for a fuel type across all depots
     */
    public function stock(int $id): void
    {
        try {
            $stock = FuelType::getTotalStock($id);

            Response::json([
                'success' => true,
                'data' => $stock,
                'fuel_type_id' => $id
            ]);
        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'error' => 'Failed to fetch fuel type stock: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/fuel-types/{id}/stations
     * Get stock distribution for a fuel type across all stations
     */
    public function stations(int $id): void
    {
        try {
            $result = FuelStockService::getStockByStations($id);

            if (!$result['success']) {
                Response::json([
                    'success' => false,
                    'error' => $result['message']
                ], 500);
                return;
            }

            Response::json($result);
        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'error' => 'Failed to fetch fuel stock by stations: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/fuel-types/{id}/regions
     * Get stock distribution for a fuel type across all regions
     */
    public function regions(int $id): void
    {
        try {
            $result = FuelStockService::getStockByRegions($id);

            if (!$result['success']) {
                Response::json([
                    'success' => false,
                    'error' => $result['message']
                ], 500);
                return;
            }

            Response::json($result);
        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'error' => 'Failed to fetch fuel stock by regions: ' . $e->getMessage()
            ], 500);
        }
    }
}
