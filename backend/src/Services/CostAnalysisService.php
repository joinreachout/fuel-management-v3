<?php

namespace App\Services;

use App\Core\Database;
use App\Utils\UnitConverter;

class CostAnalysisService
{
    public static function getCostAnalysis(): array
    {
        try {
            // Load all delivered orders with prices
            $orders = Database::fetchAll("
                SELECT
                    o.id,
                    o.delivery_date,
                    o.quantity_liters,
                    o.price_per_ton,
                    o.total_amount,
                    ft.id as fuel_type_id,
                    ft.name as fuel_type_name,
                    ft.code as fuel_type_code,
                    ft.density,
                    s.name as station_name,
                    r.name as region_name,
                    COALESCE(sup.name, 'Not assigned') as supplier_name
                FROM orders o
                JOIN fuel_types ft ON o.fuel_type_id = ft.id
                JOIN stations s ON o.station_id = s.id
                LEFT JOIN regions r ON s.region_id = r.id
                LEFT JOIN suppliers sup ON o.supplier_id = sup.id
                WHERE o.status = 'delivered'
                  AND o.quantity_liters > 0
                ORDER BY o.delivery_date DESC, ft.name
            ");

            if (empty($orders)) {
                return [
                    'success' => true,
                    'data' => [
                        'avg_cost_per_liter' => '0.00',
                        'total_monthly_spend' => 0,
                        'avg_cost_per_ton' => 0,
                        'fuel_breakdown' => [],
                        'price_trends' => []
                    ]
                ];
            }

            // Calculate statistics
            $fuelTypeStats = [];
            $totalCost = 0;
            $totalLiters = 0;
            $totalTons = 0;

            foreach ($orders as $order) {
                $fuelTypeId = (int)$order['fuel_type_id'];
                $liters = (float)$order['quantity_liters'];
                $density = (float)$order['density'];

                // Convert liters to tons
                $tons = UnitConverter::litersToTons($liters, $density);

                // Calculate cost
                $cost = 0;
                if ($order['total_amount']) {
                    $cost = (float)$order['total_amount'];
                } elseif ($order['price_per_ton'] && $tons > 0) {
                    $cost = $tons * (float)$order['price_per_ton'];
                }

                if ($cost <= 0) continue;

                // Aggregate by fuel type
                if (!isset($fuelTypeStats[$fuelTypeId])) {
                    $fuelTypeStats[$fuelTypeId] = [
                        'fuel_name' => $order['fuel_type_name'],
                        'fuel_code' => $order['fuel_type_code'],
                        'total_cost' => 0,
                        'total_liters' => 0,
                        'total_tons' => 0,
                        'order_count' => 0,
                    ];
                }

                $fuelTypeStats[$fuelTypeId]['total_cost'] += $cost;
                $fuelTypeStats[$fuelTypeId]['total_liters'] += $liters;
                $fuelTypeStats[$fuelTypeId]['total_tons'] += $tons;
                $fuelTypeStats[$fuelTypeId]['order_count']++;

                $totalCost += $cost;
                $totalLiters += $liters;
                $totalTons += $tons;
            }

            // Calculate percentages
            foreach ($fuelTypeStats as &$stats) {
                $stats['percentage_of_total'] = $totalCost > 0
                    ? round(($stats['total_cost'] / $totalCost) * 100, 1)
                    : 0;
                $stats['total_cost'] = round($stats['total_cost'], 2);
                $stats['total_tons'] = round($stats['total_tons'], 2);
            }
            unset($stats);

            // Sort by cost descending
            usort($fuelTypeStats, fn($a, $b) => $b['total_cost'] <=> $a['total_cost']);

            // Price trends for last 30 days
            $priceTrends = Database::fetchAll("
                SELECT
                    DATE(o.delivery_date) as price_date,
                    ft.id as fuel_type_id,
                    ft.name as fuel_name,
                    AVG(o.price_per_ton) as price_per_liter
                FROM orders o
                JOIN fuel_types ft ON o.fuel_type_id = ft.id
                WHERE o.delivery_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                  AND o.status = 'delivered'
                  AND o.price_per_ton > 0
                GROUP BY DATE(o.delivery_date), ft.id, ft.name
                ORDER BY price_date ASC, ft.name
            ");

            return [
                'success' => true,
                'data' => [
                    'avg_cost_per_liter' => $totalLiters > 0
                        ? number_format($totalCost / $totalLiters, 2, '.', '')
                        : '0.00',
                    'total_monthly_spend' => round($totalCost, 2),
                    'avg_cost_per_ton' => $totalTons > 0
                        ? round($totalCost / $totalTons, 2)
                        : 0,
                    'fuel_breakdown' => array_values($fuelTypeStats),
                    'price_trends' => $priceTrends
                ]
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
