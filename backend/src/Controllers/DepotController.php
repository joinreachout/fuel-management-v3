<?php

namespace App\Controllers;

use App\Core\Response;
use App\Models\Depot;

/**
 * Depot Controller
 * Handles HTTP requests for depot resources
 */
class DepotController
{
    /**
     * GET /api/depots
     * Get all depots
     */
    public function index(): void
    {
        try {
            $depots = Depot::all();

            Response::json([
                'success' => true,
                'data' => $depots,
                'count' => count($depots)
            ]);
        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'error' => 'Failed to fetch depots: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/depots/{id}
     * Get single depot by ID
     */
    public function show(int $id): void
    {
        try {
            $depot = Depot::find($id);

            if (!$depot) {
                Response::json([
                    'success' => false,
                    'error' => 'Depot not found'
                ], 404);
                return;
            }

            Response::json([
                'success' => true,
                'data' => $depot
            ]);
        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'error' => 'Failed to fetch depot: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/depots/{id}/tanks
     * Get all tanks for a depot
     */
    public function tanks(int $id): void
    {
        try {
            $tanks = Depot::getTanks($id);

            Response::json([
                'success' => true,
                'data' => $tanks,
                'count' => count($tanks),
                'depot_id' => $id
            ]);
        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'error' => 'Failed to fetch tanks: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/depots/{id}/stock
     * Get total stock for a depot (grouped by fuel type)
     */
    public function stock(int $id): void
    {
        try {
            $stock = Depot::getTotalStock($id);

            Response::json([
                'success' => true,
                'data' => $stock,
                'depot_id' => $id
            ]);
        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'error' => 'Failed to fetch stock: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/depots/{id}/forecast
     * Get consumption forecast for a depot
     */
    public function forecast(int $id): void
    {
        try {
            $forecast = Depot::getConsumptionForecast($id);

            Response::json([
                'success' => true,
                'data' => $forecast,
                'depot_id' => $id
            ]);
        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'error' => 'Failed to fetch forecast: ' . $e->getMessage()
            ], 500);
        }
    }
}
