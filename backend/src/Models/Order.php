<?php

namespace App\Models;

use App\Core\Database;

/**
 * Order Model
 * Represents fuel orders from suppliers
 */
class Order
{
    /**
     * Get all orders
     */
    public static function all(): array
    {
        return Database::fetchAll("
            SELECT
                o.id,
                o.order_number,
                o.supplier_id,
                s.name as supplier_name,
                o.station_id,
                st.name as station_name,
                o.fuel_type_id,
                ft.name as fuel_type_name,
                ft.code as fuel_type_code,
                o.quantity_liters,
                ROUND(o.quantity_liters * ft.density / 1000, 2) as quantity_tons,
                o.price_per_ton,
                o.total_amount,
                o.order_date,
                o.delivery_date,
                o.status,
                o.notes,
                o.created_at
            FROM orders o
            LEFT JOIN suppliers s ON o.supplier_id = s.id
            LEFT JOIN stations st ON o.station_id = st.id
            LEFT JOIN fuel_types ft ON o.fuel_type_id = ft.id
            ORDER BY o.order_date DESC
        ");
    }

    /**
     * Find order by ID
     */
    public static function find(int $id): ?array
    {
        $result = Database::fetchAll("
            SELECT
                o.id,
                o.order_number,
                o.supplier_id,
                s.name as supplier_name,
                s.departure_station as supplier_station,
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
                o.created_at
            FROM orders o
            LEFT JOIN suppliers s ON o.supplier_id = s.id
            LEFT JOIN stations st ON o.station_id = st.id
            LEFT JOIN depots d ON o.depot_id = d.id
            LEFT JOIN fuel_types ft ON o.fuel_type_id = ft.id
            WHERE o.id = ?
        ", [$id]);

        return $result[0] ?? null;
    }

    /**
     * Get orders by status
     */
    public static function getByStatus(string $status): array
    {
        return Database::fetchAll("
            SELECT
                o.id,
                o.order_number,
                o.supplier_id,
                s.name as supplier_name,
                o.fuel_type_id,
                ft.name as fuel_type_name,
                ft.code as fuel_type_code,
                o.quantity_liters,
                ROUND(o.quantity_liters * ft.density / 1000, 2) as quantity_tons,
                o.price_per_ton,
                o.total_amount,
                o.order_date,
                o.delivery_date,
                o.status,
                o.created_at
            FROM orders o
            LEFT JOIN suppliers s ON o.supplier_id = s.id
            LEFT JOIN fuel_types ft ON o.fuel_type_id = ft.id
            WHERE o.status = ?
            ORDER BY o.order_date DESC
        ", [$status]);
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
     * Get recent orders (last 30 days)
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
