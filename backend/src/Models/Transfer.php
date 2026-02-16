<?php

namespace App\Models;

use App\Core\Database;

/**
 * Transfer Model
 * Represents fuel transfers between stations
 * NOTE: Transfers work between STATIONS, not depots
 */
class Transfer
{
    /**
     * Get all transfers
     */
    public static function all(): array
    {
        return Database::fetchAll("
            SELECT
                t.id,
                t.from_station_id,
                s1.name as from_station_name,
                s1.code as from_station_code,
                t.to_station_id,
                s2.name as to_station_name,
                s2.code as to_station_code,
                t.fuel_type_id,
                ft.name as fuel_type_name,
                ft.code as fuel_type_code,
                t.transfer_amount_liters,
                ROUND(t.transfer_amount_liters * ft.density / 1000, 2) as transfer_amount_tons,
                t.status,
                t.urgency,
                t.estimated_days,
                t.notes,
                t.created_at,
                t.started_at,
                t.completed_at
            FROM transfers t
            LEFT JOIN stations s1 ON t.from_station_id = s1.id
            LEFT JOIN stations s2 ON t.to_station_id = s2.id
            LEFT JOIN fuel_types ft ON t.fuel_type_id = ft.id
            ORDER BY t.created_at DESC
        ");
    }

    /**
     * Find transfer by ID
     */
    public static function find(int $id): ?array
    {
        $result = Database::fetchAll("
            SELECT
                t.id,
                t.from_station_id,
                s1.name as from_station_name,
                s1.code as from_station_code,
                t.to_station_id,
                s2.name as to_station_name,
                s2.code as to_station_code,
                t.fuel_type_id,
                ft.name as fuel_type_name,
                ft.code as fuel_type_code,
                ft.density,
                t.transfer_amount_liters,
                ROUND(t.transfer_amount_liters * ft.density / 1000, 2) as transfer_amount_tons,
                t.status,
                t.urgency,
                t.estimated_days,
                t.from_station_level_before,
                t.to_station_level_before,
                t.from_station_level_after,
                t.to_station_level_after,
                t.notes,
                t.created_at,
                t.started_at,
                t.completed_at,
                t.cancelled_at,
                t.created_by
            FROM transfers t
            LEFT JOIN stations s1 ON t.from_station_id = s1.id
            LEFT JOIN stations s2 ON t.to_station_id = s2.id
            LEFT JOIN fuel_types ft ON t.fuel_type_id = ft.id
            WHERE t.id = ?
        ", [$id]);

        return $result[0] ?? null;
    }

    /**
     * Get transfers by station (both outgoing and incoming)
     */
    public static function getByStation(int $stationId): array
    {
        return Database::fetchAll("
            SELECT
                t.id,
                t.from_station_id,
                s1.name as from_station_name,
                t.to_station_id,
                s2.name as to_station_name,
                ft.name as fuel_type_name,
                ft.code as fuel_type_code,
                t.transfer_amount_liters,
                ROUND(t.transfer_amount_liters * ft.density / 1000, 2) as transfer_amount_tons,
                t.status,
                t.urgency,
                CASE
                    WHEN t.from_station_id = ? THEN 'outgoing'
                    WHEN t.to_station_id = ? THEN 'incoming'
                END as direction,
                t.created_at
            FROM transfers t
            LEFT JOIN stations s1 ON t.from_station_id = s1.id
            LEFT JOIN stations s2 ON t.to_station_id = s2.id
            LEFT JOIN fuel_types ft ON t.fuel_type_id = ft.id
            WHERE t.from_station_id = ? OR t.to_station_id = ?
            ORDER BY t.created_at DESC
        ", [$stationId, $stationId, $stationId, $stationId]);
    }

    /**
     * Get transfers by status
     */
    public static function getByStatus(string $status): array
    {
        return Database::fetchAll("
            SELECT
                t.id,
                s1.name as from_station_name,
                s2.name as to_station_name,
                ft.name as fuel_type_name,
                t.transfer_amount_liters,
                t.status,
                t.urgency,
                t.created_at
            FROM transfers t
            LEFT JOIN stations s1 ON t.from_station_id = s1.id
            LEFT JOIN stations s2 ON t.to_station_id = s2.id
            LEFT JOIN fuel_types ft ON t.fuel_type_id = ft.id
            WHERE t.status = ?
            ORDER BY t.created_at DESC
        ", [$status]);
    }

    /**
     * Get pending transfers
     */
    public static function getPending(): array
    {
        return self::getByStatus('pending');
    }

    /**
     * Get recent transfers (last 30 days)
     */
    public static function getRecent(int $days = 30): array
    {
        return Database::fetchAll("
            SELECT
                t.id,
                s1.name as from_station_name,
                s2.name as to_station_name,
                ft.name as fuel_type_name,
                ft.code as fuel_type_code,
                t.transfer_amount_liters,
                ROUND(t.transfer_amount_liters * ft.density / 1000, 2) as transfer_amount_tons,
                t.status,
                t.urgency,
                t.created_at
            FROM transfers t
            LEFT JOIN stations s1 ON t.from_station_id = s1.id
            LEFT JOIN stations s2 ON t.to_station_id = s2.id
            LEFT JOIN fuel_types ft ON t.fuel_type_id = ft.id
            WHERE t.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            ORDER BY t.created_at DESC
        ", [$days]);
    }

    /**
     * Get critical transfers (CRITICAL or CATASTROPHE urgency)
     */
    public static function getCritical(): array
    {
        return Database::fetchAll("
            SELECT
                t.id,
                s1.name as from_station_name,
                s2.name as to_station_name,
                ft.name as fuel_type_name,
                t.transfer_amount_liters,
                t.status,
                t.urgency,
                t.estimated_days,
                t.created_at
            FROM transfers t
            LEFT JOIN stations s1 ON t.from_station_id = s1.id
            LEFT JOIN stations s2 ON t.to_station_id = s2.id
            LEFT JOIN fuel_types ft ON t.fuel_type_id = ft.id
            WHERE t.urgency IN ('CRITICAL', 'CATASTROPHE')
            AND t.status NOT IN ('completed', 'cancelled')
            ORDER BY
                CASE t.urgency
                    WHEN 'CATASTROPHE' THEN 1
                    WHEN 'CRITICAL' THEN 2
                END,
                t.created_at ASC
        ");
    }
}
