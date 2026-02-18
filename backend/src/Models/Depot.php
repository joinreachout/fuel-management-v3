<?php

namespace App\Models;

use App\Core\Database;

/**
 * Depot Model
 * Represents a fuel depot at a railway station
 */
class Depot
{
    /**
     * Get all depots
     *
     * @return array
     */
    public static function all(): array
    {
        return Database::fetchAll("
            SELECT
                d.id,
                d.station_id,
                d.name,
                d.code,
                s.name as station_name,
                s.code as station_code,
                d.created_at
            FROM depots d
            LEFT JOIN stations s ON d.station_id = s.id
            ORDER BY s.name, d.name
        ");
    }

    /**
     * Find depot by ID
     *
     * @param int $id Depot ID
     * @return array|false
     */
    public static function find(int $id)
    {
        return Database::fetchOne("
            SELECT
                d.id,
                d.station_id,
                d.name,
                d.code,
                s.name as station_name,
                s.code as station_code,
                d.created_at
            FROM depots d
            LEFT JOIN stations s ON d.station_id = s.id
            WHERE d.id = ?
        ", [$id]);
    }

    /**
     * Get all tanks for a depot
     *
     * @param int $depotId Depot ID
     * @return array
     */
    public static function getTanks(int $depotId): array
    {
        return Database::fetchAll("
            SELECT
                dt.id,
                dt.tank_number,
                dt.fuel_type_id,
                ft.name as fuel_type_name,
                ft.code as fuel_type_code,
                ft.density,
                dt.capacity_liters,
                dt.current_stock_liters,
                ROUND(dt.current_stock_liters * ft.density / 1000, 2) as current_stock_tons,
                ROUND((dt.current_stock_liters / dt.capacity_liters) * 100, 1) as fill_percentage
            FROM depot_tanks dt
            LEFT JOIN fuel_types ft ON dt.fuel_type_id = ft.id
            WHERE dt.depot_id = ?
            ORDER BY dt.tank_number
        ", [$depotId]);
    }

    /**
     * Get total stock for a depot (all tanks combined)
     *
     * @param int $depotId Depot ID
     * @return array Grouped by fuel type
     */
    public static function getTotalStock(int $depotId): array
    {
        return Database::fetchAll("
            SELECT
                ft.id as fuel_type_id,
                ft.name as fuel_type_name,
                ft.code as fuel_type_code,
                ft.density,
                COUNT(dt.id) as tank_count,
                SUM(dt.capacity_liters) as total_capacity_liters,
                SUM(dt.current_stock_liters) as total_stock_liters,
                ROUND(SUM(dt.current_stock_liters * ft.density) / 1000, 2) as total_stock_tons
            FROM depot_tanks dt
            LEFT JOIN fuel_types ft ON dt.fuel_type_id = ft.id
            WHERE dt.depot_id = ?
            GROUP BY ft.id, ft.name, ft.code, ft.density
            ORDER BY ft.name
        ", [$depotId]);
    }

    /**
     * Get consumption forecast for a depot
     *
     * @param int $depotId Depot ID
     * @return array
     */
    public static function getConsumptionForecast(int $depotId): array
    {
        return Database::fetchAll("
            SELECT
                ft.id as fuel_type_id,
                ft.name as fuel_type_name,
                ft.code as fuel_type_code,
                sp.tons_per_day,
                SUM(dt.current_stock_liters) as current_stock_liters,
                ROUND(SUM(dt.current_stock_liters) / (sp.tons_per_day * 1000 / ft.density), 1) as days_until_stockout
            FROM sales_params sp
            LEFT JOIN fuel_types ft ON sp.fuel_type_id = ft.id
            LEFT JOIN depot_tanks dt ON dt.depot_id = sp.depot_id AND dt.fuel_type_id = sp.fuel_type_id
            WHERE sp.depot_id = ?
            GROUP BY ft.id, ft.name, ft.code, ft.density, sp.tons_per_day
            ORDER BY ft.name
        ", [$depotId]);
    }
}
