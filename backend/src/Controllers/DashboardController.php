<?php

namespace App\Controllers;

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
}
