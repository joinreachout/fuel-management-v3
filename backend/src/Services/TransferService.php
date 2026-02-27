<?php

namespace App\Services;

use App\Core\Database;

class TransferService
{
    public static function getTransfers(
        ?string $status = null,
        ?int $fromStation = null,
        ?int $toStation = null,
        ?int $fuelType = null
    ): array {
        try {
            // Build query with filters
            $whereConditions = [];
            $params = [];

            if ($status) {
                $whereConditions[] = "t.status = ?";
                $params[] = $status;
            }

            if ($fromStation) {
                $whereConditions[] = "t.from_station_id = ?";
                $params[] = $fromStation;
            }

            if ($toStation) {
                $whereConditions[] = "t.to_station_id = ?";
                $params[] = $toStation;
            }

            if ($fuelType) {
                $whereConditions[] = "t.fuel_type_id = ?";
                $params[] = $fuelType;
            }

            $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

            $query = "
                SELECT
                    t.*,
                    s1.name as from_station_name,
                    s2.name as to_station_name,
                    ft.name as fuel_type_name,
                    ft.density
                FROM transfers t
                JOIN stations s1 ON t.from_station_id = s1.id
                JOIN stations s2 ON t.to_station_id = s2.id
                JOIN fuel_types ft ON t.fuel_type_id = ft.id
                {$whereClause}
                ORDER BY t.created_at DESC
            ";

            $transfers = Database::fetchAll($query, $params);

            // Get statistics
            $statsQuery = "
                SELECT
                    COUNT(*) as total_transfers,
                    COALESCE(SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END), 0) as pending_transfers,
                    COALESCE(SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END), 0) as in_progress_transfers,
                    COALESCE(SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END), 0) as completed_transfers,
                    COALESCE(SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END), 0) as cancelled_transfers,
                    COALESCE(SUM(transfer_amount_liters), 0) as total_amount
                FROM transfers
                {$whereClause}
            ";

            $stats = Database::fetchOne($statsQuery, $params);

            return [
                'success' => true,
                'data' => $transfers,
                'stats' => $stats,
                'timestamp' => date('Y-m-d H:i:s')
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Find a single transfer by ID.
     */
    public static function find(int $id): ?array
    {
        $query = "
            SELECT
                t.*,
                s1.name as from_station_name,
                s2.name as to_station_name,
                ft.name as fuel_type_name,
                ft.density
            FROM transfers t
            JOIN stations s1 ON t.from_station_id = s1.id
            JOIN stations s2 ON t.to_station_id = s2.id
            JOIN fuel_types ft ON t.fuel_type_id = ft.id
            WHERE t.id = ?
        ";
        $row = Database::fetchOne($query, [$id]);
        return $row ?: null;
    }

    /**
     * Update a transfer.
     * Allowed fields depend on current status.
     *
     * pending     → urgency, estimated_days, notes, status (→ in_progress | cancelled)
     * in_progress → urgency, notes, status (→ completed | cancelled)
     * completed / cancelled → notes only
     */
    public static function update(int $id, array $data): array
    {
        $transfer = self::find($id);
        if (!$transfer) {
            throw new \InvalidArgumentException("Transfer #{$id} not found.");
        }

        $status = $transfer['status'];
        $allowed = ['notes']; // always editable

        if ($status === 'pending') {
            $allowed = array_merge($allowed, ['urgency', 'estimated_days', 'status']);
        } elseif ($status === 'in_progress') {
            $allowed = array_merge($allowed, ['urgency', 'status']);
        }

        // Validate status transition
        $validNext = [
            'pending'     => ['in_progress', 'cancelled'],
            'in_progress' => ['completed', 'cancelled'],
        ];

        $updates = [];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                if ($field === 'status') {
                    $newStatus = $data['status'];
                    $allowed_next = $validNext[$status] ?? [];
                    if (!in_array($newStatus, $allowed_next)) {
                        throw new \InvalidArgumentException(
                            "Cannot transition from '{$status}' to '{$newStatus}'."
                        );
                    }
                    $updates['status'] = $newStatus;
                    // Set timestamp fields
                    if ($newStatus === 'in_progress') {
                        $updates['started_at'] = date('Y-m-d H:i:s');
                    } elseif ($newStatus === 'completed') {
                        $updates['completed_at'] = date('Y-m-d H:i:s');
                    } elseif ($newStatus === 'cancelled') {
                        $updates['cancelled_at'] = date('Y-m-d H:i:s');
                    }
                } elseif ($field === 'estimated_days') {
                    $updates['estimated_days'] = (float)$data['estimated_days'];
                } elseif ($field === 'urgency') {
                    $validUrgencies = ['NORMAL', 'MUST_ORDER', 'CRITICAL', 'CATASTROPHE'];
                    if (!in_array($data['urgency'], $validUrgencies)) {
                        throw new \InvalidArgumentException("Invalid urgency value.");
                    }
                    $updates['urgency'] = $data['urgency'];
                } else {
                    $updates[$field] = $data[$field];
                }
            }
        }

        if (empty($updates)) {
            return ['success' => true, 'message' => 'No changes made'];
        }

        Database::update('transfers', $updates, 'id = ?', [$id]);
        return ['success' => true, 'data' => self::find($id)];
    }

    /**
     * Delete a transfer.
     * Only pending transfers can be deleted.
     */
    public static function delete(int $id): array
    {
        $transfer = self::find($id);
        if (!$transfer) {
            throw new \InvalidArgumentException("Transfer #{$id} not found.");
        }
        if ($transfer['status'] !== 'pending') {
            throw new \InvalidArgumentException(
                "Only pending transfers can be deleted. Current status: {$transfer['status']}."
            );
        }
        Database::delete('transfers', 'id = ?', [$id]);
        return ['success' => true, 'message' => "Transfer #{$id} deleted."];
    }

    /**
     * Create a new transfer manually.
     * Accepts quantity in tons, converts to liters using fuel density.
     *
     * @param array $data  from_station_id, to_station_id, fuel_type_id,
     *                     quantity_tons, urgency, estimated_days, notes (opt)
     * @return array
     */
    public static function create(array $data): array
    {
        $required = ['from_station_id', 'to_station_id', 'fuel_type_id', 'quantity_tons', 'urgency', 'estimated_days'];
        foreach ($required as $field) {
            if (empty($data[$field]) && $data[$field] !== 0) {
                throw new \InvalidArgumentException("Missing required field: {$field}");
            }
        }

        if ((int)$data['from_station_id'] === (int)$data['to_station_id']) {
            throw new \InvalidArgumentException("From and To stations must be different");
        }

        $density = (float)(Database::fetchColumn(
            "SELECT density FROM fuel_types WHERE id = ?", [(int)$data['fuel_type_id']]
        ) ?: 0.85);

        $liters = round((float)$data['quantity_tons'] * 1000 / $density, 2);

        $id = Database::insert('transfers', [
            'from_station_id'        => (int)$data['from_station_id'],
            'to_station_id'          => (int)$data['to_station_id'],
            'fuel_type_id'           => (int)$data['fuel_type_id'],
            'transfer_amount_liters' => $liters,
            'status'                 => 'pending',
            'urgency'                => $data['urgency'],
            'estimated_days'         => (float)$data['estimated_days'],
            'notes'                  => $data['notes'] ?? null,
            'created_by'             => 'manual',
        ]);

        return ['success' => true, 'id' => $id, 'transfer_amount_liters' => $liters];
    }
}
