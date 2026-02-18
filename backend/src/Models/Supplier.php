<?php

namespace App\Models;

use App\Core\Database;

/**
 * Supplier Model
 * Represents fuel suppliers
 */
class Supplier
{
    /**
     * Get all suppliers
     */
    public static function all(): array
    {
        return Database::fetchAll("
            SELECT
                id,
                name,
                departure_station,
                priority,
                auto_score,
                avg_delivery_days,
                is_active,
                created_at
            FROM suppliers
            ORDER BY name ASC
        ");
    }

    /**
     * Find supplier by ID
     */
    public static function find(int $id): ?array
    {
        $result = Database::fetchAll("
            SELECT
                id,
                name,
                departure_station,
                priority,
                auto_score,
                avg_delivery_days,
                is_active,
                created_at
            FROM suppliers
            WHERE id = ?
        ", [$id]);

        return $result[0] ?? null;
    }

    /**
     * Get active suppliers only
     */
    public static function getActive(): array
    {
        return Database::fetchAll("
            SELECT
                id,
                name,
                departure_station,
                priority,
                auto_score,
                avg_delivery_days,
                created_at
            FROM suppliers
            WHERE is_active = 1
            ORDER BY name ASC
        ");
    }

    /**
     * Create a new supplier
     */
    public static function create(string $name): int
    {
        Database::execute(
            "INSERT INTO suppliers (name, is_active, created_at) VALUES (?, 1, NOW())",
            [$name]
        );
        return (int) Database::lastInsertId();
    }

    /**
     * Get all orders for a supplier
     */
    public static function getOrders(int $supplierId): array
    {
        return Database::fetchAll("
            SELECT
                o.id,
                o.order_number,
                o.order_date,
                o.delivery_date,
                o.status,
                o.total_amount,
                ft.name as fuel_type_name,
                ft.code as fuel_type_code,
                o.quantity_liters,
                ROUND(o.quantity_liters * ft.density / 1000, 2) as quantity_tons,
                o.price_per_ton,
                o.created_at
            FROM orders o
            LEFT JOIN fuel_types ft ON o.fuel_type_id = ft.id
            WHERE o.supplier_id = ?
            ORDER BY o.order_date DESC
        ", [$supplierId]);
    }

    /**
     * Get supplier statistics
     */
    public static function getStats(int $supplierId): array
    {
        $result = Database::fetchAll("
            SELECT
                COUNT(*) as total_orders,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
                SUM(quantity_liters) as total_liters_ordered,
                SUM(total_amount) as total_amount_spent
            FROM orders
            WHERE supplier_id = ?
        ", [$supplierId]);

        return $result[0] ?? [];
    }
}
