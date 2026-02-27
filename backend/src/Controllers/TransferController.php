<?php

namespace App\Controllers;

use App\Services\TransferService;
use App\Core\Response;

class TransferController
{
    public function getTransfers(): void
    {
        try {
            $status = $_GET['status'] ?? null;
            $fromStation = isset($_GET['from_station']) ? (int)$_GET['from_station'] : null;
            $toStation = isset($_GET['to_station']) ? (int)$_GET['to_station'] : null;
            $fuelType = isset($_GET['fuel_type']) ? (int)$_GET['fuel_type'] : null;

            $data = TransferService::getTransfers($status, $fromStation, $toStation, $fuelType);
            Response::json($data);
        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'message' => 'Failed to fetch transfers: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST /api/transfers
     * Create a new transfer manually.
     */
    public function create(): void
    {
        try {
            $body = json_decode(file_get_contents('php://input'), true);
            if (!$body) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid JSON body']);
                return;
            }

            $result = TransferService::create($body);
            http_response_code(201);
            echo json_encode($result);
        } catch (\InvalidArgumentException $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}
