<?php

namespace App\Services;

use App\Core\Database;

/**
 * Alert Service
 * Generates alerts for low stock, critical situations, and anomalies
 * Uses stock_policies for thresholds and sales_params for consumption
 */
class AlertService
{
    /**
     * Get all active alerts
     *
     * @return array List of active alerts with severity levels
     */
    public static function getActiveAlerts(): array
    {
        $alerts = [];

        // 1. Critical stock levels (below critical_level_liters)
        $criticalAlerts = self::getCriticalStockAlerts();
        $alerts = array_merge($alerts, $criticalAlerts);

        // 2. Low stock levels (below min_level_liters)
        $lowStockAlerts = self::getLowStockAlerts();
        $alerts = array_merge($alerts, $lowStockAlerts);

        // 3. Running out soon (< 7 days based on consumption)
        $runningOutAlerts = self::getRunningOutSoonAlerts();
        $alerts = array_merge($alerts, $runningOutAlerts);

        // 4. Overfilled tanks (>95% capacity)
        $overfillAlerts = self::getOverfillAlerts();
        $alerts = array_merge($alerts, $overfillAlerts);

        // Sort by severity
        usort($alerts, function($a, $b) {
            $severityOrder = ['CATASTROPHE' => 1, 'CRITICAL' => 2, 'MUST_ORDER' => 3, 'WARNING' => 4, 'INFO' => 5];
            return ($severityOrder[$a['severity']] ?? 99) <=> ($severityOrder[$b['severity']] ?? 99);
        });

        return $alerts;
    }

    /**
     * Get critical stock alerts (below critical_level_liters)
     *
     * @return array Critical alerts
     */
    public static function getCriticalStockAlerts(): array
    {
        $results = Database::fetchAll("
            SELECT
                dt.depot_id,
                d.name as depot_name,
                dt.fuel_type_id,
                ft.name as fuel_type_name,
                SUM(dt.current_stock_liters) as total_stock_liters,
                pol.critical_level_liters
            FROM depot_tanks dt
            LEFT JOIN depots d ON dt.depot_id = d.id
            LEFT JOIN fuel_types ft ON dt.fuel_type_id = ft.id
            LEFT JOIN stock_policies pol ON dt.depot_id = pol.depot_id AND dt.fuel_type_id = pol.fuel_type_id
            WHERE pol.critical_level_liters IS NOT NULL
            GROUP BY dt.depot_id, dt.fuel_type_id, d.name, ft.name, pol.critical_level_liters
            HAVING total_stock_liters <= pol.critical_level_liters
        ");

        $alerts = [];
        foreach ($results as $row) {
            $alerts[] = [
                'type' => 'CRITICAL_STOCK',
                'severity' => 'CATASTROPHE',
                'depot_id' => $row['depot_id'],
                'depot_name' => $row['depot_name'],
                'fuel_type_name' => $row['fuel_type_name'],
                'message' => "КРИТИЧЕСКИЙ уровень: {$row['depot_name']} - {$row['fuel_type_name']}",
                'details' => [
                    'total_stock_liters' => (float)$row['total_stock_liters'],
                    'critical_level_liters' => (float)$row['critical_level_liters']
                ],
                'created_at' => date('Y-m-d H:i:s')
            ];
        }

        return $alerts;
    }

    /**
     * Get low stock alerts (below min_level_liters but above critical)
     *
     * @return array Low stock alerts
     */
    public static function getLowStockAlerts(): array
    {
        $results = Database::fetchAll("
            SELECT
                dt.depot_id,
                d.name as depot_name,
                dt.fuel_type_id,
                ft.name as fuel_type_name,
                SUM(dt.current_stock_liters) as total_stock_liters,
                pol.min_level_liters,
                pol.critical_level_liters
            FROM depot_tanks dt
            LEFT JOIN depots d ON dt.depot_id = d.id
            LEFT JOIN fuel_types ft ON dt.fuel_type_id = ft.id
            LEFT JOIN stock_policies pol ON dt.depot_id = pol.depot_id AND dt.fuel_type_id = pol.fuel_type_id
            WHERE pol.min_level_liters IS NOT NULL
            GROUP BY dt.depot_id, dt.fuel_type_id, d.name, ft.name, pol.min_level_liters, pol.critical_level_liters
            HAVING total_stock_liters <= pol.min_level_liters
            AND total_stock_liters > IFNULL(pol.critical_level_liters, 0)
        ");

        $alerts = [];
        foreach ($results as $row) {
            $alerts[] = [
                'type' => 'LOW_STOCK',
                'severity' => 'CRITICAL',
                'depot_id' => $row['depot_id'],
                'depot_name' => $row['depot_name'],
                'fuel_type_name' => $row['fuel_type_name'],
                'message' => "Низкий уровень запасов: {$row['depot_name']} - {$row['fuel_type_name']}",
                'details' => [
                    'total_stock_liters' => (float)$row['total_stock_liters'],
                    'min_level_liters' => (float)$row['min_level_liters']
                ],
                'created_at' => date('Y-m-d H:i:s')
            ];
        }

        return $alerts;
    }

    /**
     * Get alerts for tanks running out soon (< 7 days)
     *
     * @return array Running out soon alerts
     */
    public static function getRunningOutSoonAlerts(): array
    {
        $results = Database::fetchAll("
            SELECT
                dt.depot_id,
                d.name as depot_name,
                dt.fuel_type_id,
                ft.name as fuel_type_name,
                SUM(dt.current_stock_liters) as total_stock_liters,
                sp.liters_per_day as daily_consumption,
                ROUND(SUM(dt.current_stock_liters) / sp.liters_per_day, 1) as days_until_empty
            FROM depot_tanks dt
            LEFT JOIN depots d ON dt.depot_id = d.id
            LEFT JOIN fuel_types ft ON dt.fuel_type_id = ft.id
            LEFT JOIN sales_params sp ON dt.depot_id = sp.depot_id
                AND dt.fuel_type_id = sp.fuel_type_id
                AND (sp.effective_to IS NULL OR sp.effective_to >= CURDATE())
            WHERE sp.liters_per_day > 0
            GROUP BY dt.depot_id, dt.fuel_type_id, d.name, ft.name, sp.liters_per_day
            HAVING days_until_empty < 7
            AND days_until_empty > 0
        ");

        $alerts = [];
        foreach ($results as $row) {
            $daysLeft = (float)$row['days_until_empty'];
            $severity = $daysLeft <= 2 ? 'CRITICAL' :
                       ($daysLeft <= 5 ? 'MUST_ORDER' : 'WARNING');

            $alerts[] = [
                'type' => 'RUNNING_OUT_SOON',
                'severity' => $severity,
                'depot_id' => $row['depot_id'],
                'depot_name' => $row['depot_name'],
                'fuel_type_name' => $row['fuel_type_name'],
                'message' => "Заканчивается через {$daysLeft} дней: {$row['depot_name']} - {$row['fuel_type_name']}",
                'details' => [
                    'total_stock_liters' => (float)$row['total_stock_liters'],
                    'daily_consumption' => (float)$row['daily_consumption'],
                    'days_until_empty' => $daysLeft
                ],
                'created_at' => date('Y-m-d H:i:s')
            ];
        }

        return $alerts;
    }

    /**
     * Get overfill alerts (tanks >95% capacity)
     *
     * @return array Overfill alerts
     */
    public static function getOverfillAlerts(): array
    {
        $results = Database::fetchAll("
            SELECT
                dt.id as tank_id,
                dt.depot_id,
                d.name as depot_name,
                dt.tank_number,
                ft.name as fuel_type_name,
                dt.current_stock_liters,
                dt.capacity_liters,
                ROUND((dt.current_stock_liters / NULLIF(dt.capacity_liters, 0) * 100), 1) as fill_percentage
            FROM depot_tanks dt
            LEFT JOIN depots d ON dt.depot_id = d.id
            LEFT JOIN fuel_types ft ON dt.fuel_type_id = ft.id
            WHERE (dt.current_stock_liters / NULLIF(dt.capacity_liters, 0)) > 0.95
        ");

        $alerts = [];
        foreach ($results as $row) {
            $fillPercent = (float)$row['fill_percentage'];
            $severity = $fillPercent >= 98 ? 'WARNING' : 'INFO';

            $alerts[] = [
                'type' => 'OVERFILL_WARNING',
                'severity' => $severity,
                'depot_id' => $row['depot_id'],
                'depot_name' => $row['depot_name'],
                'fuel_type_name' => $row['fuel_type_name'],
                'message' => "Резервуар почти полон ({$fillPercent}%): {$row['depot_name']} - {$row['tank_number']}",
                'details' => [
                    'tank_id' => $row['tank_id'],
                    'tank_number' => $row['tank_number'],
                    'current_stock_liters' => (float)$row['current_stock_liters'],
                    'capacity_liters' => (float)$row['capacity_liters'],
                    'fill_percentage' => $fillPercent
                ],
                'created_at' => date('Y-m-d H:i:s')
            ];
        }

        return $alerts;
    }

    /**
     * Get alert summary (counts by severity)
     *
     * @return array Alert counts
     */
    public static function getAlertSummary(): array
    {
        $alerts = self::getActiveAlerts();

        $summary = [
            'CATASTROPHE' => 0,
            'CRITICAL' => 0,
            'MUST_ORDER' => 0,
            'WARNING' => 0,
            'INFO' => 0,
            'total' => count($alerts)
        ];

        foreach ($alerts as $alert) {
            if (isset($summary[$alert['severity']])) {
                $summary[$alert['severity']]++;
            }
        }

        return $summary;
    }

    /**
     * Get alerts for specific depot
     *
     * @param int $depotId Depot ID
     * @return array Alerts for this depot
     */
    public static function getDepotAlerts(int $depotId): array
    {
        $allAlerts = self::getActiveAlerts();

        return array_filter($allAlerts, function($alert) use ($depotId) {
            return isset($alert['depot_id']) && $alert['depot_id'] == $depotId;
        });
    }

    /**
     * Check if depot needs immediate attention
     *
     * @param int $depotId Depot ID
     * @return bool True if has CATASTROPHE or CRITICAL alerts
     */
    public static function needsImmediateAttention(int $depotId): bool
    {
        $alerts = self::getDepotAlerts($depotId);

        foreach ($alerts as $alert) {
            if (in_array($alert['severity'], ['CATASTROPHE', 'CRITICAL'])) {
                return true;
            }
        }

        return false;
    }
}
