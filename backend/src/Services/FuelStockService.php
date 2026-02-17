<?php

namespace App\Services;

use App\Core\Database;

/**
 * FuelStockService
 * Business logic for fuel stock distribution across stations
 */
class FuelStockService
{
    /**
     * Get stock distribution for a specific fuel type across all stations
     *
     * @param int $fuelTypeId Fuel type ID
     * @return array
     */
    public static function getStockByStations(int $fuelTypeId): array
    {
        try {
            $stations = Database::fetchAll("
                SELECT
                    s.id as station_id,
                    s.name as station_name,
                    s.code as station_code,
                    ft.density,
                    COUNT(DISTINCT dt.id) as tank_count,
                    SUM(dt.capacity_liters) as total_capacity_liters,
                    SUM(dt.current_stock_liters) as total_stock_liters,
                    ROUND(SUM(dt.capacity_liters * ft.density) / 1000, 2) as total_capacity_tons,
                    ROUND(SUM(dt.current_stock_liters * ft.density) / 1000, 2) as total_stock_tons,
                    ROUND(
                        (SUM(dt.current_stock_liters) / NULLIF(SUM(dt.capacity_liters), 0)) * 100,
                        1
                    ) as avg_fill_percentage
                FROM depot_tanks dt
                JOIN depots d ON dt.depot_id = d.id
                JOIN stations s ON d.station_id = s.id
                JOIN fuel_types ft ON dt.fuel_type_id = ft.id
                WHERE dt.fuel_type_id = ?
                  AND dt.is_active = 1
                  AND d.is_active = 1
                  AND s.is_active = 1
                GROUP BY s.id, s.name, s.code
                HAVING total_stock_liters > 0
                ORDER BY s.name
            ", [$fuelTypeId]);

            return [
                'success' => true,
                'data' => $stations,
                'count' => count($stations),
                'fuel_type_id' => $fuelTypeId
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get stock distribution for a specific fuel type across all regions
     *
     * @param int $fuelTypeId Fuel type ID
     * @return array
     */
    public static function getStockByRegions(int $fuelTypeId): array
    {
        try {
            $regions = Database::fetchAll("
                SELECT
                    r.id as region_id,
                    r.name as region_name,
                    ft.density,
                    COUNT(DISTINCT dt.id) as tank_count,
                    COUNT(DISTINCT s.id) as station_count,
                    SUM(dt.capacity_liters) as total_capacity_liters,
                    SUM(dt.current_stock_liters) as total_stock_liters,
                    ROUND(SUM(dt.capacity_liters * ft.density) / 1000, 2) as total_capacity_tons,
                    ROUND(SUM(dt.current_stock_liters * ft.density) / 1000, 2) as total_stock_tons,
                    ROUND(
                        (SUM(dt.current_stock_liters) / NULLIF(SUM(dt.capacity_liters), 0)) * 100,
                        1
                    ) as avg_fill_percentage
                FROM depot_tanks dt
                JOIN depots d ON dt.depot_id = d.id
                JOIN stations s ON d.station_id = s.id
                JOIN regions r ON s.region_id = r.id
                JOIN fuel_types ft ON dt.fuel_type_id = ft.id
                WHERE dt.fuel_type_id = ?
                  AND dt.is_active = 1
                  AND d.is_active = 1
                  AND s.is_active = 1
                GROUP BY r.id, r.name
                HAVING total_stock_liters > 0
                ORDER BY r.name
            ", [$fuelTypeId]);

            return [
                'success' => true,
                'data' => $regions,
                'count' => count($regions),
                'fuel_type_id' => $fuelTypeId
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
