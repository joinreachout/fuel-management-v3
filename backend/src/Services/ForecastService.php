<?php

namespace App\Services;

use App\Core\Database;

/**
 * Forecast Service
 * Calculates fuel consumption predictions and days until empty
 * Uses sales_params.liters_per_day for actual consumption data
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

        // Get consumption rate from sales_params (ACTUAL data)
        $salesParam = Database::fetchAll("
            SELECT liters_per_day
            FROM sales_params
            WHERE depot_id = ? AND fuel_type_id = ?
            AND (effective_to IS NULL OR effective_to >= CURDATE())
            ORDER BY effective_from DESC
            LIMIT 1
        ", [$tank['depot_id'], $tank['fuel_type_id']]);

        if (empty($salesParam)) {
            return [
                'tank_id' => $tankId,
                'depot_name' => $tank['depot_name'],
                'fuel_type_name' => $tank['fuel_type_name'],
                'current_stock_liters' => (float)$tank['current_stock_liters'],
                'capacity_liters' => (float)$tank['capacity_liters'],
                'daily_consumption_liters' => null,
                'days_until_empty' => null,
                'warning' => 'No sales_params configured for this depot/fuel type'
            ];
        }

        $dailyConsumption = (float)$salesParam[0]['liters_per_day'];
        $currentStock = (float)$tank['current_stock_liters'];

        $daysUntilEmpty = $dailyConsumption > 0 ? $currentStock / $dailyConsumption : null;

        // Get stock policy thresholds (if configured)
        $policy = Database::fetchAll("
            SELECT
                min_level_liters,
                critical_level_liters,
                target_level_liters
            FROM stock_policies
            WHERE depot_id = ? AND fuel_type_id = ?
        ", [$tank['depot_id'], $tank['fuel_type_id']]);

        $result = [
            'tank_id' => $tankId,
            'depot_name' => $tank['depot_name'],
            'fuel_type_name' => $tank['fuel_type_name'],
            'current_stock_liters' => $currentStock,
            'capacity_liters' => (float)$tank['capacity_liters'],
            'daily_consumption_liters' => $dailyConsumption,
            'days_until_empty' => $daysUntilEmpty ? round($daysUntilEmpty, 1) : null
        ];

        // Add policy thresholds if available
        if (!empty($policy)) {
            $policy = $policy[0];
            $result['min_level_liters'] = (float)$policy['min_level_liters'];
            $result['critical_level_liters'] = (float)$policy['critical_level_liters'];
            $result['below_minimum'] = $currentStock < (float)$policy['min_level_liters'];
            $result['below_critical'] = $currentStock < (float)$policy['critical_level_liters'];

            // Calculate days until minimum/critical
            if ($dailyConsumption > 0) {
                $daysUntilMin = ($currentStock - (float)$policy['min_level_liters']) / $dailyConsumption;
                $daysUntilCritical = ($currentStock - (float)$policy['critical_level_liters']) / $dailyConsumption;

                $result['days_until_minimum'] = $daysUntilMin > 0 ? round($daysUntilMin, 1) : 0;
                $result['days_until_critical'] = $daysUntilCritical > 0 ? round($daysUntilCritical, 1) : 0;
            }
        }

        return $result;
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
            $forecast = self::getDaysUntilEmpty($tank['id']);
            if (!isset($forecast['error'])) {
                $forecasts[] = $forecast;
            }
        }

        return [
            'depot_id' => $depotId,
            'tanks' => $forecasts,
            'count' => count($forecasts)
        ];
    }

    /**
     * Get critical tanks (below critical level or running out soon)
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
                sp.liters_per_day as daily_consumption_liters,
                pol.critical_level_liters,
                pol.min_level_liters,
                ROUND(dt.current_stock_liters / sp.liters_per_day, 1) as days_until_empty
            FROM depot_tanks dt
            LEFT JOIN depots d ON dt.depot_id = d.id
            LEFT JOIN fuel_types ft ON dt.fuel_type_id = ft.id
            LEFT JOIN sales_params sp ON dt.depot_id = sp.depot_id
                AND dt.fuel_type_id = sp.fuel_type_id
                AND (sp.effective_to IS NULL OR sp.effective_to >= CURDATE())
            LEFT JOIN stock_policies pol ON dt.depot_id = pol.depot_id
                AND dt.fuel_type_id = pol.fuel_type_id
            WHERE sp.liters_per_day > 0
            AND (
                dt.current_stock_liters < pol.critical_level_liters
                OR (dt.current_stock_liters / sp.liters_per_day) < 7
            )
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
     * Predict when depot needs reorder for specific fuel type
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

        // Get consumption rate from sales_params
        $salesParam = Database::fetchAll("
            SELECT liters_per_day
            FROM sales_params
            WHERE depot_id = ? AND fuel_type_id = ?
            AND (effective_to IS NULL OR effective_to >= CURDATE())
            ORDER BY effective_from DESC
            LIMIT 1
        ", [$depotId, $fuelTypeId]);

        if (empty($salesParam)) {
            return [
                'error' => 'No sales_params configured',
                'depot_id' => $depotId,
                'fuel_type_id' => $fuelTypeId
            ];
        }

        $dailyConsumption = (float)$salesParam[0]['liters_per_day'];

        // Get stock policy thresholds
        $policy = Database::fetchAll("
            SELECT
                min_level_liters,
                critical_level_liters
            FROM stock_policies
            WHERE depot_id = ? AND fuel_type_id = ?
        ", [$depotId, $fuelTypeId]);

        if (empty($policy)) {
            // No policy - use simple calculation (7 days as reorder point)
            $daysUntilEmpty = $dailyConsumption > 0 ? $totalStock / $dailyConsumption : null;

            return [
                'depot_id' => $depotId,
                'fuel_type_id' => $fuelTypeId,
                'total_stock_liters' => $totalStock,
                'daily_consumption_liters' => $dailyConsumption,
                'days_until_empty' => $daysUntilEmpty ? round($daysUntilEmpty, 1) : null,
                'urgency' => $daysUntilEmpty && $daysUntilEmpty < 7 ? 'MUST_ORDER' : 'NORMAL',
                'warning' => 'No stock_policies configured - using 7 days threshold'
            ];
        }

        $policy = $policy[0];
        $minLevel = (float)$policy['min_level_liters'];
        $criticalLevel = (float)$policy['critical_level_liters'];

        $daysUntilMin = $dailyConsumption > 0 ? ($totalStock - $minLevel) / $dailyConsumption : null;
        $daysUntilCritical = $dailyConsumption > 0 ? ($totalStock - $criticalLevel) / $dailyConsumption : null;

        // Determine urgency
        $urgency = 'NORMAL';
        if ($totalStock <= $criticalLevel) {
            $urgency = 'CATASTROPHE';
        } elseif ($totalStock <= $minLevel) {
            $urgency = 'CRITICAL';
        } elseif ($daysUntilMin && $daysUntilMin <= 3) {
            $urgency = 'MUST_ORDER';
        }

        return [
            'depot_id' => $depotId,
            'fuel_type_id' => $fuelTypeId,
            'total_stock_liters' => $totalStock,
            'daily_consumption_liters' => $dailyConsumption,
            'min_level_liters' => $minLevel,
            'critical_level_liters' => $criticalLevel,
            'days_until_minimum' => $daysUntilMin ? round($daysUntilMin, 1) : null,
            'days_until_critical' => $daysUntilCritical ? round($daysUntilCritical, 1) : null,
            'reorder_date' => $daysUntilMin ? date('Y-m-d', strtotime("+{$daysUntilMin} days")) : null,
            'urgency' => $urgency,
            'should_order_now' => $totalStock <= $minLevel
        ];
    }
}
