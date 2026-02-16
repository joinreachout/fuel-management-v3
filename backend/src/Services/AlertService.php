<?php

namespace App\Services;

use App\Core\Database;

/**
 * Alert Service
 * Generates alerts for low stock, critical situations, and anomalies
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

        // 1. Critical stock levels (below minimum days)
        $criticalTanks = ForecastService::getCriticalTanks();
        foreach ($criticalTanks as $tank) {
            $daysLeft = (float)$tank['days_until_empty'];
            $severity = $daysLeft <= 1 ? 'CATASTROPHE' :
                       ($daysLeft <= 3 ? 'CRITICAL' :
                       ($daysLeft <= 7 ? 'WARNING' : 'INFO'));

            $alerts[] = [
                'type' => 'LOW_STOCK',
                'severity' => $severity,
                'depot_id' => $tank['depot_id'],
                'depot_name' => $tank['depot_name'],
                'fuel_type_name' => $tank['fuel_type_name'],
                'message' => "Low stock alert: {$tank['depot_name']} - {$tank['fuel_type_name']}",
                'details' => [
                    'current_stock_liters' => (float)$tank['current_stock_liters'],
                    'days_until_empty' => $daysLeft,
                    'daily_consumption' => (float)$tank['daily_consumption_liters']
                ],
                'created_at' => date('Y-m-d H:i:s')
            ];
        }

        // 2. Tanks at or below reorder point
        $reorderAlerts = self::getReorderAlerts();
        $alerts = array_merge($alerts, $reorderAlerts);

        // 3. Overfilled tanks (>95% capacity)
        $overfillAlerts = self::getOverfillAlerts();
        $alerts = array_merge($alerts, $overfillAlerts);

        // Sort by severity
        usort($alerts, function($a, $b) {
            $severityOrder = ['CATASTROPHE' => 1, 'CRITICAL' => 2, 'WARNING' => 3, 'INFO' => 4];
            return $severityOrder[$a['severity']] <=> $severityOrder[$b['severity']];
        });

        return $alerts;
    }

    /**
     * Get reorder alerts (stock at or below reorder point)
     *
     * @return array Reorder alerts
     */
    public static function getReorderAlerts(): array
    {
        $results = Database::fetchAll("
            SELECT
                dt.depot_id,
                d.name as depot_name,
                dt.fuel_type_id,
                ft.name as fuel_type_name,
                SUM(dt.current_stock_liters) as total_stock_liters,
                sp.reorder_point_liters,
                sp.daily_consumption_liters
            FROM depot_tanks dt
            LEFT JOIN depots d ON dt.depot_id = d.id
            LEFT JOIN fuel_types ft ON dt.fuel_type_id = ft.id
            LEFT JOIN stock_policies sp ON dt.depot_id = sp.depot_id AND dt.fuel_type_id = sp.fuel_type_id
            WHERE sp.reorder_point_liters IS NOT NULL
            GROUP BY dt.depot_id, dt.fuel_type_id, d.name, ft.name, sp.reorder_point_liters, sp.daily_consumption_liters
            HAVING total_stock_liters <= sp.reorder_point_liters
        ");

        $alerts = [];
        foreach ($results as $row) {
            $alerts[] = [
                'type' => 'REORDER_NEEDED',
                'severity' => 'MUST_ORDER',
                'depot_id' => $row['depot_id'],
                'depot_name' => $row['depot_name'],
                'fuel_type_name' => $row['fuel_type_name'],
                'message' => "Reorder required: {$row['depot_name']} - {$row['fuel_type_name']}",
                'details' => [
                    'total_stock_liters' => (float)$row['total_stock_liters'],
                    'reorder_point_liters' => (float)$row['reorder_point_liters'],
                    'daily_consumption' => (float)$row['daily_consumption_liters']
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
                ROUND((dt.current_stock_liters / dt.capacity_liters * 100), 1) as fill_percentage
            FROM depot_tanks dt
            LEFT JOIN depots d ON dt.depot_id = d.id
            LEFT JOIN fuel_types ft ON dt.fuel_type_id = ft.id
            WHERE (dt.current_stock_liters / dt.capacity_liters) > 0.95
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
                'message' => "Tank near capacity: {$row['depot_name']} - {$row['tank_number']}",
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
            $summary[$alert['severity']]++;
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
