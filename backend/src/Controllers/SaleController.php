<?php

namespace App\Controllers;

use App\Core\Response;
use App\Models\Sale;

/**
 * Sale Controller
 * Handles HTTP requests for sale resources
 */
class SaleController
{
    /**
     * GET /api/sales
     * Get all sales
     */
    public function index(): void
    {
        try {
            $sales = Sale::all();

            Response::json([
                'success' => true,
                'data' => $sales,
                'count' => count($sales)
            ]);
        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'error' => 'Failed to fetch sales: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/sales/{id}
     * Get single sale by ID
     */
    public function show(int $id): void
    {
        try {
            $sale = Sale::find($id);

            if (!$sale) {
                Response::json([
                    'success' => false,
                    'error' => 'Sale not found'
                ], 404);
                return;
            }

            Response::json([
                'success' => true,
                'data' => $sale
            ]);
        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'error' => 'Failed to fetch sale: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/sales/unpaid
     * Get unpaid sales
     */
    public function unpaid(): void
    {
        try {
            $sales = Sale::getUnpaid();

            Response::json([
                'success' => true,
                'data' => $sales,
                'count' => count($sales)
            ]);
        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'error' => 'Failed to fetch unpaid sales: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/sales/summary/fuel-type
     * Get sales summary by fuel type
     */
    public function summaryByFuelType(): void
    {
        try {
            $summary = Sale::getSummaryByFuelType();

            Response::json([
                'success' => true,
                'data' => $summary,
                'count' => count($summary)
            ]);
        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'error' => 'Failed to fetch sales summary: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/sales/summary/depot
     * Get sales summary by depot
     */
    public function summaryByDepot(): void
    {
        try {
            $summary = Sale::getSummaryByDepot();

            Response::json([
                'success' => true,
                'data' => $summary,
                'count' => count($summary)
            ]);
        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'error' => 'Failed to fetch sales summary: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/sales/recent
     * Get recent sales (last 30 days)
     */
    public function recent(): void
    {
        try {
            $days = $_GET['days'] ?? 30;
            $sales = Sale::getRecent((int)$days);

            Response::json([
                'success' => true,
                'data' => $sales,
                'count' => count($sales),
                'days' => (int)$days
            ]);
        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'error' => 'Failed to fetch recent sales: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/sales/daily-report
     * Get daily sales report
     */
    public function dailyReport(): void
    {
        try {
            $date = $_GET['date'] ?? date('Y-m-d');
            $report = Sale::getDailyReport($date);

            Response::json([
                'success' => true,
                'data' => $report,
                'count' => count($report),
                'date' => $date
            ]);
        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'error' => 'Failed to fetch daily report: ' . $e->getMessage()
            ], 500);
        }
    }
}
