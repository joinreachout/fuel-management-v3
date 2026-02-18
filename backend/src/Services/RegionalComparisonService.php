<?php

namespace App\Services;

use App\Core\Database;

class RegionalComparisonService
{
    public static function getRegionalComparison(): array
    {

        try {
            // Get regional comparison data
            $query = "
                SELECT
                    r.id as region_id,
                    r.name as region_name,
                    r.code as region_code,
                    COUNT(DISTINCT s.id) as stations_count
                FROM regions r
                LEFT JOIN stations s ON r.id = s.region_id
                GROUP BY r.id, r.name, r.code
                HAVING stations_count > 0
                ORDER BY r.name
            ";

            $regions = Database::fetchAll($query);

            // Get additional data for each region
            foreach ($regions as &$region) {
                $regionId = $region['region_id'];

                // Get fill percentage data
                $fillQuery = "
                    SELECT
                        ROUND(
                            (SUM(dt.current_stock_liters) / NULLIF(SUM(dt.capacity_liters), 0) * 100),
                            1
                        ) as fill_percentage
                    FROM depot_tanks dt
                    JOIN depots d ON dt.depot_id = d.id
                    JOIN stations s ON d.station_id = s.id
                    WHERE s.region_id = ?
                      AND dt.is_active = TRUE
                      AND dt.fuel_type_id IS NOT NULL
                ";

                $fillData = Database::fetchOne($fillQuery, [$regionId]);
                $avgFill = $fillData ? floatval($fillData['fill_percentage']) : 0;

                // Get critical stations count
                $criticalQuery = "
                    SELECT COUNT(DISTINCT s.id) as critical_count
                    FROM stations s
                    WHERE s.region_id = ?
                      AND s.id IN (
                          SELECT DISTINCT d.station_id
                          FROM depot_tanks dt
                          JOIN depots d ON dt.depot_id = d.id
                          WHERE dt.is_active = TRUE
                            AND dt.fuel_type_id IS NOT NULL
                            AND dt.capacity_liters > 0
                          GROUP BY d.station_id, dt.fuel_type_id
                          HAVING (SUM(dt.current_stock_liters) / SUM(dt.capacity_liters) * 100) < 30
                      )
                ";

                $criticalData = Database::fetchOne($criticalQuery, [$regionId]);
                $criticalCount = $criticalData ? intval($criticalData['critical_count']) : 0;

                // Get total stock in tons
                $stockQuery = "
                    SELECT
                        COALESCE(SUM((dt.current_stock_liters * ft.density) / 1000), 0) as total_stock_tons
                    FROM depot_tanks dt
                    JOIN depots d ON dt.depot_id = d.id
                    JOIN stations s ON d.station_id = s.id
                    JOIN fuel_types ft ON dt.fuel_type_id = ft.id
                    WHERE s.region_id = ?
                      AND dt.is_active = TRUE
                      AND dt.fuel_type_id IS NOT NULL
                ";

                $stockData = Database::fetchOne($stockQuery, [$regionId]);
                $totalStock = $stockData ? floatval($stockData['total_stock_tons']) : 0;

                // Get total consumption in tons per day
                $consumptionQuery = "
                    SELECT
                        COALESCE(SUM(sp.tons_per_day), 0) as total_consumption_tons
                    FROM sales_params sp
                    JOIN depots d ON sp.depot_id = d.id
                    JOIN stations s ON d.station_id = s.id
                    JOIN fuel_types ft ON sp.fuel_type_id = ft.id
                    WHERE s.region_id = ?
                ";

                $consumptionData = Database::fetchOne($consumptionQuery, [$regionId]);
                $totalConsumption = $consumptionData ? floatval($consumptionData['total_consumption_tons']) : 0;

                // Add calculated fields
                $region['avg_fill_percentage'] = $avgFill;
                $region['critical_stations_count'] = $criticalCount;
                $region['total_stock_tons'] = $totalStock;
                $region['total_consumption_tons_per_day'] = $totalConsumption;
            }
            unset($region);

            // Format results
            $result = [];
            foreach ($regions as $region) {
                $avgFill = floatval($region['avg_fill_percentage']);

                // Determine status
                $statusColor = 'green';
                $statusLabel = 'Good';
                if ($avgFill < 30) {
                    $statusColor = 'red';
                    $statusLabel = 'Critical';
                } elseif ($avgFill < 50) {
                    $statusColor = 'orange';
                    $statusLabel = 'Low';
                } elseif ($avgFill < 80) {
                    $statusColor = 'yellow';
                    $statusLabel = 'Normal';
                }

                $result[] = [
                    'region_id' => intval($region['region_id']),
                    'region_name' => $region['region_name'],
                    'region_code' => $region['region_code'],
                    'stations_count' => intval($region['stations_count']),
                    'avg_fill_percentage' => round($avgFill, 1),
                    'critical_stations_count' => intval($region['critical_stations_count']),
                    'total_stock_tons' => round(floatval($region['total_stock_tons']), 2),
                    'total_consumption_tons_per_day' => round(floatval($region['total_consumption_tons_per_day']), 2),
                    'status' => $statusLabel,
                    'status_color' => $statusColor,
                    '_sort_order' => $avgFill < 30 ? 1 : ($avgFill < 50 ? 2 : ($avgFill < 80 ? 3 : 4))
                ];
            }

            // Sort by status priority
            usort($result, function($a, $b) {
                if ($a['_sort_order'] != $b['_sort_order']) {
                    return $a['_sort_order'] - $b['_sort_order'];
                }
                if ($a['avg_fill_percentage'] != $b['avg_fill_percentage']) {
                    return $a['avg_fill_percentage'] <=> $b['avg_fill_percentage'];
                }
                return strcmp($a['region_name'], $b['region_name']);
            });

            // Remove sort helper
            foreach ($result as &$item) {
                unset($item['_sort_order']);
            }
            unset($item);

            return [
                'success' => true,
                'data' => $result,
                'count' => count($result),
                'timestamp' => date('Y-m-d H:i:s')
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
