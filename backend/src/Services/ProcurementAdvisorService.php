<?php

namespace App\Services;

use App\Core\Database;

/**
 * Procurement Advisor Service
 * Generates procurement recommendations based on:
 * - Current stock levels
 * - Consumption forecasts
 * - Supplier availability and pricing
 * - Delivery times
 */
class ProcurementAdvisorService
{
    /**
     * Get upcoming shortages - stations/depots that will run out soon
     * Returns list sorted by urgency
     *
     * @param int|null $daysThreshold Show shortages within N days (default: 14)
     * @return array List of upcoming shortages with recommendations
     */
    public static function getUpcomingShortages(?int $daysThreshold = 14): array
    {
        // Get all depots with their fuel stock and consumption rates
        $sql = "
            SELECT
                s.id as station_id,
                s.name as station_name,
                s.code as station_code,
                d.id as depot_id,
                d.name as depot_name,
                dt.fuel_type_id,
                ft.name as fuel_type_name,
                ft.code as fuel_type_code,
                ft.density,
                SUM(dt.current_stock_liters) as current_stock_liters,
                SUM(dt.capacity_liters) as capacity_liters,
                sp.liters_per_day as daily_consumption_liters,
                pol.min_level_liters,
                pol.critical_level_liters,
                pol.target_level_liters,
                ROUND(SUM(dt.current_stock_liters) / dt.capacity_liters * 100, 1) as fill_percentage,
                ROUND(SUM(dt.current_stock_liters) / sp.liters_per_day, 1) as days_until_empty
            FROM depot_tanks dt
            INNER JOIN depots d ON dt.depot_id = d.id
            INNER JOIN stations s ON d.station_id = s.id
            INNER JOIN fuel_types ft ON dt.fuel_type_id = ft.id
            LEFT JOIN sales_params sp ON dt.depot_id = sp.depot_id
                AND dt.fuel_type_id = sp.fuel_type_id
                AND (sp.effective_to IS NULL OR sp.effective_to >= CURDATE())
            LEFT JOIN stock_policies pol ON dt.depot_id = pol.depot_id
                AND dt.fuel_type_id = pol.fuel_type_id
            WHERE sp.liters_per_day > 0
            GROUP BY s.id, s.name, s.code, d.id, d.name, dt.fuel_type_id, ft.name, ft.code, ft.density,
                     sp.liters_per_day, pol.min_level_liters, pol.critical_level_liters, pol.target_level_liters
            HAVING days_until_empty <= ?
                OR current_stock_liters <= IFNULL(pol.min_level_liters, 0)
            ORDER BY days_until_empty ASC
        ";

        $results = Database::fetchAll($sql, [$daysThreshold]);

        $shortages = [];
        foreach ($results as $row) {
            $currentStockLiters = (float)$row['current_stock_liters'];
            $capacityLiters = (float)$row['capacity_liters'];
            $dailyConsumption = (float)$row['daily_consumption_liters'];
            $daysLeft = (float)$row['days_until_empty'];
            $density = (float)$row['density'];

            // Convert to tons
            $currentStockTons = $currentStockLiters * $density / 1000;
            $capacityTons = $capacityLiters * $density / 1000;
            $dailyConsumptionTons = $dailyConsumption * $density / 1000;

            // Determine urgency level
            $urgency = self::calculateUrgency(
                $currentStockLiters,
                (float)($row['min_level_liters'] ?? 0),
                (float)($row['critical_level_liters'] ?? 0),
                $daysLeft
            );

            // Calculate recommended order quantity
            $targetLevel = (float)($row['target_level_liters'] ?? $capacityLiters * 0.8);
            $recommendedOrderLiters = max(0, $targetLevel - $currentStockLiters);
            $recommendedOrderTons = $recommendedOrderLiters * $density / 1000;

            // Get best supplier for this fuel type
            $bestSupplier = self::getBestSupplier($row['fuel_type_id'], $urgency);

            // Calculate critical date and last order date
            $criticalDate = null;
            $lastOrderDate = null;
            $daysUntilCritical = null;

            if ($daysLeft > 0) {
                $criticalDate = date('Y-m-d', strtotime("+{$daysLeft} days"));

                // Consider supplier delivery time
                $deliveryDays = $bestSupplier['avg_delivery_days'] ?? 7;
                $safetyBuffer = 2; // Extra buffer days
                $orderLeadTime = $deliveryDays + $safetyBuffer;

                $daysUntilMustOrder = max(0, $daysLeft - $orderLeadTime);
                $lastOrderDate = date('Y-m-d', strtotime("+{$daysUntilMustOrder} days"));
                $daysUntilCritical = max(0, $daysLeft);
            }

            $shortages[] = [
                'station_id' => $row['station_id'],
                'station_name' => $row['station_name'],
                'station_code' => $row['station_code'],
                'depot_id' => $row['depot_id'],
                'depot_name' => $row['depot_name'],
                'fuel_type_id' => $row['fuel_type_id'],
                'fuel_type_name' => $row['fuel_type_name'],
                'fuel_type_code' => $row['fuel_type_code'],
                'urgency' => $urgency,
                'days_left' => $daysLeft,
                'days_until_critical' => $daysUntilCritical,
                'critical_date' => $criticalDate,
                'last_order_date' => $lastOrderDate,
                'current_stock_tons' => round($currentStockTons, 2),
                'current_stock_liters' => round($currentStockLiters, 2),
                'capacity_tons' => round($capacityTons, 2),
                'fill_percentage' => (float)$row['fill_percentage'],
                'daily_consumption_tons' => round($dailyConsumptionTons, 2),
                'recommended_order_tons' => round($recommendedOrderTons, 2),
                'recommended_order_liters' => round($recommendedOrderLiters, 2),
                'best_supplier' => $bestSupplier,
                'created_at' => date('Y-m-d H:i:s')
            ];
        }

        return $shortages;
    }

    /**
     * Calculate urgency level based on stock and time
     *
     * @param float $currentStock Current stock in liters
     * @param float $minLevel Minimum level threshold
     * @param float $criticalLevel Critical level threshold
     * @param float $daysLeft Days until empty
     * @return string Urgency level
     */
    private static function calculateUrgency(
        float $currentStock,
        float $minLevel,
        float $criticalLevel,
        float $daysLeft
    ): string {
        // CATASTROPHE - already below critical level
        if ($criticalLevel > 0 && $currentStock <= $criticalLevel) {
            return 'CATASTROPHE';
        }

        // CRITICAL - below minimum or less than 2 days left
        if (($minLevel > 0 && $currentStock <= $minLevel) || $daysLeft <= 2) {
            return 'CRITICAL';
        }

        // MUST_ORDER - less than 5 days left
        if ($daysLeft <= 5) {
            return 'MUST_ORDER';
        }

        // WARNING - less than 7 days left
        if ($daysLeft <= 7) {
            return 'WARNING';
        }

        // PLANNED - within 14 days
        return 'PLANNED';
    }

    /**
     * Get best supplier for fuel type based on ranking
     *
     * @param int $fuelTypeId Fuel type ID
     * @param string $urgency Urgency level
     * @return array|null Best supplier info
     */
    private static function getBestSupplier(int $fuelTypeId, string $urgency): ?array
    {
        // Get active suppliers sorted by priority and auto_score
        // Priority: 1 = highest, auto_score: higher = better
        $sql = "
            SELECT
                s.id,
                s.name,
                s.departure_station,
                s.priority,
                s.auto_score,
                s.avg_delivery_days,
                sp.price_per_ton,
                sp.currency
            FROM suppliers s
            LEFT JOIN supplier_prices sp ON s.id = sp.supplier_id
                AND sp.fuel_type_id = ?
                AND sp.is_active = 1
            WHERE s.is_active = 1
            ORDER BY
                s.priority ASC,
                s.auto_score DESC,
                sp.price_per_ton ASC
            LIMIT 1
        ";

        $result = Database::fetchAll($sql, [$fuelTypeId]);

        if (empty($result)) {
            return null;
        }

        $supplier = $result[0];

        return [
            'id' => $supplier['id'],
            'name' => $supplier['name'],
            'departure_station' => $supplier['departure_station'],
            'priority' => $supplier['priority'],
            'score' => (float)$supplier['auto_score'],
            'avg_delivery_days' => (int)$supplier['avg_delivery_days'],
            'price_per_ton' => $supplier['price_per_ton'] ? (float)$supplier['price_per_ton'] : null,
            'currency' => $supplier['currency']
        ];
    }

    /**
     * Get procurement summary statistics
     *
     * @return array Summary stats
     */
    public static function getProcurementSummary(): array
    {
        $shortages = self::getUpcomingShortages(14);

        $summary = [
            'total_shortages' => count($shortages),
            'by_urgency' => [
                'CATASTROPHE' => 0,
                'CRITICAL' => 0,
                'MUST_ORDER' => 0,
                'WARNING' => 0,
                'PLANNED' => 0
            ],
            'total_value_estimate' => 0,
            'avg_lead_time_days' => 0
        ];

        $totalLeadTime = 0;
        $supplierCount = 0;

        foreach ($shortages as $shortage) {
            // Count by urgency
            if (isset($summary['by_urgency'][$shortage['urgency']])) {
                $summary['by_urgency'][$shortage['urgency']]++;
            }

            // Estimate total value
            if ($shortage['best_supplier'] && isset($shortage['best_supplier']['price_per_ton'])) {
                $orderValue = $shortage['recommended_order_tons'] * $shortage['best_supplier']['price_per_ton'];
                $summary['total_value_estimate'] += $orderValue;
            }

            // Calculate average lead time
            if ($shortage['best_supplier']) {
                $totalLeadTime += $shortage['best_supplier']['avg_delivery_days'];
                $supplierCount++;
            }
        }

        if ($supplierCount > 0) {
            $summary['avg_lead_time_days'] = round($totalLeadTime / $supplierCount, 1);
        }

        $summary['total_value_estimate'] = round($summary['total_value_estimate'], 2);

        // Calculate critical counts
        $summary['mandatory_orders'] = $summary['by_urgency']['CATASTROPHE'] + $summary['by_urgency']['CRITICAL'];
        $summary['recommended_orders'] = $summary['by_urgency']['MUST_ORDER'] + $summary['by_urgency']['WARNING'];

        return $summary;
    }

    /**
     * Get supplier recommendations ranked by multiple factors
     *
     * @param int $fuelTypeId Fuel type ID
     * @param float $requiredTons Required quantity in tons
     * @param string $urgency Urgency level
     * @return array List of suppliers with composite scores
     */
    public static function getSupplierRecommendations(
        int $fuelTypeId,
        float $requiredTons,
        string $urgency
    ): array {
        $sql = "
            SELECT
                s.id,
                s.name,
                s.departure_station,
                s.priority,
                s.auto_score,
                s.avg_delivery_days,
                sp.price_per_ton,
                sp.currency,
                sp.min_quantity_tons,
                sp.max_quantity_tons
            FROM suppliers s
            LEFT JOIN supplier_prices sp ON s.id = sp.supplier_id
                AND sp.fuel_type_id = ?
                AND sp.is_active = 1
            WHERE s.is_active = 1
                AND (sp.min_quantity_tons IS NULL OR sp.min_quantity_tons <= ?)
                AND (sp.max_quantity_tons IS NULL OR sp.max_quantity_tons >= ?)
            ORDER BY
                s.priority ASC,
                s.auto_score DESC,
                sp.price_per_ton ASC
        ";

        $results = Database::fetchAll($sql, [$fuelTypeId, $requiredTons, $requiredTons]);

        $recommendations = [];
        foreach ($results as $supplier) {
            // Calculate composite score
            $priorityScore = (10 - (int)$supplier['priority']) * 10; // Higher priority = higher score
            $autoScore = (float)$supplier['auto_score'] * 10;
            $urgencyScore = self::getUrgencyScore($urgency, (int)$supplier['avg_delivery_days']);

            $compositeScore = $priorityScore + $autoScore + $urgencyScore;

            $recommendations[] = [
                'id' => $supplier['id'],
                'name' => $supplier['name'],
                'departure_station' => $supplier['departure_station'],
                'priority' => $supplier['priority'],
                'auto_score' => (float)$supplier['auto_score'],
                'avg_delivery_days' => (int)$supplier['avg_delivery_days'],
                'price_per_ton' => $supplier['price_per_ton'] ? (float)$supplier['price_per_ton'] : null,
                'currency' => $supplier['currency'],
                'composite_score' => round($compositeScore, 2),
                'is_recommended' => $compositeScore >= 50
            ];
        }

        // Sort by composite score descending
        usort($recommendations, function($a, $b) {
            return $b['composite_score'] <=> $a['composite_score'];
        });

        return $recommendations;
    }

    /**
     * Calculate urgency score based on delivery time
     *
     * @param string $urgency Urgency level
     * @param int $deliveryDays Delivery days
     * @return float Score
     */
    private static function getUrgencyScore(string $urgency, int $deliveryDays): float
    {
        $urgencyWeight = [
            'CATASTROPHE' => 50,
            'CRITICAL' => 40,
            'MUST_ORDER' => 30,
            'WARNING' => 20,
            'PLANNED' => 10
        ];

        $baseScore = $urgencyWeight[$urgency] ?? 10;

        // Penalize slow delivery for urgent orders
        if (in_array($urgency, ['CATASTROPHE', 'CRITICAL', 'MUST_ORDER'])) {
            if ($deliveryDays <= 3) {
                return $baseScore + 20; // Fast delivery bonus
            } elseif ($deliveryDays <= 7) {
                return $baseScore;
            } else {
                return $baseScore - 20; // Slow delivery penalty
            }
        }

        return $baseScore;
    }
}
