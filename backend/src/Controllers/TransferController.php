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
}
