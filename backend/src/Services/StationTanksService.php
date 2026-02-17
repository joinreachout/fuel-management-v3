<?php

namespace App\Services;

use App\Core\Database;

/**
 * StationTanksService
 * Business logic for station tank operations
 */
class StationTanksService
{
    /**
     * Get all tanks for a specific station
     *
     * @param int $stationId Station ID
     * @return array
     */
    public static function getStationTanks(int $stationId): array
    {
        try {
            $tanks = Database::fetchAll("
                SELECT
                    dt.id as tank_id,
                    dt.tank_number,
                    dt.depot_id,
                    d.name as depot_name,
                    dt.fuel_type_id,
                    ft.name as fuel_type_name,
                    ft.code as fuel_type_code,
                    ft.density,
                    dt.capacity_liters,
                    dt.current_stock_liters,
                    ROUND(dt.current_stock_liters * ft.density / 1000, 2) as current_stock_tons,
                    ROUND((dt.current_stock_liters / NULLIF(dt.capacity_liters, 0)) * 100, 1) as fill_percentage,
                    dt.is_active
                FROM depot_tanks dt
                JOIN depots d ON dt.depot_id = d.id
                JOIN fuel_types ft ON dt.fuel_type_id = ft.id
                WHERE d.station_id = ?
                  AND dt.is_active = 1
                  AND d.is_active = 1
                ORDER BY d.name, ft.name, dt.tank_number
            ", [$stationId]);

            return [
                'success' => true,
                'data' => $tanks,
                'count' => count($tanks),
                'station_id' => $stationId
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
