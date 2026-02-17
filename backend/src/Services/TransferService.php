<?php

namespace App\Services;

use App\Core\Database;
use PDO;

class TransferService
{
    public static function getTransfers(
        ?string $status = null,
        ?int $fromStation = null,
        ?int $toStation = null,
        ?int $fuelType = null
    ): array {
        $db = Database::getInstance();
        $pdo = $db->getConnection();

        try {
            // Build query with filters
            $whereConditions = [];
            $params = [];

            if ($status) {
                $whereConditions[] = "t.status = :status";
                $params[':status'] = $status;
            }

            if ($fromStation) {
                $whereConditions[] = "t.from_station_id = :from_station";
                $params[':from_station'] = $fromStation;
            }

            if ($toStation) {
                $whereConditions[] = "t.to_station_id = :to_station";
                $params[':to_station'] = $toStation;
            }

            if ($fuelType) {
                $whereConditions[] = "t.fuel_type_id = :fuel_type";
                $params[':fuel_type'] = $fuelType;
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

            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $transfers = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get statistics
            $statsQuery = "
                SELECT
                    COUNT(*) as total_transfers,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_transfers,
                    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_transfers,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_transfers,
                    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_transfers,
                    COALESCE(SUM(transfer_amount), 0) as total_amount
                FROM transfers
                {$whereClause}
            ";

            $statsStmt = $pdo->prepare($statsQuery);
            $statsStmt->execute($params);
            $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

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
}
