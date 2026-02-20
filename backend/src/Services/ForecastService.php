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
            SELECT sp.liters_per_day
            FROM sales_params sp
            WHERE sp.depot_id = ? AND sp.fuel_type_id = ?
            AND (sp.effective_to IS NULL OR sp.effective_to >= CURDATE())
            ORDER BY sp.effective_from DESC
            LIMIT 1
        ", [$depotId, $fuelTypeId]);

        if (empty($salesParam)) {
            return [
                'error' => 'No sales_params configured',
                'depot_id' => $depotId,
                'fuel_type_id' => $fuelTypeId
            ];
        }

        $dailyConsumption = (float)$salesParam[0]['liters_per_day']; // L/day

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

    /**
     * Get station-level fuel forecast for chart
     * Generates time series data showing projected fuel levels
     *
     * @param string $level 'station' or 'region'
     * @param string|null $region Region filter
     * @param int|null $stationId Station filter
     * @param int|null $fuelTypeId Fuel type filter
     * @param int $days Forecast horizon (30, 60, or 90 days)
     * @return array Time series forecast data
     */
    public static function getStationForecast(
        string $level,
        ?string $region,
        ?int $stationId,
        ?int $fuelTypeId,
        int $days
    ): array {
        // Build SQL query based on filters
        $sql = "
            SELECT
                s.id as station_id,
                s.name as station_name,
                s.code as station_code,
                r.name as region_name,
                dt.id as tank_id,
                dt.fuel_type_id,
                ft.name as fuel_type_name,
                ft.code as fuel_type_code,
                dt.current_stock_liters,
                dt.capacity_liters,
                sp.liters_per_day
            FROM stations s
            LEFT JOIN regions r ON s.region_id = r.id
            LEFT JOIN depots d ON s.id = d.station_id
            LEFT JOIN depot_tanks dt ON d.id = dt.depot_id
            LEFT JOIN fuel_types ft ON dt.fuel_type_id = ft.id
            LEFT JOIN sales_params sp ON dt.depot_id = sp.depot_id
                AND dt.fuel_type_id = sp.fuel_type_id
                AND (sp.effective_to IS NULL OR sp.effective_to >= CURDATE())
            WHERE 1=1
        ";

        $params = [];

        if ($region) {
            $sql .= " AND r.name = ?";
            $params[] = $region;
        }

        if ($stationId) {
            $sql .= " AND s.id = ?";
            $params[] = $stationId;
        }

        if ($fuelTypeId) {
            $sql .= " AND dt.fuel_type_id = ?";
            $params[] = $fuelTypeId;
        }

        $sql .= " AND sp.liters_per_day > 0 ORDER BY s.name, ft.name";

        $tanks = Database::fetchAll($sql, $params);

        if (empty($tanks)) {
            return [
                'labels' => [],
                'datasets' => [],
                'message' => 'No data available for selected filters'
            ];
        }

        // Generate date labels and date strings for delivery lookup
        $labels = [];
        $dateStrings = []; // 'Y-m-d' format for matching orders
        for ($i = 0; $i <= $days; $i++) {
            $labels[] = date('M d', strtotime("+{$i} days"));
            $dateStrings[] = date('Y-m-d', strtotime("+{$i} days"));
        }

        // Group tanks by fuel type or station
        // Also track station_ids and fuel_type_ids for orders query
        $groupedData = [];
        foreach ($tanks as $tank) {
            if ($level === 'station') {
                $key = $tank['station_name'] . ' - ' . $tank['fuel_type_name'];
            } else {
                // Region level - group by fuel type
                $key = $tank['fuel_type_name'];
            }

            if (!isset($groupedData[$key])) {
                $groupedData[$key] = [
                    'current_stock' => 0,
                    'daily_consumption' => 0,
                    'capacity' => 0,
                    'fuel_type' => $tank['fuel_type_name'],
                    'station_ids' => [],
                    'fuel_type_ids' => []
                ];
            }

            $groupedData[$key]['current_stock'] += (float)$tank['current_stock_liters'];
            $groupedData[$key]['daily_consumption'] += (float)$tank['liters_per_day'];
            $groupedData[$key]['capacity'] += (float)$tank['capacity_liters'];

            // Collect unique station_ids and fuel_type_ids for orders query
            if (!in_array($tank['station_id'], $groupedData[$key]['station_ids'])) {
                $groupedData[$key]['station_ids'][] = (int)$tank['station_id'];
            }
            if (!in_array($tank['fuel_type_id'], $groupedData[$key]['fuel_type_ids'])) {
                $groupedData[$key]['fuel_type_ids'][] = (int)$tank['fuel_type_id'];
            }
        }

        // Generate datasets with forecast projection (including delivery bumps)
        $datasets = [];
        $colors = [
            'Diesel' => '#3b82f6',
            'Petrol 95' => '#10b981',
            'Petrol 98' => '#f59e0b',
            'Petrol' => '#8b5cf6',
            'Kerosene' => '#ec4899'
        ];
        $colorIndex = 0;
        $defaultColors = ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899', '#06b6d4', '#f43f5e'];

        foreach ($groupedData as $label => $data) {
            $stockData = [];
            $currentStock = $data['current_stock'] / 1000; // L → kL for chart display
            $dailyConsumption = $data['daily_consumption'] / 1000; // L/day → kL/day
            $capacityKL = $data['capacity'] > 0 ? $data['capacity'] / 1000 : PHP_FLOAT_MAX;

            // Build delivery map for this group: ['Y-m-d' => kL]
            $deliveries = [];
            if (!empty($data['station_ids']) && !empty($data['fuel_type_ids'])) {
                $stationPlaceholders = implode(',', array_fill(0, count($data['station_ids']), '?'));
                $fuelPlaceholders = implode(',', array_fill(0, count($data['fuel_type_ids']), '?'));

                $orderParams = array_merge(
                    $data['station_ids'],
                    $data['fuel_type_ids'],
                    [$days]
                );

                $orderRows = Database::fetchAll("
                    SELECT
                        DATE_FORMAT(o.delivery_date, '%Y-%m-%d') as delivery_date,
                        SUM(o.quantity_liters) as total_liters
                    FROM orders o
                    WHERE o.station_id IN ({$stationPlaceholders})
                      AND o.fuel_type_id IN ({$fuelPlaceholders})
                      AND o.delivery_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
                      AND o.status IN ('confirmed', 'in_transit')
                    GROUP BY o.delivery_date
                ", $orderParams);

                foreach ($orderRows as $row) {
                    // Convert liters to kL for chart
                    $deliveries[$row['delivery_date']] = (float)$row['total_liters'] / 1000;
                }
            }

            // Day-by-day projection: Stock(day) = Stock(day-1) - consumption + delivery
            $projectedStock = $currentStock;
            for ($i = 0; $i <= $days; $i++) {
                if ($i > 0) {
                    $deliveryToday = $deliveries[$dateStrings[$i]] ?? 0;
                    $projectedStock = $projectedStock - $dailyConsumption + $deliveryToday;
                    // Cap at 0 (can't go negative) and at capacity (can't overfill)
                    $projectedStock = max(0, min($capacityKL, $projectedStock));
                }
                $stockData[] = round($projectedStock, 2);
            }

            // Get color for this fuel type
            $color = $colors[$data['fuel_type']] ?? $defaultColors[$colorIndex % count($defaultColors)];
            $colorIndex++;

            $datasets[] = [
                'label' => $label,
                'data' => $stockData,
                'borderColor' => $color,
                'backgroundColor' => $color . '20',
                'tension' => 0.4,
                'fill' => true
            ];
        }

        return [
            'labels' => $labels,
            'datasets' => $datasets
        ];
    }
}
