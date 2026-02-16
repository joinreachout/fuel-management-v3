<?php

namespace App\Models;

use App\Core\Database;

/**
 * Transfer Model
 * Represents fuel transfers between depots
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
                t.transfer_number,
                t.from_depot_id,
                d1.name as from_depot_name,
                d1.code as from_depot_code,
                t.to_depot_id,
                d2.name as to_depot_name,
                d2.code as to_depot_code,
                t.fuel_type_id,
                ft.name as fuel_type_name,
                ft.code as fuel_type_code,
                t.quantity_liters,
                ROUND(t.quantity_liters * ft.density / 1000, 2) as quantity_tons,
                t.transfer_date,
                t.status,
                t.notes,
                t.created_at
            FROM transfers t
            LEFT JOIN depots d1 ON t.from_depot_id = d1.id
            LEFT JOIN depots d2 ON t.to_depot_id = d2.id
            LEFT JOIN fuel_types ft ON t.fuel_type_id = ft.id
            ORDER BY t.transfer_date DESC
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
                t.transfer_number,
                t.from_depot_id,
                d1.name as from_depot_name,
                d1.code as from_depot_code,
                s1.name as from_station_name,
                t.to_depot_id,
                d2.name as to_depot_name,
                d2.code as to_depot_code,
                s2.name as to_station_name,
                t.fuel_type_id,
                ft.name as fuel_type_name,
                ft.code as fuel_type_code,
                ft.density,
                t.quantity_liters,
                ROUND(t.quantity_liters * ft.density / 1000, 2) as quantity_tons,
                t.transfer_date,
                t.status,
                t.notes,
                t.created_at
            FROM transfers t
            LEFT JOIN depots d1 ON t.from_depot_id = d1.id
            LEFT JOIN stations s1 ON d1.station_id = s1.id
            LEFT JOIN depots d2 ON t.to_depot_id = d2.id
            LEFT JOIN stations s2 ON d2.station_id = s2.id
            LEFT JOIN fuel_types ft ON t.fuel_type_id = ft.id
            WHERE t.id = ?
        ", [$id]);

        return $result[0] ?? null;
    }

    /**
     * Get transfers by depot (both incoming and outgoing)
     */
    public static function getByDepot(int $depotId): array
    {
        return Database::fetchAll("
            SELECT
                t.id,
                t.transfer_number,
                t.from_depot_id,
                d1.name as from_depot_name,
                t.to_depot_id,
                d2.name as to_depot_name,
                ft.name as fuel_type_name,
                ft.code as fuel_type_code,
                t.quantity_liters,
                ROUND(t.quantity_liters * ft.density / 1000, 2) as quantity_tons,
                t.transfer_date,
                t.status,
                CASE
                    WHEN t.from_depot_id = ? THEN 'outgoing'
                    WHEN t.to_depot_id = ? THEN 'incoming'
                END as direction,
                t.created_at
            FROM transfers t
            LEFT JOIN depots d1 ON t.from_depot_id = d1.id
            LEFT JOIN depots d2 ON t.to_depot_id = d2.id
            LEFT JOIN fuel_types ft ON t.fuel_type_id = ft.id
            WHERE t.from_depot_id = ? OR t.to_depot_id = ?
            ORDER BY t.transfer_date DESC
        ", [$depotId, $depotId, $depotId, $depotId]);
    }

    /**
     * Get transfers by status
     */
    public static function getByStatus(string $status): array
    {
        return Database::fetchAll("
            SELECT
                t.id,
                t.transfer_number,
                d1.name as from_depot_name,
                d2.name as to_depot_name,
                ft.name as fuel_type_name,
                t.quantity_liters,
                t.transfer_date,
                t.status,
                t.created_at
            FROM transfers t
            LEFT JOIN depots d1 ON t.from_depot_id = d1.id
            LEFT JOIN depots d2 ON t.to_depot_id = d2.id
            LEFT JOIN fuel_types ft ON t.fuel_type_id = ft.id
            WHERE t.status = ?
            ORDER BY t.transfer_date DESC
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
                t.transfer_number,
                d1.name as from_depot_name,
                d2.name as to_depot_name,
                ft.name as fuel_type_name,
                ft.code as fuel_type_code,
                t.quantity_liters,
                ROUND(t.quantity_liters * ft.density / 1000, 2) as quantity_tons,
                t.transfer_date,
                t.status,
                t.created_at
            FROM transfers t
            LEFT JOIN depots d1 ON t.from_depot_id = d1.id
            LEFT JOIN depots d2 ON t.to_depot_id = d2.id
            LEFT JOIN fuel_types ft ON t.fuel_type_id = ft.id
            WHERE t.transfer_date >= DATE_SUB(NOW(), INTERVAL ? DAY)
            ORDER BY t.transfer_date DESC
        ", [$days]);
    }

    /**
     * Create new transfer with transaction
     */
    public static function create(array $data): bool
    {
        try {
            Database::beginTransaction();

            // Insert transfer record
            $transferId = Database::insert('transfers', [
                'transfer_number' => $data['transfer_number'],
                'from_depot_id' => $data['from_depot_id'],
                'to_depot_id' => $data['to_depot_id'],
                'fuel_type_id' => $data['fuel_type_id'],
                'quantity_liters' => $data['quantity_liters'],
                'transfer_date' => $data['transfer_date'],
                'status' => 'pending',
                'notes' => $data['notes'] ?? null,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            Database::commit();
            return true;
        } catch (\Exception $e) {
            Database::rollback();
            throw $e;
        }
    }

    /**
     * Complete transfer - update stock in both depots
     */
    public static function complete(int $transferId): bool
    {
        try {
            Database::beginTransaction();

            $transfer = self::find($transferId);
            if (!$transfer || $transfer['status'] !== 'pending') {
                throw new \Exception('Invalid transfer or already completed');
            }

            // Update status
            Database::update('transfers',
                ['status' => 'completed'],
                'id = ?',
                [$transferId]
            );

            // TODO: Update depot_tanks stock for both from and to depots
            // This requires finding the right tanks and updating their stock

            Database::commit();
            return true;
        } catch (\Exception $e) {
            Database::rollback();
            throw $e;
        }
    }
}
