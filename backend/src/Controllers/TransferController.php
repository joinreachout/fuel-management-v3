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
     * GET /api/transfers/{id}
     * Return one transfer by ID.
     */
    public function show(int $id): void
    {
        try {
            $data = TransferService::find($id);
            if (!$data) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Transfer not found']);
                return;
            }
            Response::json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * PATCH /api/transfers/{id}
     * Update editable fields of a transfer.
     */
    public function update(int $id): void
    {
        try {
            $body = json_decode(file_get_contents('php://input'), true);
            if (!$body) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid JSON body']);
                return;
            }
            $result = TransferService::update($id, $body);
            Response::json($result);
        } catch (\InvalidArgumentException $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * DELETE /api/transfers/{id}
     * Delete a pending transfer.
     */
    public function delete(int $id): void
    {
        try {
            $result = TransferService::delete($id);
            Response::json($result);
        } catch (\InvalidArgumentException $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
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
