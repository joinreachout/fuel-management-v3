<?php

namespace App\Models;

use App\Core\Database;

/**
 * Order Model
 *
 * Two distinct types co-exist in the orders table:
 *   purchase_order — created by user (planned/matched/expired/cancelled)
 *   erp_order      — imported from ERP via Import module (confirmed/in_transit/delivered/cancelled)
 *
 * Only ERP orders drive the Forecast chart.
 * PO lifecycle: planned → (ERP matches it → matched) | (date passes → expired) | (user cancels → cancelled)
 */
class Order
{
    /**
     * Base SELECT fields used in multiple queries
     */
    private static function baseSelect(): string
    {
        return "
            SELECT
                o.id,
                o.order_number,
                o.supplier_id,
                s.name as supplier_name,
                o.station_id,
                st.name as station_name,
                o.depot_id,
                d.name as depot_name,
                o.fuel_type_id,
                ft.name as fuel_type_name,
                ft.code as fuel_type_code,
                ft.density,
                o.quantity_liters,
                ROUND(o.quantity_liters * ft.density / 1000, 2) as quantity_tons,
                o.price_per_ton,
                o.total_amount,
                o.order_date,
                o.delivery_date,
                o.order_type,
                o.status,
                o.notes,
                o.cancelled_reason,
                o.cancelled_at,
                o.erp_order_id,
                o.matched_at,
                o.created_at,
                o.created_by
            FROM orders o
            LEFT JOIN suppliers s ON o.supplier_id = s.id
            LEFT JOIN stations st ON o.station_id = st.id
            LEFT JOIN depots d ON o.depot_id = d.id
            LEFT JOIN fuel_types ft ON o.fuel_type_id = ft.id
        ";
    }

    /**
     * Get all orders with optional filters
     * @param array $filters: order_type, station_id, fuel_type_id, status, date_from, date_to
     */
    public static function all(array $filters = []): array
    {
        $sql = self::baseSelect() . " WHERE 1=1";
        $params = [];

        if (!empty($filters['order_type'])) {
            $sql .= " AND o.order_type = ?";
            $params[] = $filters['order_type'];
        }
        if (!empty($filters['station_id'])) {
            $sql .= " AND o.station_id = ?";
            $params[] = (int)$filters['station_id'];
        }
        if (!empty($filters['fuel_type_id'])) {
            $sql .= " AND o.fuel_type_id = ?";
            $params[] = (int)$filters['fuel_type_id'];
        }
        if (!empty($filters['status'])) {
            $sql .= " AND o.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['date_from'])) {
            $sql .= " AND o.delivery_date >= ?";
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $sql .= " AND o.delivery_date <= ?";
            $params[] = $filters['date_to'];
        }

        $sql .= " ORDER BY o.delivery_date ASC, o.id DESC";
        return Database::fetchAll($sql, $params);
    }

    /**
     * Find order by ID
     */
    public static function find(int $id): ?array
    {
        $result = Database::fetchAll(
            self::baseSelect() . " WHERE o.id = ?",
            [$id]
        );
        return $result[0] ?? null;
    }

    /**
     * Create a new purchase order (PO)
     * Auto-generates order_number in format ORD-YYYY-NNN
     */
    public static function create(array $data): ?array
    {
        // Generate order_number: ORD-2026-001
        $year = date('Y');
        $countRow = Database::fetchAll(
            "SELECT COUNT(*) as cnt FROM orders WHERE YEAR(order_date) = ?",
            [$year]
        );
        $count = (int)($countRow[0]['cnt'] ?? 0) + 1;
        $orderNumber = 'ORD-' . $year . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);

        // Calculate total_amount if price_per_ton and quantity_liters provided
        $totalAmount = $data['total_amount'] ?? null;
        if (!$totalAmount && !empty($data['price_per_ton']) && !empty($data['quantity_liters'])) {
            // Get density for the fuel type
            $ftRow = Database::fetchAll(
                "SELECT density FROM fuel_types WHERE id = ?",
                [(int)$data['fuel_type_id']]
            );
            $density = (float)($ftRow[0]['density'] ?? 0.85);
            $tons = ($data['quantity_liters'] * $density) / 1000;
            $totalAmount = round($tons * $data['price_per_ton'], 2);
        }

        Database::query("
            INSERT INTO orders (
                order_number, order_type, station_id, depot_id, fuel_type_id,
                supplier_id, quantity_liters, price_per_ton, total_amount,
                order_date, delivery_date, status, notes, created_by
            ) VALUES (?, 'purchase_order', ?, ?, ?, ?, ?, ?, ?, ?, ?, 'planned', ?, ?)
        ", [
            $orderNumber,
            (int)$data['station_id'],
            !empty($data['depot_id']) ? (int)$data['depot_id'] : null,
            (int)$data['fuel_type_id'],
            !empty($data['supplier_id']) ? (int)$data['supplier_id'] : null,
            (float)$data['quantity_liters'],
            !empty($data['price_per_ton']) ? (float)$data['price_per_ton'] : null,
            $totalAmount,
            $data['order_date'] ?? date('Y-m-d'),
            $data['delivery_date'],
            $data['notes'] ?? null,
            $data['created_by'] ?? null,
        ]);

        $id = Database::getConnection()->lastInsertId();
        return self::find($id);
    }

    /**
     * Update a purchase order (only if not delivered/cancelled)
     */
    public static function update(int $id, array $data): ?array
    {
        $order = self::find($id);
        if (!$order) return null;
        if (in_array($order['status'], ['delivered', 'cancelled'])) return null;

        $fields = [];
        $params = [];

        $allowed = ['quantity_liters', 'price_per_ton', 'total_amount',
                    'delivery_date', 'supplier_id', 'depot_id', 'notes'];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = ?";
                $params[] = $data[$field];
            }
        }

        if (empty($fields)) return self::find($id);

        $params[] = $id;
        Database::query(
            "UPDATE orders SET " . implode(', ', $fields) . " WHERE id = ?",
            $params
        );

        return self::find($id);
    }

    /**
     * Cancel a purchase order with a reason (user error correction only)
     * Only purchase_orders can be cancelled by the user.
     * ERP orders: status managed by ERP/Import only.
     */
    public static function cancel(int $id, string $reason): ?array
    {
        $order = self::find($id);
        if (!$order) return null;
        // Only POs can be cancelled by the user
        if ($order['order_type'] !== 'purchase_order') return null;
        // Cannot cancel if already terminal
        if (in_array($order['status'], ['delivered', 'cancelled', 'matched'])) return null;

        Database::query("
            UPDATE orders
            SET status = 'cancelled', cancelled_reason = ?, cancelled_at = NOW()
            WHERE id = ?
        ", [trim($reason), $id]);

        return self::find($id);
    }

    /**
     * Delete a purchase order (only if status = 'planned')
     */
    public static function delete(int $id): bool
    {
        $order = self::find($id);
        if (!$order || $order['status'] !== 'planned') return false;

        Database::query("DELETE FROM orders WHERE id = ?", [$id]);
        return true;
    }

    /**
     * Get orders by status
     */
    public static function getByStatus(string $status): array
    {
        return Database::fetchAll(
            self::baseSelect() . " WHERE o.status = ? ORDER BY o.order_date DESC",
            [$status]
        );
    }

    /**
     * Get pending orders (not yet delivered)
     */
    public static function getPending(): array
    {
        return self::getByStatus('planned');
    }

    /**
     * Get orders summary by fuel type
     */
    public static function getSummaryByFuelType(): array
    {
        return Database::fetchAll("
            SELECT
                ft.id as fuel_type_id,
                ft.name as fuel_type_name,
                ft.code as fuel_type_code,
                COUNT(o.id) as total_orders,
                SUM(o.quantity_liters) as total_liters,
                ROUND(SUM(o.quantity_liters * ft.density / 1000), 2) as total_tons,
                SUM(o.total_amount) as total_amount,
                AVG(o.price_per_ton) as avg_price_per_ton
            FROM orders o
            LEFT JOIN fuel_types ft ON o.fuel_type_id = ft.id
            WHERE o.status = 'delivered'
            GROUP BY ft.id, ft.name, ft.code, ft.density
            ORDER BY total_liters DESC
        ");
    }

    /**
     * Mark a PO as matched with an incoming ERP order.
     * Called during Import when a matching ERP order arrives.
     */
    public static function matchWithErp(int $poId, int $erpOrderId): ?array
    {
        $po = self::find($poId);
        if (!$po || $po['order_type'] !== 'purchase_order' || $po['status'] !== 'planned') {
            return null;
        }

        Database::query("
            UPDATE orders
            SET status = 'matched', erp_order_id = ?, matched_at = NOW()
            WHERE id = ?
        ", [$erpOrderId, $poId]);

        return self::find($poId);
    }

    /**
     * Mark a PO as expired (delivery date passed without ERP confirmation).
     * Called during page load or cron-like check.
     */
    public static function markExpiredPOs(): int
    {
        $result = Database::query("
            UPDATE orders
            SET status = 'expired'
            WHERE order_type = 'purchase_order'
              AND status = 'planned'
              AND delivery_date < CURDATE()
        ");
        return $result->rowCount();
    }

    /**
     * Find active PO for station + fuel_type (for Procurement Advisor)
     * Returns the first planned PO with future delivery_date, if any
     */
    public static function findActivePO(int $stationId, int $fuelTypeId): ?array
    {
        $result = Database::fetchAll("
            SELECT
                o.id, o.order_number, o.delivery_date, o.quantity_liters,
                ROUND(o.quantity_liters * ft.density / 1000, 2) as quantity_tons,
                o.status
            FROM orders o
            LEFT JOIN fuel_types ft ON o.fuel_type_id = ft.id
            WHERE o.order_type = 'purchase_order'
              AND o.status = 'planned'
              AND o.station_id = ?
              AND o.fuel_type_id = ?
              AND o.delivery_date > CURDATE()
            ORDER BY o.delivery_date ASC
            LIMIT 1
        ", [$stationId, $fuelTypeId]);
        return $result[0] ?? null;
    }

    /**
     * Create an ERP order record (called from Import module)
     * order_type is always 'erp_order'; status set by ERP data
     */
    public static function createErpOrder(array $data): ?array
    {
        $year = date('Y');
        $countRow = Database::fetchAll(
            "SELECT COUNT(*) as cnt FROM orders WHERE YEAR(order_date) = ?",
            [$year]
        );
        $count = (int)($countRow[0]['cnt'] ?? 0) + 1;
        $orderNumber = $data['order_number'] ?? ('ERP-' . $year . '-' . str_pad($count, 3, '0', STR_PAD_LEFT));

        $totalAmount = $data['total_amount'] ?? null;
        if (!$totalAmount && !empty($data['price_per_ton']) && !empty($data['quantity_liters'])) {
            $ftRow = Database::fetchAll("SELECT density FROM fuel_types WHERE id = ?", [(int)$data['fuel_type_id']]);
            $density = (float)($ftRow[0]['density'] ?? 0.85);
            $tons = ($data['quantity_liters'] * $density) / 1000;
            $totalAmount = round($tons * $data['price_per_ton'], 2);
        }

        $validStatuses = ['confirmed', 'in_transit', 'delivered', 'cancelled'];
        $status = in_array($data['status'] ?? '', $validStatuses) ? $data['status'] : 'confirmed';

        Database::query("
            INSERT INTO orders (
                order_number, order_type, station_id, depot_id, fuel_type_id,
                supplier_id, quantity_liters, price_per_ton, total_amount,
                order_date, delivery_date, status, notes
            ) VALUES (?, 'erp_order', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ", [
            $orderNumber,
            (int)$data['station_id'],
            !empty($data['depot_id']) ? (int)$data['depot_id'] : null,
            (int)$data['fuel_type_id'],
            !empty($data['supplier_id']) ? (int)$data['supplier_id'] : null,
            (float)$data['quantity_liters'],
            !empty($data['price_per_ton']) ? (float)$data['price_per_ton'] : null,
            $totalAmount,
            $data['order_date'] ?? date('Y-m-d'),
            $data['delivery_date'],
            $status,
            $data['notes'] ?? null,
        ]);

        $id = Database::getConnection()->lastInsertId();

        // Auto-match: look for a PO with same station/fuel/date ±7 days
        $matchedPO = Database::fetchAll("
            SELECT id FROM orders
            WHERE order_type = 'purchase_order'
              AND status = 'planned'
              AND station_id = ?
              AND fuel_type_id = ?
              AND ABS(DATEDIFF(delivery_date, ?)) <= 7
            ORDER BY ABS(DATEDIFF(delivery_date, ?)) ASC
            LIMIT 1
        ", [
            (int)$data['station_id'],
            (int)$data['fuel_type_id'],
            $data['delivery_date'],
            $data['delivery_date']
        ]);

        if (!empty($matchedPO)) {
            self::matchWithErp((int)$matchedPO[0]['id'], $id);
        }

        return self::find($id);
    }

    /**
     * Get recent orders (last N days)
     */
    public static function getRecent(int $days = 30): array
    {
        return Database::fetchAll("
            SELECT
                o.id,
                o.order_number,
                s.name as supplier_name,
                ft.name as fuel_type_name,
                o.quantity_liters,
                o.total_amount,
                o.order_date,
                o.delivery_date,
                o.status,
                o.created_at
            FROM orders o
            LEFT JOIN suppliers s ON o.supplier_id = s.id
            LEFT JOIN fuel_types ft ON o.fuel_type_id = ft.id
            WHERE o.order_date >= DATE_SUB(NOW(), INTERVAL ? DAY)
            ORDER BY o.order_date DESC
        ", [$days]);
    }
}
