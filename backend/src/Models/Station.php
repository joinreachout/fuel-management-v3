<?php
/**
 * Station Model
 * Represents a railway station with depots
 */

namespace App\Models;

use App\Core\Database;

class Station
{
    /**
     * Get all stations
     *
     * @return array
     */
    public static function all(): array
    {
        return Database::fetchAll("
            SELECT
                s.id,
                s.name,
                s.code,
                s.region_id,
                r.name as region_name,
                s.is_active,
                s.created_at
            FROM stations s
            LEFT JOIN regions r ON s.region_id = r.id
            WHERE s.is_active = 1
            ORDER BY r.name, s.name
        ");
    }

    /**
     * Find station by ID
     *
     * @param int $id Station ID
     * @return array|false
     */
    public static function find(int $id)
    {
        return Database::fetchOne("
            SELECT
                s.id,
                s.name,
                s.code,
                s.region_id,
                r.name as region_name,
                s.is_active,
                s.created_at
            FROM stations s
            LEFT JOIN regions r ON s.region_id = r.id
            WHERE s.id = ?
        ", [$id]);
    }

    /**
     * Get current stock for station by fuel type
     *
     * @param int $stationId Station ID
     * @param int $fuelTypeId Fuel type ID
     * @return float Stock in liters
     */
    public static function getCurrentStock(int $stationId, int $fuelTypeId): float
    {
        $result = Database::fetchColumn("
            SELECT COALESCE(SUM(dt.current_stock_liters), 0)
            FROM depot_tanks dt
            JOIN depots d ON dt.depot_id = d.id
            WHERE d.station_id = ?
              AND dt.fuel_type_id = ?
              AND dt.is_active = 1
              AND d.is_active = 1
        ", [$stationId, $fuelTypeId]);

        return (float) $result;
    }

    /**
     * Get all stock levels for a station
     *
     * @param int $stationId Station ID
     * @return array
     */
    public static function getStockLevels(int $stationId): array
    {
        return Database::fetchAll("
            SELECT
                ft.id as fuel_type_id,
                ft.name as fuel_type_name,
                ft.density,
                COALESCE(SUM(dt.current_stock_liters), 0) as stock_liters,
                COALESCE(SUM(dt.capacity_liters), 0) as capacity_liters,
                ROUND(COALESCE(SUM(dt.current_stock_liters * ft.density) / 1000, 0), 2) as stock_tons,
                ROUND(COALESCE(SUM(dt.capacity_liters * ft.density) / 1000, 0), 2) as capacity_tons,
                ROUND(
                    CASE
                        WHEN SUM(dt.capacity_liters) > 0
                        THEN SUM(dt.current_stock_liters) / SUM(dt.capacity_liters) * 100
                        ELSE 0
                    END, 1
                ) as fill_percentage
            FROM fuel_types ft
            LEFT JOIN depot_tanks dt ON ft.id = dt.fuel_type_id AND dt.is_active = 1
            LEFT JOIN depots d ON dt.depot_id = d.id AND d.station_id = ? AND d.is_active = 1
            GROUP BY ft.id, ft.name, ft.density
            HAVING stock_liters > 0
            ORDER BY ft.name
        ", [$stationId]);
    }

    /**
     * Get depots for a station
     *
     * @param int $stationId Station ID
     * @return array
     */
    public static function getDepots(int $stationId): array
    {
        return Database::fetchAll("
            SELECT
                id,
                name,
                code,
                category,
                capacity_m3,
                daily_unloading_capacity_tons,
                is_active
            FROM depots
            WHERE station_id = ? AND is_active = 1
            ORDER BY name
        ", [$stationId]);
    }

    /**
     * Create new station
     *
     * @param array $data Station data
     * @return int New station ID
     */
    public static function create(array $data): int
    {
        return Database::insert('stations', [
            'name' => $data['name'],
            'code' => $data['code'] ?? null,
            'region_id' => $data['region_id'],
            'is_active' => $data['is_active'] ?? 1,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Update station
     *
     * @param int $id Station ID
     * @param array $data Data to update
     * @return int Number of affected rows
     */
    public static function update(int $id, array $data): int
    {
        $updateData = [];

        if (isset($data['name'])) $updateData['name'] = $data['name'];
        if (isset($data['code'])) $updateData['code'] = $data['code'];
        if (isset($data['region_id'])) $updateData['region_id'] = $data['region_id'];
        if (isset($data['is_active'])) $updateData['is_active'] = $data['is_active'];

        $updateData['updated_at'] = date('Y-m-d H:i:s');

        return Database::update('stations', $updateData, 'id = ?', [$id]);
    }
}
