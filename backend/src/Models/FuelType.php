<?php

namespace App\Models;

use App\Core\Database;

/**
 * FuelType Model
 * Represents a type of fuel (АИ-92, ДТ, etc.)
 */
class FuelType
{
    /**
     * Get all fuel types
     *
     * @return array
     */
    public static function all(): array
    {
        return Database::fetchAll("
            SELECT
                id,
                name,
                code,
                density,
                unit,
                fuel_group,
                excel_mapping,
                created_at
            FROM fuel_types
            ORDER BY name
        ");
    }

    /**
     * Find fuel type by ID
     *
     * @param int $id Fuel type ID
     * @return array|false
     */
    public static function find(int $id)
    {
        return Database::fetchOne("
            SELECT
                id,
                name,
                code,
                density,
                unit,
                fuel_group,
                excel_mapping,
                created_at
            FROM fuel_types
            WHERE id = ?
        ", [$id]);
    }

    /**
     * Get total stock across all depots for this fuel type
     *
     * @param int $fuelTypeId Fuel type ID
     * @return array
     */
    public static function getTotalStock(int $fuelTypeId): array
    {
        return Database::fetchAll("
            SELECT
                d.id as depot_id,
                d.name as depot_name,
                s.id as station_id,
                s.name as station_name,
                COUNT(dt.id) as tank_count,
                SUM(dt.capacity_liters) as total_capacity_liters,
                SUM(dt.current_stock_liters) as total_stock_liters,
                ROUND(SUM(dt.current_stock_liters * ft.density) / 1000, 2) as total_stock_tons
            FROM depot_tanks dt
            LEFT JOIN depots d ON dt.depot_id = d.id
            LEFT JOIN stations s ON d.station_id = s.id
            LEFT JOIN fuel_types ft ON dt.fuel_type_id = ft.id
            WHERE dt.fuel_type_id = ?
            GROUP BY d.id, d.name, s.id, s.name
            ORDER BY s.name, d.name
        ", [$fuelTypeId]);
    }
}
