<?php

namespace App\Services;

use App\Core\Database;

/**
 * Report Service
 * Generates various reports and analytics
 */
class ReportService
{
    /**
     * Get daily stock report for all depots
     *
     * @param string|null $date Date in Y-m-d format (default: today)
     * @return array Daily stock report
     */
    public static function getDailyStockReport(?string $date = null): array
    {
        $date = $date ?? date('Y-m-d');

        $depots = Database::fetchAll("
            SELECT
                d.id as depot_id,
                d.name as depot_name,
                d.code as depot_code,
                s.name as station_name,
                ft.id as fuel_type_id,
                ft.name as fuel_type_name,
                ft.code as fuel_type_code,
                SUM(dt.capacity_liters) as total_capacity_liters,
                SUM(dt.current_stock_liters) as total_stock_liters,
                ROUND(SUM(dt.current_stock_liters * ft.density / 1000), 2) as total_stock_tons,
                ROUND((SUM(dt.current_stock_liters) / NULLIF(SUM(dt.capacity_liters), 0) * 100), 1) as fill_percentage
            FROM depot_tanks dt
            LEFT JOIN depots d ON dt.depot_id = d.id
            LEFT JOIN stations s ON d.station_id = s.id
            LEFT JOIN fuel_types ft ON dt.fuel_type_id = ft.id
            GROUP BY d.id, d.name, d.code, s.name, ft.id, ft.name, ft.code, ft.density
            ORDER BY d.name, ft.name
        ");

        return [
            'report_date' => $date,
            'report_type' => 'daily_stock',
            'data' => $depots,
            'count' => count($depots),
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Get inventory summary (totals by fuel type)
     *
     * @return array Inventory summary
     */
    public static function getInventorySummary(): array
    {
        $summary = Database::fetchAll("
            SELECT
                ft.id as fuel_type_id,
                ft.name as fuel_type_name,
                ft.code as fuel_type_code,
                ft.density,
                COUNT(DISTINCT dt.depot_id) as depot_count,
                COUNT(dt.id) as tank_count,
                SUM(dt.capacity_liters) as total_capacity_liters,
                SUM(dt.current_stock_liters) as total_stock_liters,
                ROUND(SUM(dt.current_stock_liters * ft.density / 1000), 2) as total_stock_tons,
                ROUND((SUM(dt.current_stock_liters) / NULLIF(SUM(dt.capacity_liters), 0) * 100), 1) as avg_fill_percentage
            FROM depot_tanks dt
            LEFT JOIN fuel_types ft ON dt.fuel_type_id = ft.id
            GROUP BY ft.id, ft.name, ft.code, ft.density
            ORDER BY total_stock_liters DESC
        ");

        // Calculate grand totals
        $grandTotals = [
            'total_capacity_liters' => 0,
            'total_stock_liters' => 0,
            'total_stock_tons' => 0
        ];

        foreach ($summary as $row) {
            $grandTotals['total_capacity_liters'] += (float)$row['total_capacity_liters'];
            $grandTotals['total_stock_liters'] += (float)$row['total_stock_liters'];
            $grandTotals['total_stock_tons'] += (float)$row['total_stock_tons'];
        }

        return [
            'report_type' => 'inventory_summary',
            'by_fuel_type' => $summary,
            'grand_totals' => $grandTotals,
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Get station performance report
     *
     * @return array Station performance metrics
     */
    public static function getStationPerformance(): array
    {
        $stations = Database::fetchAll("
            SELECT
                s.id as station_id,
                s.name as station_name,
                s.code as station_code,
                COUNT(DISTINCT d.id) as depot_count,
                COUNT(DISTINCT dt.id) as tank_count,
                SUM(dt.capacity_liters) as total_capacity_liters,
                SUM(dt.current_stock_liters) as total_stock_liters,
                ROUND((SUM(dt.current_stock_liters) / NULLIF(SUM(dt.capacity_liters), 0) * 100), 1) as avg_fill_percentage
            FROM stations s
            LEFT JOIN depots d ON s.id = d.station_id
            LEFT JOIN depot_tanks dt ON d.id = dt.depot_id
            GROUP BY s.id, s.name, s.code
            ORDER BY total_stock_liters DESC
        ");

        return [
            'report_type' => 'station_performance',
            'data' => $stations,
            'count' => count($stations),
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Get low stock report (tanks below minimum)
     * Uses stock_policies thresholds and sales_params consumption
     *
     * @return array Low stock report
     */
    public static function getLowStockReport(): array
    {
        $lowStock = Database::fetchAll("
            SELECT
                dt.id as tank_id,
                dt.depot_id,
                d.name as depot_name,
                d.code as depot_code,
                s.name as station_name,
                dt.tank_number,
                ft.name as fuel_type_name,
                ft.code as fuel_type_code,
                dt.capacity_liters,
                dt.current_stock_liters,
                ROUND((dt.current_stock_liters / dt.capacity_liters * 100), 1) as fill_percentage,
                pol.min_level_liters,
                pol.critical_level_liters,
                sp.liters_per_day as daily_consumption_liters,
                ROUND(dt.current_stock_liters / sp.liters_per_day, 1) as days_until_empty
            FROM depot_tanks dt
            LEFT JOIN depots d ON dt.depot_id = d.id
            LEFT JOIN stations s ON d.station_id = s.id
            LEFT JOIN fuel_types ft ON dt.fuel_type_id = ft.id
            LEFT JOIN stock_policies pol ON dt.depot_id = pol.depot_id AND dt.fuel_type_id = pol.fuel_type_id
            LEFT JOIN sales_params sp ON dt.depot_id = sp.depot_id
                AND dt.fuel_type_id = sp.fuel_type_id
                AND (sp.effective_to IS NULL OR sp.effective_to >= CURDATE())
            WHERE sp.liters_per_day > 0
            AND pol.min_level_liters IS NOT NULL
            AND dt.current_stock_liters <= pol.min_level_liters
            ORDER BY days_until_empty ASC
        ");

        return [
            'report_type' => 'low_stock',
            'data' => $lowStock,
            'count' => count($lowStock),
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Get capacity utilization report
     *
     * @return array Capacity utilization by depot
     */
    public static function getCapacityUtilization(): array
    {
        $utilization = Database::fetchAll("
            SELECT
                d.id as depot_id,
                d.name as depot_name,
                d.code as depot_code,
                s.name as station_name,
                COUNT(dt.id) as tank_count,
                SUM(dt.capacity_liters) as total_capacity_liters,
                SUM(dt.current_stock_liters) as total_stock_liters,
                ROUND((SUM(dt.current_stock_liters) / NULLIF(SUM(dt.capacity_liters), 0) * 100), 1) as utilization_percentage,
                SUM(dt.capacity_liters) - SUM(dt.current_stock_liters) as available_space_liters
            FROM depots d
            LEFT JOIN stations s ON d.station_id = s.id
            LEFT JOIN depot_tanks dt ON d.id = dt.depot_id
            GROUP BY d.id, d.name, d.code, s.name
            ORDER BY utilization_percentage DESC
        ");

        return [
            'report_type' => 'capacity_utilization',
            'data' => $utilization,
            'count' => count($utilization),
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Get dashboard summary (key metrics)
     *
     * @return array Dashboard metrics
     */
    public static function getDashboardSummary(): array
    {
        // Total inventory
        $inventory = Database::fetchAll("
            SELECT
                COUNT(DISTINCT s.id) as total_stations,
                COUNT(DISTINCT d.id) as total_depots,
                COUNT(dt.id) as total_tanks,
                SUM(dt.capacity_liters) as total_capacity_liters,
                SUM(dt.current_stock_liters) as total_stock_liters,
                ROUND((SUM(dt.current_stock_liters) / NULLIF(SUM(dt.capacity_liters), 0) * 100), 1) as avg_fill_percentage
            FROM depot_tanks dt
            LEFT JOIN depots d ON dt.depot_id = d.id
            LEFT JOIN stations s ON d.station_id = s.id
        ");

        $inventory = $inventory[0];

        // Alert summary
        $alertSummary = AlertService::getAlertSummary();

        // Critical tanks count
        $criticalTanks = ForecastService::getCriticalTanks();

        return [
            'inventory' => [
                'total_stations' => (int)$inventory['total_stations'],
                'total_depots' => (int)$inventory['total_depots'],
                'total_tanks' => (int)$inventory['total_tanks'],
                'total_capacity_liters' => (float)$inventory['total_capacity_liters'],
                'total_stock_liters' => (float)$inventory['total_stock_liters'],
                'avg_fill_percentage' => (float)$inventory['avg_fill_percentage']
            ],
            'alerts' => $alertSummary,
            'critical_tanks_count' => count($criticalTanks),
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }
}
