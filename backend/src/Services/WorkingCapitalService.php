<?php

namespace App\Services;

use App\Core\Database;

/**
 * Working Capital Service
 * Calculates current stock value, days of cover, and optimization potential
 */
class WorkingCapitalService
{
    public static function getSummary(): array
    {
        try {
            // Current stock value: SUM(stock_liters * density * avg_price_per_ton / 1000)
            // avg_price_per_ton from most recent delivered orders per fuel_type
            $stockValue = Database::fetchAll("
                SELECT
                    ft.id as fuel_type_id,
                    ft.name as fuel_type_name,
                    ft.density,
                    SUM(dt.current_stock_liters) as total_stock_liters,
                    COALESCE(
                        (SELECT AVG(o2.price_per_ton)
                         FROM orders o2
                         WHERE o2.fuel_type_id = ft.id
                           AND o2.status = 'delivered'
                           AND o2.price_per_ton > 0
                         ORDER BY o2.delivery_date DESC
                         LIMIT 5),
                        0
                    ) as avg_price_per_ton
                FROM depot_tanks dt
                JOIN fuel_types ft ON dt.fuel_type_id = ft.id
                WHERE dt.is_active = 1
                GROUP BY ft.id, ft.name, ft.density
            ");

            $totalStockValueUSD = 0;
            foreach ($stockValue as $row) {
                $liters = (float)$row['total_stock_liters'];
                $density = (float)$row['density'];
                $pricePerTon = (float)$row['avg_price_per_ton'];
                if ($pricePerTon > 0 && $density > 0) {
                    $tons = $liters * $density / 1000;
                    $totalStockValueUSD += $tons * $pricePerTon;
                }
            }

            // Days of cover: avg days per depot/fuel combination
            $coverRows = Database::fetchAll("
                SELECT
                    dt.depot_id,
                    dt.fuel_type_id,
                    SUM(dt.current_stock_liters) as total_stock,
                    sp.liters_per_day
                FROM depot_tanks dt
                JOIN sales_params sp ON dt.depot_id = sp.depot_id
                    AND dt.fuel_type_id = sp.fuel_type_id
                    AND (sp.effective_to IS NULL OR sp.effective_to >= CURDATE())
                WHERE sp.liters_per_day > 0
                  AND dt.is_active = 1
                GROUP BY dt.depot_id, dt.fuel_type_id, sp.liters_per_day
            ");

            $daysOfCoverValues = [];
            foreach ($coverRows as $row) {
                $lpd = (float)$row['liters_per_day'];
                if ($lpd > 0) {
                    $daysOfCoverValues[] = (float)$row['total_stock'] / $lpd;
                }
            }
            $avgDaysOfCover = count($daysOfCoverValues) > 0
                ? array_sum($daysOfCoverValues) / count($daysOfCoverValues)
                : 0;

            // Optimized target = 21 days (standard safety stock)
            $targetDays = 21;
            $optimizationRatio = $avgDaysOfCover > 0
                ? min(1, $targetDays / $avgDaysOfCover)
                : 1;
            $optimizedValueUSD = $totalStockValueUSD * $optimizationRatio;
            $capitalFreedUSD = $totalStockValueUSD - $optimizedValueUSD;

            // Convert USD → format as millions
            $formatM = fn($v) => round($v / 1000000, 1);

            return [
                'success' => true,
                'data' => [
                    'current_stock_value_usd' => round($totalStockValueUSD, 0),
                    'current_stock_value_display' => '$' . $formatM($totalStockValueUSD) . 'M',
                    'optimized_value_usd' => round($optimizedValueUSD, 0),
                    'optimized_value_display' => '$' . $formatM($optimizedValueUSD) . 'M',
                    'days_of_cover' => round($avgDaysOfCover, 0),
                    'target_days_of_cover' => $targetDays,
                    'capital_freed_usd' => round($capitalFreedUSD, 0),
                    'capital_freed_display' => '$' . $formatM($capitalFreedUSD) . 'M',
                    'reduction_percentage' => $avgDaysOfCover > 0
                        ? round((1 - $optimizationRatio) * 100, 0)
                        : 0,
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
