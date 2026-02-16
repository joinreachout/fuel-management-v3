<?php

namespace App\Controllers;

use App\Core\Response;
use App\Services\ReportService;

/**
 * Report Controller
 * Handles report generation requests
 */
class ReportController
{
    /**
     * GET /api/reports/daily-stock?date=YYYY-MM-DD
     * Get daily stock report
     */
    public function dailyStock(): void
    {
        try {
            $date = $_GET['date'] ?? null;
            $report = ReportService::getDailyStockReport($date);

            Response::json([
                'success' => true,
                'data' => $report
            ]);
        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'error' => 'Failed to generate daily stock report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/reports/inventory-summary
     * Get inventory summary by fuel type
     */
    public function inventorySummary(): void
    {
        try {
            $report = ReportService::getInventorySummary();

            Response::json([
                'success' => true,
                'data' => $report
            ]);
        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'error' => 'Failed to generate inventory summary: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/reports/station-performance
     * Get station performance metrics
     */
    public function stationPerformance(): void
    {
        try {
            $report = ReportService::getStationPerformance();

            Response::json([
                'success' => true,
                'data' => $report
            ]);
        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'error' => 'Failed to generate station performance report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/reports/low-stock
     * Get low stock report
     */
    public function lowStock(): void
    {
        try {
            $report = ReportService::getLowStockReport();

            Response::json([
                'success' => true,
                'data' => $report
            ]);
        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'error' => 'Failed to generate low stock report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/reports/capacity-utilization
     * Get capacity utilization report
     */
    public function capacityUtilization(): void
    {
        try {
            $report = ReportService::getCapacityUtilization();

            Response::json([
                'success' => true,
                'data' => $report
            ]);
        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'error' => 'Failed to generate capacity utilization report: ' . $e->getMessage()
            ], 500);
        }
    }
}
