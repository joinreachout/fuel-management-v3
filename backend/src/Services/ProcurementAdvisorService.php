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

            // Get best supplier for this fuel type and station (needed for delivery time calculation)
            $bestSupplier = self::getBestSupplier($row['fuel_type_id'], $row['station_id'], $urgency);

            // Calculate recommended order quantity with proper logic
            $targetLevel = (float)($row['target_level_liters'] ?? $capacityLiters * 0.8);
            $deliveryDays = $bestSupplier['avg_delivery_days'] ?? 7;
            $safetyBufferDays = 2; // Extra buffer days for delays

            // Calculate consumption during delivery period
            $consumptionDuringDelivery = $dailyConsumption * ($deliveryDays + $safetyBufferDays);

            // Recommended order = What we need to reach target + What will be consumed during delivery - What we have now
            // This ensures we'll have target_level when order arrives
            $recommendedOrderLiters = max(0,
                $targetLevel + $consumptionDuringDelivery - $currentStockLiters
            );

            // Check if order exceeds capacity - if so, cap at capacity
            $maxOrderLiters = $capacityLiters - $currentStockLiters + $consumptionDuringDelivery;
            $recommendedOrderLiters = min($recommendedOrderLiters, $maxOrderLiters);

            $recommendedOrderTons = $recommendedOrderLiters * $density / 1000;

            // Calculate critical date and last order date
            $criticalDate = null;
            $lastOrderDate = null;
            $daysUntilCritical = null;

            if ($daysLeft > 0) {
                $criticalDate = date('Y-m-d', strtotime("+{$daysLeft} days"));

                // Last order date: when we must order to receive delivery before critical
                $orderLeadTime = $deliveryDays + $safetyBufferDays;
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
                'calculation_details' => [
                    'target_level_tons' => round($targetLevel * $density / 1000, 2),
                    'consumption_during_delivery_tons' => round($consumptionDuringDelivery * $density / 1000, 2),
                    'delivery_days' => $deliveryDays,
                    'safety_buffer_days' => $safetyBufferDays,
                    'formula' => 'recommended = (target_level + consumption_during_delivery) - current_stock'
                ],
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
     * Get best supplier for fuel type and station based on ranking
     *
     * @param int $fuelTypeId Fuel type ID
     * @param int $stationId Station ID
     * @param string $urgency Urgency level
     * @return array|null Best supplier info with specific delivery time and price
     */
    private static function getBestSupplier(int $fuelTypeId, int $stationId, string $urgency): ?array
    {
        // Get active suppliers with their offers to this specific station
        // Priority: 1 = highest, auto_score: higher = better
        // Now includes actual delivery_days and prices for the specific route
        $sql = "
            SELECT
                s.id,
                s.name,
                s.departure_station,
                s.priority,
                s.auto_score,
                sso.delivery_days,
                sso.price_diesel_b7,
                sso.price_diesel_b10,
                sso.price_gas_92,
                sso.price_gas_95,
                sso.price_gas_98,
                sso.currency
            FROM suppliers s
            INNER JOIN supplier_station_offers sso
                ON s.id = sso.supplier_id
            WHERE sso.station_id = ?
              AND sso.is_active = 1
              AND s.is_active = 1
            ORDER BY
                s.priority ASC,
                sso.delivery_days ASC,
                s.auto_score DESC
            LIMIT 1
        ";

        $result = Database::fetchAll($sql, [$stationId]);

        if (empty($result)) {
            return null;
        }

        $supplier = $result[0];

        // Determine correct price based on fuel_type_id
        $pricePerTon = null;
        switch ($fuelTypeId) {
            case 25: // Diesel B7
                $pricePerTon = $supplier['price_diesel_b7'];
                break;
            case 33: // Diesel B10
                $pricePerTon = $supplier['price_diesel_b10'];
                break;
            case 23: // A-92
                $pricePerTon = $supplier['price_gas_92'];
                break;
            case 31: // A-95
                $pricePerTon = $supplier['price_gas_95'];
                break;
            case 32: // A-98
                $pricePerTon = $supplier['price_gas_98'];
                break;
            // Add more fuel types as needed
        }

        return [
            'id' => $supplier['id'],
            'name' => $supplier['name'],
            'departure_station' => $supplier['departure_station'],
            'priority' => $supplier['priority'],
            'score' => (float)$supplier['auto_score'],
            'avg_delivery_days' => (int)$supplier['delivery_days'], // Now actual delivery time for this route!
            'price_per_ton' => $pricePerTon ? (float)$pricePerTon : null,
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
     * Note: This version returns general supplier ranking without station-specific info
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
        // Get general supplier ranking
        // For station-specific recommendations, use getBestSupplier() with station_id
        $sql = "
            SELECT
                s.id,
                s.name,
                s.departure_station,
                s.priority,
                s.auto_score,
                AVG(sso.delivery_days) as avg_delivery_days,
                AVG(sso.price_diesel_b7) as avg_price_diesel_b7,
                AVG(sso.price_gas_92) as avg_price_gas_92,
                AVG(sso.price_gas_95) as avg_price_gas_95,
                sso.currency
            FROM suppliers s
            LEFT JOIN supplier_station_offers sso ON s.id = sso.supplier_id
                AND sso.is_active = 1
            WHERE s.is_active = 1
            GROUP BY s.id, s.name, s.departure_station, s.priority, s.auto_score, sso.currency
            ORDER BY
                s.priority ASC,
                s.auto_score DESC
        ";

        $results = Database::fetchAll($sql);

        $recommendations = [];
        foreach ($results as $supplier) {
            $avgDeliveryDays = $supplier['avg_delivery_days'] ? (int)$supplier['avg_delivery_days'] : 14;

            // Calculate composite score
            $priorityScore = (10 - (int)$supplier['priority']) * 10; // Higher priority = higher score
            $autoScore = (float)$supplier['auto_score'] * 10;
            $urgencyScore = self::getUrgencyScore($urgency, $avgDeliveryDays);

            $compositeScore = $priorityScore + $autoScore + $urgencyScore;

            // Select price based on fuel type
            $pricePerTon = null;
            switch ($fuelTypeId) {
                case 25: // Diesel B7
                case 33: // Diesel B10
                    $pricePerTon = $supplier['avg_price_diesel_b7'];
                    break;
                case 23: // A-92
                    $pricePerTon = $supplier['avg_price_gas_92'];
                    break;
                case 31: // A-95
                case 32: // A-98
                    $pricePerTon = $supplier['avg_price_gas_95'];
                    break;
            }

            $recommendations[] = [
                'id' => $supplier['id'],
                'name' => $supplier['name'],
                'departure_station' => $supplier['departure_station'],
                'priority' => $supplier['priority'],
                'auto_score' => (float)$supplier['auto_score'],
                'avg_delivery_days' => $avgDeliveryDays,
                'price_per_ton' => $pricePerTon ? (float)$pricePerTon : null,
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
