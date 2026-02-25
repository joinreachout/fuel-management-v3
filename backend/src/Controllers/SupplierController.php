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
     * Get all active suppliers with real data from supplier_station_offers + orders.
     * Ranked by: priority ASC, then stations_served DESC, then erp_orders_count DESC.
     * Each supplier includes per-fuel-type prices (not an average across fuel types).
     */
    public function top(): void
    {
        try {
            $db = \App\Core\Database::class;

            // ── 1. Main supplier stats ─────────────────────────────────
            $suppliers = \App\Core\Database::fetchAll("
                SELECT
                    sup.id,
                    sup.name,
                    sup.departure_station       AS location,
                    sup.priority,
                    sup.auto_score,
                    -- Delivery days from supplier_station_offers (real routes)
                    MIN(sso.delivery_days)              AS min_delivery_days,
                    MAX(sso.delivery_days)              AS max_delivery_days,
                    ROUND(AVG(sso.delivery_days))       AS avg_delivery_days,
                    COUNT(DISTINCT sso.station_id)      AS stations_served,
                    COUNT(DISTINCT sso.fuel_type_id)    AS fuel_types_count,
                    -- Order history from orders table (ERP orders = real deliveries)
                    COUNT(DISTINCT o.id)                AS orders_count,
                    SUM(CASE WHEN o.order_type = 'erp_order' THEN 1 ELSE 0 END) AS erp_orders_count,
                    SUM(CASE WHEN o.order_type = 'erp_order' AND o.status = 'delivered' THEN 1 ELSE 0 END)
                                                        AS delivered_count,
                    ROUND(SUM(o.total_amount), 2)       AS total_spend,
                    ROUND(SUM(o.quantity_liters) / 1000, 1) AS total_volume_kl
                FROM suppliers sup
                LEFT JOIN supplier_station_offers sso
                    ON sso.supplier_id = sup.id AND sso.is_active = 1
                LEFT JOIN orders o
                    ON o.supplier_id = sup.id
                WHERE sup.is_active = 1
                GROUP BY sup.id, sup.name, sup.departure_station, sup.priority, sup.auto_score
                ORDER BY sup.priority ASC, stations_served DESC, erp_orders_count DESC
            ");

            // ── 2. Per-fuel-type prices (single price per supplier+fuel) ──
            // Price is the same across all stations for a given supplier+fuel
            // (enforced by the Parameters UI save logic)
            $priceRows = \App\Core\Database::fetchAll("
                SELECT
                    sso.supplier_id,
                    ft.id   AS fuel_type_id,
                    ft.name AS fuel_type_name,
                    ft.code AS fuel_type_code,
                    AVG(sso.price_per_ton) AS price_per_ton
                FROM supplier_station_offers sso
                INNER JOIN fuel_types ft ON sso.fuel_type_id = ft.id
                WHERE sso.is_active = 1
                  AND sso.price_per_ton IS NOT NULL
                  AND sso.price_per_ton > 0
                GROUP BY sso.supplier_id, sso.fuel_type_id, ft.id, ft.name, ft.code
                ORDER BY sso.supplier_id, ft.name
            ");

            // Index prices by supplier_id → [{ fuel_type_code, price_per_ton }]
            $priceMap = [];
            foreach ($priceRows as $p) {
                $priceMap[(int)$p['supplier_id']][] = [
                    'fuel_type_id'   => (int)$p['fuel_type_id'],
                    'fuel_type_name' => $p['fuel_type_name'],
                    'fuel_type_code' => $p['fuel_type_code'],
                    'price_per_ton'  => round((float)$p['price_per_ton'], 2),
                ];
            }

            // ── 3. Build result ────────────────────────────────────────
            $result = [];
            foreach ($suppliers as $row) {
                $supplierId    = (int)$row['id'];
                $erpCount      = (int)($row['erp_orders_count'] ?? 0);
                $deliveredCount = (int)($row['delivered_count'] ?? 0);

                // Delivered rate: what % of ERP orders were actually delivered
                // (Not "on-time" — we don't track actual vs. planned delivery timestamps)
                $deliveredRate = $erpCount > 0
                    ? round($deliveredCount / $erpCount * 100)
                    : null;

                $result[] = [
                    'id'              => $supplierId,
                    'name'            => $row['name'],
                    'location'        => $row['location'] ?? '',
                    'priority'        => (int)$row['priority'],
                    'auto_score'      => (float)$row['auto_score'],
                    // Delivery (from real routes in supplier_station_offers)
                    'min_delivery_days' => $row['min_delivery_days'] !== null ? (int)$row['min_delivery_days'] : null,
                    'max_delivery_days' => $row['max_delivery_days'] !== null ? (int)$row['max_delivery_days'] : null,
                    'avg_delivery_days' => $row['avg_delivery_days'] !== null ? (int)$row['avg_delivery_days'] : null,
                    'stations_served'   => (int)($row['stations_served'] ?? 0),
                    'fuel_types_count'  => (int)($row['fuel_types_count'] ?? 0),
                    // Order history
                    'orders_count'     => (int)($row['orders_count'] ?? 0),
                    'erp_orders_count' => $erpCount,
                    'delivered_count'  => $deliveredCount,
                    'delivered_rate'   => $deliveredRate,   // % delivered (not on-time rate)
                    'total_spend'      => (float)($row['total_spend'] ?? 0),
                    'total_volume_kl'  => (float)($row['total_volume_kl'] ?? 0),
                    // Per-fuel-type prices (NOT a single average — each fuel separately)
                    'prices'           => $priceMap[$supplierId] ?? [],
                ];
            }

            Response::json([
                'success' => true,
                'data'    => $result,
                'count'   => count($result),
            ]);
        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'error'   => 'Failed to fetch top suppliers: ' . $e->getMessage(),
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
