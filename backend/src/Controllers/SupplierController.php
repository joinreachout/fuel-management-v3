<?php

namespace App\Controllers;

use App\Core\Response;
use App\Models\Supplier;

/**
 * Supplier Controller
 * Handles HTTP requests for supplier resources
 */
class SupplierController
{
    /**
     * GET /api/suppliers
     * Get all suppliers
     */
    public function index(): void
    {
        try {
            $suppliers = Supplier::all();

            Response::json([
                'success' => true,
                'data' => $suppliers,
                'count' => count($suppliers)
            ]);
        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'error' => 'Failed to fetch suppliers: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/suppliers/{id}
     * Get single supplier by ID
     */
    public function show(int $id): void
    {
        try {
            $supplier = Supplier::find($id);

            if (!$supplier) {
                Response::json([
                    'success' => false,
                    'error' => 'Supplier not found'
                ], 404);
                return;
            }

            Response::json([
                'success' => true,
                'data' => $supplier
            ]);
        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'error' => 'Failed to fetch supplier: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/suppliers/active
     * Get active suppliers only
     */
    public function active(): void
    {
        try {
            $suppliers = Supplier::getActive();

            Response::json([
                'success' => true,
                'data' => $suppliers,
                'count' => count($suppliers)
            ]);
        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'error' => 'Failed to fetch active suppliers: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST /api/suppliers
     * Create a new supplier
     */
    public function create(): void
    {
        try {
            $body = json_decode(file_get_contents('php://input'), true) ?? [];
            $name = trim($body['name'] ?? '');

            if ($name === '') {
                Response::json(['success' => false, 'error' => 'Supplier name is required'], 400);
                return;
            }

            $id = Supplier::create($name);

            Response::json([
                'success' => true,
                'data'    => ['id' => $id, 'name' => $name, 'is_active' => 1],
            ], 201);
        } catch (\Exception $e) {
            Response::json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/suppliers/{id}/orders
     * Get all orders for a supplier
     */
    public function orders(int $id): void
    {
        try {
            $orders = Supplier::getOrders($id);

            Response::json([
                'success' => true,
                'data' => $orders,
                'count' => count($orders),
                'supplier_id' => $id
            ]);
        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'error' => 'Failed to fetch supplier orders: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/suppliers/top
     * Get top suppliers ranked by order volume from orders table
     */
    public function top(): void
    {
        try {
            $rows = \App\Core\Database::fetchAll("
                SELECT
                    sup.id,
                    sup.name,
                    sup.departure_station as location,
                    sup.avg_delivery_days,
                    COUNT(o.id) as orders_count,
                    SUM(o.total_amount) as total_spend,
                    AVG(o.price_per_ton) as avg_price_per_ton,
                    SUM(o.quantity_liters) as total_liters,
                    SUM(CASE WHEN o.status = 'delivered' THEN 1 ELSE 0 END) as delivered_count
                FROM suppliers sup
                LEFT JOIN orders o ON o.supplier_id = sup.id
                WHERE sup.is_active = 1
                GROUP BY sup.id, sup.name, sup.departure_station, sup.avg_delivery_days
                HAVING orders_count > 0
                ORDER BY total_spend DESC
                LIMIT 3
            ");

            $grandTotal = array_sum(array_column($rows, 'total_spend'));

            $result = [];
            foreach ($rows as $row) {
                $ordersCount = (int)$row['orders_count'];
                $deliveredCount = (int)$row['delivered_count'];
                $onTimeRate = $ordersCount > 0 ? round($deliveredCount / $ordersCount * 100) : 0;
                $sharePercent = $grandTotal > 0
                    ? round((float)$row['total_spend'] / $grandTotal * 100, 1)
                    : 0;
                $avgDays = (float)($row['avg_delivery_days'] ?? 33);
                $deliveryScore = max(0, min(100, round(100 - ($avgDays - 30) * 4.3)));
                $result[] = [
                    'id' => (int)$row['id'],
                    'name' => $row['name'],
                    'location' => $row['location'] ?? '',
                    'orders_count' => $ordersCount,
                    'total_spend' => round((float)$row['total_spend'], 2),
                    'share_percent' => $sharePercent,
                    'avg_price_per_ton' => round((float)$row['avg_price_per_ton'], 2),
                    'avg_delivery_days' => round($avgDays, 1),
                    'on_time_rate' => $onTimeRate,
                    'delivery_score' => $deliveryScore,
                    'pricing_score' => 80,
                    'composite_score' => 0,
                ];
            }

            // Adjust pricing scores relative to each other (cheaper = higher score)
            if (!empty($result)) {
                $prices = array_column($result, 'avg_price_per_ton');
                $minPrice = min($prices);
                $maxPrice = max($prices);
                $priceRange = $maxPrice - $minPrice;
                foreach ($result as &$sup) {
                    $sup['pricing_score'] = $priceRange > 0
                        ? round(100 - (($sup['avg_price_per_ton'] - $minPrice) / $priceRange) * 30)
                        : 85;
                    $sup['composite_score'] = round(($sup['delivery_score'] + $sup['pricing_score']) / 2);
                }
                unset($sup);
            }

            Response::json([
                'success' => true,
                'data' => $result,
                'count' => count($result)
            ]);
        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'error' => 'Failed to fetch top suppliers: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/suppliers/{id}/stats
     * Get supplier statistics
     */
    public function stats(int $id): void
    {
        try {
            $stats = Supplier::getStats($id);

            Response::json([
                'success' => true,
                'data' => $stats,
                'supplier_id' => $id
            ]);
        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'error' => 'Failed to fetch supplier stats: ' . $e->getMessage()
            ], 500);
        }
    }
}
