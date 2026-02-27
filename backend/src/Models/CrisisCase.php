<?php

namespace App\Models;

use App\Core\Database;

/**
 * CrisisCase Model
 * Represents a crisis resolution case (split delivery or transfer proposal)
 */
class CrisisCase
{
    /**
     * Get all cases, optionally filtered by status
     *
     * @param string|null $status Filter by status (proposed|accepted|monitoring|resolved)
     * @return array
     */
    public static function findAll(?string $status = null): array
    {
        $sql = "
            SELECT
                cc.*,
                rd.name  AS receiving_depot_name,
                rs.name  AS receiving_station_name,
                rs.id    AS receiving_station_id,
                ft.name  AS fuel_type_name,
                ft.code  AS fuel_type_code,
                dd.name  AS donor_depot_name,
                ds.name  AS donor_station_name,
                -- Donor ERP order details
                o.order_number  AS donor_order_number,
                o.quantity_liters AS donor_order_qty_liters,
                o.delivery_date AS donor_order_delivery_date,
                -- Compensating PO details
                poc.order_number AS po_critical_number,
                poc.quantity_liters AS po_critical_qty_liters,
                poc.status AS po_critical_status,
                pod.order_number AS po_donor_number,
                pod.quantity_liters AS po_donor_qty_liters,
                pod.status AS po_donor_status
            FROM crisis_cases cc
            JOIN depots rd    ON cc.receiving_depot_id = rd.id
            JOIN stations rs  ON rd.station_id = rs.id
            JOIN fuel_types ft ON cc.fuel_type_id = ft.id
            LEFT JOIN depots dd   ON cc.donor_depot_id = dd.id
            LEFT JOIN stations ds ON dd.station_id = ds.id
            LEFT JOIN orders o    ON cc.donor_order_id = o.id
            LEFT JOIN orders poc  ON cc.po_for_critical_id = poc.id
            LEFT JOIN orders pod  ON cc.po_for_donor_id = pod.id
        ";

        $params = [];
        if ($status !== null) {
            $sql .= ' WHERE cc.status = ?';
            $params[] = $status;
        }

        $sql .= ' ORDER BY cc.created_at DESC';

        return Database::fetchAll($sql, $params);
    }

    /**
     * Get a single case by ID
     *
     * @param int $id
     * @return array|false
     */
    public static function findById(int $id)
    {
        return Database::fetchOne(
            "SELECT cc.*,
                rd.name AS receiving_depot_name,
                rs.name AS receiving_station_name,
                rs.id   AS receiving_station_id,
                ft.name AS fuel_type_name,
                ft.code AS fuel_type_code,
                dd.name AS donor_depot_name,
                o.order_number AS donor_order_number,
                o.delivery_date AS donor_order_delivery_date
             FROM crisis_cases cc
             JOIN depots rd    ON cc.receiving_depot_id = rd.id
             JOIN stations rs  ON rd.station_id = rs.id
             JOIN fuel_types ft ON cc.fuel_type_id = ft.id
             LEFT JOIN depots dd  ON cc.donor_depot_id = dd.id
             LEFT JOIN orders o   ON cc.donor_order_id = o.id
             WHERE cc.id = ?",
            [$id]
        );
    }

    /**
     * Create a new crisis case
     *
     * @param array $data
     * @return int New case ID
     */
    public static function create(array $data): int
    {
        return Database::insert('crisis_cases', [
            'case_type'           => $data['case_type'],
            'status'              => $data['status'] ?? 'accepted',
            'receiving_depot_id'  => $data['receiving_depot_id'],
            'fuel_type_id'        => $data['fuel_type_id'],
            'qty_needed_tons'     => $data['qty_needed_tons'],
            'donor_order_id'      => $data['donor_order_id']   ?? null,
            'donor_depot_id'      => $data['donor_depot_id']   ?? null,
            'split_qty_tons'      => $data['split_qty_tons'],
            'po_for_critical_id'  => $data['po_for_critical_id'] ?? null,
            'po_for_donor_id'     => $data['po_for_donor_id']    ?? null,
            'notes'               => $data['notes'] ?? null,
        ]);
    }

    /**
     * Update case status (and optionally link compensating POs)
     *
     * @param int   $id
     * @param array $data Fields to update
     * @return int Rows affected
     */
    public static function update(int $id, array $data): int
    {
        $allowed = [
            'status', 'po_for_critical_id', 'po_for_donor_id',
            'notes', 'resolved_at',
        ];
        $update = array_intersect_key($data, array_flip($allowed));

        if (empty($update)) {
            return 0;
        }

        return Database::update('crisis_cases', $update, 'id = ?', [$id]);
    }

    /**
     * Mark a case as resolved
     *
     * @param int $id
     * @return int Rows affected
     */
    public static function resolve(int $id): int
    {
        return Database::update('crisis_cases', [
            'status'      => 'resolved',
            'resolved_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);
    }

    /**
     * Count active (non-resolved) cases
     *
     * @return int
     */
    public static function countActive(): int
    {
        return (int)Database::fetchColumn(
            "SELECT COUNT(*) FROM crisis_cases WHERE status != 'resolved'"
        );
    }
}
