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
