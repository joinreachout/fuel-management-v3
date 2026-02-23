<?php

namespace App\Models;

use App\Core\Database;

/**
 * Order Model
 * Represents fuel purchase orders (PO) — created by users for boss approval + print
 * ERP deliveries (confirmed/in_transit/delivered) come through Import module
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
                o.status,
                o.notes,
                o.cancelled_reason,
                o.cancelled_at,
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
     * @param array $filters: station_id, fuel_type_id, status, date_from, date_to
     */
    public static function all(array $filters = []): array
    {
        $sql = self::baseSelect() . " WHERE 1=1";
        $params = [];

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

        $sql .= " ORDER BY o.order_date DESC, o.id DESC";
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
                order_number, station_id, depot_id, fuel_type_id,
                supplier_id, quantity_liters, price_per_ton, total_amount,
                order_date, delivery_date, status, notes, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'planned', ?, ?)
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
     * Cancel a purchase order with a reason (user error correction)
     * Can cancel: planned, confirmed, in_transit
     */
    public static function cancel(int $id, string $reason): ?array
    {
        $order = self::find($id);
        if (!$order) return null;
        if (in_array($order['status'], ['delivered', 'cancelled'])) return null;

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
