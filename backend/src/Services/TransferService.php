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
