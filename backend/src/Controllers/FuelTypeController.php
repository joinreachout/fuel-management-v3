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

    /**
     * GET /api/fuel-types/distribution
     * Get stock distribution by fuel type (percentages, volumes, consumption)
     */
    public function distribution(): void
    {
        try {
            $colors = [
                'GAS92'    => '#3b82f6',
                'GAS80'    => '#6366f1',
                'GAS95'    => '#10b981',
                'GAS98'    => '#f59e0b',
                'GAS92EUR' => '#8b5cf6',
                'DIESB7'   => '#ef4444',
                'DIESB10'  => '#f97316',
                'GAZ'      => '#06b6d4',
                'JET'      => '#64748b',
                'MTBE'     => '#a78bfa',
            ];

            $rows = \App\Core\Database::fetchAll("
                SELECT
                    ft.id as fuel_type_id,
                    ft.name as fuel_type_name,
                    ft.code as fuel_type_code,
                    COUNT(DISTINCT dt.depot_id) as depot_count,
                    COUNT(DISTINCT d.station_id) as station_count,
                    SUM(dt.current_stock_liters) as total_stock_liters,
                    SUM(dt.capacity_liters) as total_capacity_liters,
                    COALESCE(SUM(sp.liters_per_day), 0) as total_daily_usage_liters
                FROM depot_tanks dt
                JOIN fuel_types ft ON dt.fuel_type_id = ft.id
                JOIN depots d ON dt.depot_id = d.id
                LEFT JOIN sales_params sp ON dt.depot_id = sp.depot_id
                    AND dt.fuel_type_id = sp.fuel_type_id
                    AND (sp.effective_to IS NULL OR sp.effective_to >= CURDATE())
                WHERE dt.is_active = 1
                  AND dt.current_stock_liters > 0
                GROUP BY ft.id, ft.name, ft.code
                ORDER BY total_stock_liters DESC
            ");

            $grandTotal = array_sum(array_column($rows, 'total_stock_liters'));

            $formatVol = function(float $liters): string {
                if ($liters >= 1000000) return round($liters / 1000000, 1) . 'M L';
                if ($liters >= 1000)    return round($liters / 1000, 0) . 'K L';
                return round($liters) . ' L';
            };

            $result = [];
            foreach ($rows as $row) {
                $stock = (float)$row['total_stock_liters'];
                $capacity = (float)$row['total_capacity_liters'];
                $daily = (float)$row['total_daily_usage_liters'];
                $code = $row['fuel_type_code'];
                $pct = $grandTotal > 0 ? round($stock / $grandTotal * 100, 1) : 0;
                $fillPct = $capacity > 0 ? round($stock / $capacity * 100, 1) : 0;

                $result[] = [
                    'fuel_type_id'    => (int)$row['fuel_type_id'],
                    'name'            => $row['fuel_type_name'],
                    'code'            => $code,
                    'color'           => $colors[$code] ?? '#94a3b8',
                    'volume'          => $formatVol($stock),
                    'volume_liters'   => round($stock, 0),
                    'percentage'      => $pct,
                    'fill_percentage' => $fillPct,
                    'stations'        => (int)$row['station_count'],
                    'avg_stock'       => $formatVol($stock / max(1, (int)$row['depot_count'])),
                    'daily_usage'     => $formatVol($daily),
                ];
            }

            $avgFill = count($rows) > 0
                ? round(array_sum(array_column(
                    array_map(fn($r) => [
                        'f' => (float)$r['total_capacity_liters'] > 0
                            ? (float)$r['total_stock_liters'] / (float)$r['total_capacity_liters'] * 100
                            : 0
                    ], $rows), 'f')) / count($rows), 1)
                : 0;

            Response::json([
                'success' => true,
                'data' => $result,
                'summary' => [
                    'total_volume'   => $formatVol($grandTotal),
                    'fuel_types'     => count($result),
                    'avg_fill'       => $avgFill . '%',
                ],
                'count' => count($result)
            ]);
        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'error' => 'Failed to fetch fuel distribution: ' . $e->getMessage()
            ], 500);
        }
    }
}
