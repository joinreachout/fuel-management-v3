<?php

namespace App\Controllers;

use App\Services\ProcurementAdvisorService;

/**
 * Procurement Advisor Controller
 * Handles API requests for procurement recommendations
 */
class ProcurementAdvisorController
{
    /**
     * GET /api/procurement/upcoming-shortages
     * Get list of upcoming fuel shortages with recommendations
     *
     * Query params:
     * - days: Threshold for days (default: 14)
     *
     * @return void
     */
    public function getUpcomingShortages(): void
    {
        try {
            $days = isset($_GET['days']) ? (int)$_GET['days'] : 14;
            $days = max(1, min($days, 90)); // Limit between 1-90 days

            $shortages = ProcurementAdvisorService::getUpcomingShortages($days);

            http_response_code(200);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $shortages,
                'count' => count($shortages),
                'threshold_days' => $days
            ], JSON_PRETTY_PRINT);

        } catch (\Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ], JSON_PRETTY_PRINT);
        }
    }

    /**
     * GET /api/procurement/summary
     * Get procurement summary statistics
     *
     * @return void
     */
    public function getSummary(): void
    {
        try {
            $summary = ProcurementAdvisorService::getProcurementSummary();

            http_response_code(200);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $summary
            ], JSON_PRETTY_PRINT);

        } catch (\Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ], JSON_PRETTY_PRINT);
        }
    }

    /**
     * GET /api/procurement/best-suppliers
     * Get best supplier for each (station, fuel_type) combination.
     *
     * Query params:
     * - station_id: Filter to a specific station (optional)
     * - day_cost:   USD/ton penalty per delivery day, default 5 (optional)
     *
     * @return void
     */
    public function getBestSuppliers(): void
    {
        try {
            $stationId = isset($_GET['station_id']) ? (int)$_GET['station_id'] : null;
            $dayCost   = isset($_GET['day_cost'])   ? (float)$_GET['day_cost'] : 5.0;

            $data = ProcurementAdvisorService::getBestSuppliersTable($stationId, $dayCost);

            http_response_code(200);
            header('Content-Type: application/json');
            echo json_encode([
                'success'    => true,
                'data'       => $data,
                'count'      => count($data),
                'day_cost_usd' => $dayCost,
            ], JSON_PRETTY_PRINT);

        } catch (\Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error'   => $e->getMessage(),
            ], JSON_PRETTY_PRINT);
        }
    }

    /**
     * GET /api/procurement/supplier-recommendations
     * Get ranked supplier recommendations for fuel type
     *
     * Query params:
     * - fuel_type_id: Fuel type ID (required)
     * - required_tons: Required quantity in tons (required)
     * - urgency: Urgency level (optional, default: NORMAL)
     *
     * @return void
     */
    public function getSupplierRecommendations(): void
    {
        try {
            if (!isset($_GET['fuel_type_id']) || !isset($_GET['required_tons'])) {
                throw new \Exception('Missing required parameters: fuel_type_id, required_tons');
            }

            $fuelTypeId = (int)$_GET['fuel_type_id'];
            $requiredTons = (float)$_GET['required_tons'];
            $urgency = $_GET['urgency'] ?? 'NORMAL';

            $suppliers = ProcurementAdvisorService::getSupplierRecommendations(
                $fuelTypeId,
                $requiredTons,
                $urgency
            );

            http_response_code(200);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $suppliers,
                'count' => count($suppliers),
                'fuel_type_id' => $fuelTypeId,
                'required_tons' => $requiredTons,
                'urgency' => $urgency
            ], JSON_PRETTY_PRINT);

        } catch (\Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ], JSON_PRETTY_PRINT);
        }
    }
}
