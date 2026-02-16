<?php

namespace App\Services;

use App\Core\Database;

/**
 * Forecast Service
 * Calculates fuel consumption predictions and days until empty
 * Based on stock_policies and historical consumption data
 */
class ForecastService
{
    /**
     * Calculate days until tank is empty based on consumption rate
     *
     * @param int $tankId Depot tank ID
     * @return array Forecast data with days_until_empty, daily_consumption, etc.
     */
    public static function getDaysUntilEmpty(int $tankId): array
    {
        // Get tank current stock
        $tank = Database::fetchAll("
            SELECT
                dt.id,
                dt.depot_id,
                dt.fuel_type_id,
                dt.current_stock_liters,
                dt.capacity_liters,
                d.name as depot_name,
                ft.name as fuel_type_name
            FROM depot_tanks dt
            LEFT JOIN depots d ON dt.depot_id = d.id
            LEFT JOIN fuel_types ft ON dt.fuel_type_id = ft.id
            WHERE dt.id = ?
        ", [$tankId]);

        if (empty($tank)) {
            return [
                'error' => 'Tank not found',
                'tank_id' => $tankId
            ];
        }

        $tank = $tank[0];

        // Get stock policy for this depot and fuel type
        $policy = Database::fetchAll("
            SELECT
                daily_consumption_liters,
                min_stock_days,
                max_stock_days,
                reorder_point_liters
            FROM stock_policies
            WHERE depot_id = ? AND fuel_type_id = ?
        ", [$tank['depot_id'], $tank['fuel_type_id']]);

        if (empty($policy)) {
            // No policy configured - estimate from sales_params
            $salesParam = Database::fetchAll("
                SELECT avg_daily_volume_liters
                FROM sales_params
                WHERE depot_id = ? AND fuel_type_id = ?
            ", [$tank['depot_id'], $tank['fuel_type_id']]);

            $dailyConsumption = !empty($salesParam) ? (float)$salesParam[0]['avg_daily_volume_liters'] : 1000.0;

            return [
                'tank_id' => $tankId,
                'depot_name' => $tank['depot_name'],
                'fuel_type_name' => $tank['fuel_type_name'],
                'current_stock_liters' => (float)$tank['current_stock_liters'],
                'capacity_liters' => (float)$tank['capacity_liters'],
                'daily_consumption_liters' => $dailyConsumption,
                'days_until_empty' => $dailyConsumption > 0 ? round((float)$tank['current_stock_liters'] / $dailyConsumption, 1) : null,
                'estimated' => true,
                'warning' => 'No stock policy configured - using sales_params estimate'
            ];
        }

        $policy = $policy[0];
        $dailyConsumption = (float)$policy['daily_consumption_liters'];
        $currentStock = (float)$tank['current_stock_liters'];

        $daysUntilEmpty = $dailyConsumption > 0 ? $currentStock / $dailyConsumption : null;

        return [
            'tank_id' => $tankId,
            'depot_name' => $tank['depot_name'],
            'fuel_type_name' => $tank['fuel_type_name'],
            'current_stock_liters' => $currentStock,
            'capacity_liters' => (float)$tank['capacity_liters'],
            'daily_consumption_liters' => $dailyConsumption,
            'days_until_empty' => $daysUntilEmpty ? round($daysUntilEmpty, 1) : null,
            'min_stock_days' => (int)$policy['min_stock_days'],
            'max_stock_days' => (int)$policy['max_stock_days'],
            'reorder_point_liters' => (float)$policy['reorder_point_liters'],
            'below_reorder_point' => $currentStock < (float)$policy['reorder_point_liters'],
            'estimated' => false
        ];
    }

    /**
     * Calculate forecast for entire depot (all tanks)
     *
     * @param int $depotId Depot ID
     * @return array Forecast data for all tanks in depot
     */
    public static function getDepotForecast(int $depotId): array
    {
        // Get all tanks for this depot
        $tanks = Database::fetchAll("
            SELECT id
            FROM depot_tanks
            WHERE depot_id = ?
        ", [$depotId]);

        $forecasts = [];
        foreach ($tanks as $tank) {
            $forecasts[] = self::getDaysUntilEmpty($tank['id']);
        }

        return [
            'depot_id' => $depotId,
            'tanks' => $forecasts,
            'count' => count($forecasts)
        ];
    }

    /**
     * Get critical tanks (below minimum stock days)
     *
     * @param int|null $depotId Optional depot ID filter
     * @return array List of tanks requiring urgent attention
     */
    public static function getCriticalTanks(?int $depotId = null): array
    {
        $sql = "
            SELECT
                dt.id as tank_id,
                dt.depot_id,
                d.name as depot_name,
                dt.fuel_type_id,
                ft.name as fuel_type_name,
                dt.current_stock_liters,
                sp.daily_consumption_liters,
                sp.min_stock_days,
                ROUND(dt.current_stock_liters / sp.daily_consumption_liters, 1) as days_until_empty
            FROM depot_tanks dt
            LEFT JOIN depots d ON dt.depot_id = d.id
            LEFT JOIN fuel_types ft ON dt.fuel_type_id = ft.id
            LEFT JOIN stock_policies sp ON dt.depot_id = sp.depot_id AND dt.fuel_type_id = sp.fuel_type_id
            WHERE sp.daily_consumption_liters > 0
            AND (dt.current_stock_liters / sp.daily_consumption_liters) < sp.min_stock_days
        ";

        $params = [];
        if ($depotId !== null) {
            $sql .= " AND dt.depot_id = ?";
            $params[] = $depotId;
        }

        $sql .= " ORDER BY days_until_empty ASC";

        return Database::fetchAll($sql, $params);
    }

    /**
     * Predict when depot needs reorder
     *
     * @param int $depotId Depot ID
     * @param int $fuelTypeId Fuel type ID
     * @return array Reorder prediction with date and urgency
     */
    public static function predictReorderDate(int $depotId, int $fuelTypeId): array
    {
        // Get total stock for this fuel type in depot
        $stock = Database::fetchAll("
            SELECT
                SUM(dt.current_stock_liters) as total_stock_liters
            FROM depot_tanks dt
            WHERE dt.depot_id = ? AND dt.fuel_type_id = ?
        ", [$depotId, $fuelTypeId]);

        $totalStock = !empty($stock) ? (float)$stock[0]['total_stock_liters'] : 0;

        // Get stock policy
        $policy = Database::fetchAll("
            SELECT
                daily_consumption_liters,
                min_stock_days,
                reorder_point_liters
            FROM stock_policies
            WHERE depot_id = ? AND fuel_type_id = ?
        ", [$depotId, $fuelTypeId]);

        if (empty($policy)) {
            return [
                'error' => 'No stock policy configured',
                'depot_id' => $depotId,
                'fuel_type_id' => $fuelTypeId
            ];
        }

        $policy = $policy[0];
        $dailyConsumption = (float)$policy['daily_consumption_liters'];
        $reorderPoint = (float)$policy['reorder_point_liters'];
        $minStockDays = (int)$policy['min_stock_days'];

        $daysUntilReorder = $dailyConsumption > 0 ? ($totalStock - $reorderPoint) / $dailyConsumption : null;

        $urgency = 'NORMAL';
        if ($totalStock <= $reorderPoint) {
            $urgency = 'CRITICAL';
        } elseif ($daysUntilReorder && $daysUntilReorder <= $minStockDays) {
            $urgency = 'MUST_ORDER';
        }

        return [
            'depot_id' => $depotId,
            'fuel_type_id' => $fuelTypeId,
            'total_stock_liters' => $totalStock,
            'daily_consumption_liters' => $dailyConsumption,
            'reorder_point_liters' => $reorderPoint,
            'days_until_reorder' => $daysUntilReorder ? round($daysUntilReorder, 1) : null,
            'reorder_date' => $daysUntilReorder ? date('Y-m-d', strtotime("+{$daysUntilReorder} days")) : null,
            'urgency' => $urgency,
            'should_order_now' => $totalStock <= $reorderPoint
        ];
    }
}
