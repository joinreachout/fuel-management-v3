<?php

namespace App\Models;

use App\Core\Database;

/**
 * Sale Model
 * Represents fuel sales from depots
 */
class Sale
{
    /**
     * Get all sales
     */
    public static function all(): array
    {
        return Database::fetchAll("
            SELECT
                s.id,
                s.sale_number,
                s.depot_id,
                d.name as depot_name,
                d.code as depot_code,
                st.name as station_name,
                s.fuel_type_id,
                ft.name as fuel_type_name,
                ft.code as fuel_type_code,
                s.quantity_liters,
                ROUND(s.quantity_liters * ft.density / 1000, 2) as quantity_tons,
                s.price_per_liter,
                s.total_amount,
                s.currency,
                s.customer_name,
                s.sale_date,
                s.payment_status,
                s.notes,
                s.created_at
            FROM sales s
            LEFT JOIN depots d ON s.depot_id = d.id
            LEFT JOIN stations st ON d.station_id = st.id
            LEFT JOIN fuel_types ft ON s.fuel_type_id = ft.id
            ORDER BY s.sale_date DESC
        ");
    }

    /**
     * Find sale by ID
     */
    public static function find(int $id): ?array
    {
        $result = Database::fetchAll("
            SELECT
                s.id,
                s.sale_number,
                s.depot_id,
                d.name as depot_name,
                d.code as depot_code,
                st.name as station_name,
                st.code as station_code,
                s.fuel_type_id,
                ft.name as fuel_type_name,
                ft.code as fuel_type_code,
                ft.density,
                s.quantity_liters,
                ROUND(s.quantity_liters * ft.density / 1000, 2) as quantity_tons,
                s.price_per_liter,
                s.total_amount,
                s.currency,
                s.customer_name,
                s.customer_phone,
                s.customer_address,
                s.sale_date,
                s.payment_status,
                s.payment_method,
                s.notes,
                s.created_at
            FROM sales s
            LEFT JOIN depots d ON s.depot_id = d.id
            LEFT JOIN stations st ON d.station_id = st.id
            LEFT JOIN fuel_types ft ON s.fuel_type_id = ft.id
            WHERE s.id = ?
        ", [$id]);

        return $result[0] ?? null;
    }

    /**
     * Get sales by depot
     */
    public static function getByDepot(int $depotId): array
    {
        return Database::fetchAll("
            SELECT
                s.id,
                s.sale_number,
                ft.name as fuel_type_name,
                ft.code as fuel_type_code,
                s.quantity_liters,
                ROUND(s.quantity_liters * ft.density / 1000, 2) as quantity_tons,
                s.price_per_liter,
                s.total_amount,
                s.currency,
                s.customer_name,
                s.sale_date,
                s.payment_status,
                s.created_at
            FROM sales s
            LEFT JOIN fuel_types ft ON s.fuel_type_id = ft.id
            WHERE s.depot_id = ?
            ORDER BY s.sale_date DESC
        ", [$depotId]);
    }

    /**
     * Get sales by payment status
     */
    public static function getByPaymentStatus(string $status): array
    {
        return Database::fetchAll("
            SELECT
                s.id,
                s.sale_number,
                d.name as depot_name,
                ft.name as fuel_type_name,
                s.quantity_liters,
                s.total_amount,
                s.currency,
                s.customer_name,
                s.sale_date,
                s.payment_status,
                s.created_at
            FROM sales s
            LEFT JOIN depots d ON s.depot_id = d.id
            LEFT JOIN fuel_types ft ON s.fuel_type_id = ft.id
            WHERE s.payment_status = ?
            ORDER BY s.sale_date DESC
        ", [$status]);
    }

    /**
     * Get unpaid sales
     */
    public static function getUnpaid(): array
    {
        return self::getByPaymentStatus('unpaid');
    }

    /**
     * Get sales summary by fuel type
     */
    public static function getSummaryByFuelType(): array
    {
        return Database::fetchAll("
            SELECT
                ft.id as fuel_type_id,
                ft.name as fuel_type_name,
                ft.code as fuel_type_code,
                COUNT(s.id) as total_sales,
                SUM(s.quantity_liters) as total_liters,
                ROUND(SUM(s.quantity_liters * ft.density / 1000), 2) as total_tons,
                SUM(s.total_amount) as total_revenue,
                AVG(s.price_per_liter) as avg_price_per_liter
            FROM sales s
            LEFT JOIN fuel_types ft ON s.fuel_type_id = ft.id
            GROUP BY ft.id, ft.name, ft.code, ft.density
            ORDER BY total_liters DESC
        ");
    }

    /**
     * Get sales summary by depot
     */
    public static function getSummaryByDepot(): array
    {
        return Database::fetchAll("
            SELECT
                d.id as depot_id,
                d.name as depot_name,
                d.code as depot_code,
                st.name as station_name,
                COUNT(s.id) as total_sales,
                SUM(s.quantity_liters) as total_liters,
                SUM(s.total_amount) as total_revenue
            FROM sales s
            LEFT JOIN depots d ON s.depot_id = d.id
            LEFT JOIN stations st ON d.station_id = st.id
            GROUP BY d.id, d.name, d.code, st.name
            ORDER BY total_revenue DESC
        ");
    }

    /**
     * Get recent sales (last 30 days)
     */
    public static function getRecent(int $days = 30): array
    {
        return Database::fetchAll("
            SELECT
                s.id,
                s.sale_number,
                d.name as depot_name,
                ft.name as fuel_type_name,
                s.quantity_liters,
                s.total_amount,
                s.currency,
                s.customer_name,
                s.sale_date,
                s.payment_status,
                s.created_at
            FROM sales s
            LEFT JOIN depots d ON s.depot_id = d.id
            LEFT JOIN fuel_types ft ON s.fuel_type_id = ft.id
            WHERE s.sale_date >= DATE_SUB(NOW(), INTERVAL ? DAY)
            ORDER BY s.sale_date DESC
        ", [$days]);
    }

    /**
     * Get daily sales report
     */
    public static function getDailyReport(string $date): array
    {
        return Database::fetchAll("
            SELECT
                d.name as depot_name,
                ft.name as fuel_type_name,
                ft.code as fuel_type_code,
                COUNT(s.id) as sales_count,
                SUM(s.quantity_liters) as total_liters,
                ROUND(SUM(s.quantity_liters * ft.density / 1000), 2) as total_tons,
                SUM(s.total_amount) as total_revenue
            FROM sales s
            LEFT JOIN depots d ON s.depot_id = d.id
            LEFT JOIN fuel_types ft ON s.fuel_type_id = ft.id
            WHERE DATE(s.sale_date) = ?
            GROUP BY d.name, ft.name, ft.code, ft.density
            ORDER BY d.name, ft.name
        ", [$date]);
    }
}
