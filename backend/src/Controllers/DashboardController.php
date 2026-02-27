<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\Response;
use App\Services\ReportService;
use App\Services\AlertService;
use App\Services\ForecastService;

/**
 * Dashboard Controller
 * Provides aggregated data for dashboard views
 */
class DashboardController
{
    /**
     * GET /api/dashboard/summary
     * Get dashboard summary with key metrics
     */
    public function summary(): void
    {
        try {
            $summary = ReportService::getDashboardSummary();

            Response::json([
                'success' => true,
                'data' => $summary
            ]);
        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'error' => 'Failed to fetch dashboard summary: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/dashboard/alerts
     * Get all active alerts
     */
    public function alerts(): void
    {
        try {
            $alerts = AlertService::getActiveAlerts();

            Response::json([
                'success' => true,
                'data' => $alerts,
                'count' => count($alerts)
            ]);
        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'error' => 'Failed to fetch alerts: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/dashboard/alerts/summary
     * Get alert summary (counts by severity)
     */
    public function alertSummary(): void
    {
        try {
            $summary = AlertService::getAlertSummary();

            Response::json([
                'success' => true,
                'data' => $summary
            ]);
        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'error' => 'Failed to fetch alert summary: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/dashboard/critical-tanks
     * Get tanks requiring urgent attention
     */
    public function criticalTanks(): void
    {
        try {
            $tanks = ForecastService::getCriticalTanks();

            Response::json([
                'success' => true,
                'data' => $tanks,
                'count' => count($tanks)
            ]);
        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'error' => 'Failed to fetch critical tanks: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/dashboard/forecast
     * Get fuel level forecast by station/region
     * Params: level, region, station_id, fuel_type_id, days
     */
    public function forecast(): void
    {
        try {
            $level = $_GET['level'] ?? 'station';
            $region = $_GET['region'] ?? null;
            $stationId = isset($_GET['station_id']) ? (int)$_GET['station_id'] : null;
            $fuelTypeId = isset($_GET['fuel_type_id']) ? (int)$_GET['fuel_type_id'] : null;
            $days = isset($_GET['days']) ? (int)$_GET['days'] : 30;

            $forecast = ForecastService::getStationForecast($level, $region, $stationId, $fuelTypeId, $days);

            Response::json([
                'success' => true,
                'data' => $forecast
            ]);
        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'error' => 'Failed to fetch forecast: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/dashboard/stockout-forecast
     * Returns per-station days-until-empty data for the Stockout Forecast widget.
     * Only includes depots that have daily consumption data (sales_params).
     */
    public function stockoutForecast(): void
    {
        try {
            // Per-station+fuel: aggregate all depot tanks, join with sales_params
            // days_until_empty = SUM(current_stock) / liters_per_day
            $sql = "
                SELECT
                    s.id   AS station_id,
                    s.name AS station_name,
                    ft.id   AS fuel_type_id,
                    ft.name AS fuel_type_name,
                    ROUND(SUM(dt.current_stock_liters), 0)   AS stock_liters,
                    COALESCE(SUM(sp.liters_per_day), 0)      AS daily_liters,
                    CASE
                        WHEN COALESCE(SUM(sp.liters_per_day), 0) > 0
                        THEN ROUND(SUM(dt.current_stock_liters) / SUM(sp.liters_per_day), 1)
                        ELSE NULL
                    END AS days_until_empty,
                    CASE
                        WHEN COALESCE(SUM(sp.liters_per_day), 0) > 0
                        THEN DATE_ADD(CURDATE(),
                             INTERVAL FLOOR(SUM(dt.current_stock_liters) / SUM(sp.liters_per_day)) DAY)
                        ELSE NULL
                    END AS empty_date
                FROM depot_tanks dt
                JOIN depots d     ON dt.depot_id    = d.id
                JOIN stations s   ON d.station_id   = s.id
                JOIN fuel_types ft ON dt.fuel_type_id = ft.id
                LEFT JOIN sales_params sp
                    ON sp.depot_id = d.id
                   AND sp.fuel_type_id = dt.fuel_type_id
                   AND (sp.effective_to IS NULL OR sp.effective_to >= CURDATE())
                GROUP BY s.id, s.name, ft.id, ft.name
                HAVING daily_liters > 0 AND days_until_empty IS NOT NULL
                ORDER BY days_until_empty ASC
            ";

            $rows = Database::fetchAll($sql);

            // Top 10 nearest stockouts
            $top10 = array_slice(array_map(fn($r) => [
                'station_id'       => (int)$r['station_id'],
                'station_name'     => $r['station_name'],
                'fuel_type_id'     => (int)$r['fuel_type_id'],
                'fuel_type_name'   => $r['fuel_type_name'],
                'stock_liters'     => (float)$r['stock_liters'],
                'daily_liters'     => (float)$r['daily_liters'],
                'days_until_empty' => (float)$r['days_until_empty'],
                'empty_date'       => $r['empty_date'],
            ], $rows), 0, 10);

            // By-fuel-type: nearest stockout per fuel type + count within 45 days
            $byFuel = [];
            foreach ($rows as $r) {
                $fid = (int)$r['fuel_type_id'];
                $days = (float)$r['days_until_empty'];
                if (!isset($byFuel[$fid])) {
                    $byFuel[$fid] = [
                        'fuel_type_id'        => $fid,
                        'fuel_type_name'      => $r['fuel_type_name'],
                        'nearest_days'        => $days,
                        'nearest_station'     => $r['station_name'],
                        'nearest_empty_date'  => $r['empty_date'],
                        'count_in_45_days'    => 0,
                    ];
                }
                if ($days <= 45) {
                    $byFuel[$fid]['count_in_45_days']++;
                }
            }

            Response::json([
                'success'      => true,
                'top_10'       => $top10,
                'by_fuel_type' => array_values($byFuel),
            ]);
        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'error'   => 'Failed to fetch stockout forecast: ' . $e->getMessage()
            ], 500);
        }
    }
}
