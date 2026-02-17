<?php

namespace App\Models;

use App\Core\Database;

/**
 * DepotTank Model
 * Represents a fuel storage tank at a depot
 * SOURCE OF TRUTH for fuel inventory (current_stock_liters)
 */
class DepotTank
{
    /**
     * Get all tanks
     *
     * @return array
     */
    public static function all(): array
    {
        return Database::fetchAll("
            SELECT
                dt.id,
                dt.depot_id,
                dt.fuel_type_id,
                dt.tank_number,
                dt.capacity_liters,
                dt.current_stock_liters,
                d.name as depot_name,
                d.code as depot_code,
                s.name as station_name,
                ft.name as fuel_type_name,
                ft.code as fuel_type_code,
                ft.density,
                ROUND(dt.current_stock_liters * ft.density / 1000, 2) as current_stock_tons,
                ROUND((dt.current_stock_liters / dt.capacity_liters) * 100, 1) as fill_percentage
            FROM depot_tanks dt
            LEFT JOIN depots d ON dt.depot_id = d.id
            LEFT JOIN stations s ON d.station_id = s.id
            LEFT JOIN fuel_types ft ON dt.fuel_type_id = ft.id
            ORDER BY s.name, d.name, dt.tank_number
        ");
    }

    /**
     * Find tank by ID
     *
     * @param int $id Tank ID
     * @return array|false
     */
    public static function find(int $id)
    {
        return Database::fetchOne("
            SELECT
                dt.id,
                dt.depot_id,
                dt.fuel_type_id,
                dt.tank_number,
                dt.capacity_liters,
                dt.current_stock_liters,
                d.name as depot_name,
                d.code as depot_code,
                s.name as station_name,
                ft.name as fuel_type_name,
                ft.code as fuel_type_code,
                ft.density,
                ROUND(dt.current_stock_liters * ft.density / 1000, 2) as current_stock_tons,
                ROUND((dt.current_stock_liters / dt.capacity_liters) * 100, 1) as fill_percentage,
                dt.created_at,
                dt.updated_at
            FROM depot_tanks dt
            LEFT JOIN depots d ON dt.depot_id = d.id
            LEFT JOIN stations s ON d.station_id = s.id
            LEFT JOIN fuel_types ft ON dt.fuel_type_id = ft.id
            WHERE dt.id = ?
        ", [$id]);
    }

    /**
     * Update stock level for a tank
     * This is the SOURCE OF TRUTH - all inventory changes go through here
     *
     * @param int $tankId Tank ID
     * @param float $newStockLiters New stock level in liters
     * @param string $reason Reason for change (delivery, consumption, transfer, etc.)
     * @return bool
     */
    public static function updateStock(int $tankId, float $newStockLiters, string $reason = 'manual_adjustment'): bool
    {
        try {
            Database::beginTransaction();

            // Get current stock
            $tank = self::find($tankId);
            if (!$tank) {
                throw new \Exception("Tank not found: $tankId");
            }

            $oldStock = $tank['current_stock_liters'];

            // Update tank stock
            Database::update(
                'depot_tanks',
                ['current_stock_liters' => $newStockLiters],
                'id = ?',
                [$tankId]
            );

            // Log the change in stock_audit
            Database::insert('stock_audit', [
                'depot_tank_id' => $tankId,
                'change_reason' => $reason,
                'old_stock_liters' => $oldStock,
                'new_stock_liters' => $newStockLiters,
                'change_liters' => $newStockLiters - $oldStock,
                'changed_at' => date('Y-m-d H:i:s')
            ]);

            Database::commit();
            return true;

        } catch (\Exception $e) {
            Database::rollback();
            throw $e;
        }
    }

    /**
     * Get stock history for a tank
     *
     * @param int $tankId Tank ID
     * @param int $limit Number of records to return
     * @return array
     */
    public static function getStockHistory(int $tankId, int $limit = 50): array
    {
        return Database::fetchAll("
            SELECT
                sa.id,
                sa.change_type,
                sa.old_stock_liters,
                sa.new_stock_liters,
                sa.change_liters,
                ROUND(sa.change_liters * ft.density / 1000, 2) as change_tons,
                sa.created_at
            FROM stock_audit sa
            LEFT JOIN depot_tanks dt ON sa.depot_tank_id = dt.id
            LEFT JOIN fuel_types ft ON dt.fuel_type_id = ft.id
            WHERE sa.depot_tank_id = ?
            ORDER BY sa.created_at DESC
            LIMIT ?
        ", [$tankId, $limit]);
    }

    /**
     * Get tanks with low stock (below threshold)
     *
     * @param float $thresholdPercentage Percentage threshold (e.g., 20 for 20%)
     * @return array
     */
    public static function getLowStockTanks(float $thresholdPercentage = 20): array
    {
        return Database::fetchAll("
            SELECT
                dt.id,
                dt.depot_id,
                dt.tank_number,
                d.name as depot_name,
                s.name as station_name,
                ft.name as fuel_type_name,
                dt.capacity_liters,
                dt.current_stock_liters,
                ROUND((dt.current_stock_liters / dt.capacity_liters) * 100, 1) as fill_percentage,
                ROUND(dt.current_stock_liters * ft.density / 1000, 2) as current_stock_tons
            FROM depot_tanks dt
            LEFT JOIN depots d ON dt.depot_id = d.id
            LEFT JOIN stations s ON d.station_id = s.id
            LEFT JOIN fuel_types ft ON dt.fuel_type_id = ft.id
            WHERE (dt.current_stock_liters / dt.capacity_liters) * 100 < ?
            ORDER BY fill_percentage ASC
        ", [$thresholdPercentage]);
    }
}
