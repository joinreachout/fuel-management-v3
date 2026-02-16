<?php

namespace App\Controllers;

use App\Core\Response;
use App\Models\Transfer;

/**
 * Transfer Controller
 * Handles HTTP requests for transfer resources
 */
class TransferController
{
    /**
     * GET /api/transfers
     * Get all transfers
     */
    public function index(): void
    {
        try {
            $transfers = Transfer::all();

            Response::json([
                'success' => true,
                'data' => $transfers,
                'count' => count($transfers)
            ]);
        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'error' => 'Failed to fetch transfers: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/transfers/{id}
     * Get single transfer by ID
     */
    public function show(int $id): void
    {
        try {
            $transfer = Transfer::find($id);

            if (!$transfer) {
                Response::json([
                    'success' => false,
                    'error' => 'Transfer not found'
                ], 404);
                return;
            }

            Response::json([
                'success' => true,
                'data' => $transfer
            ]);
        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'error' => 'Failed to fetch transfer: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/transfers/pending
     * Get pending transfers
     */
    public function pending(): void
    {
        try {
            $transfers = Transfer::getPending();

            Response::json([
                'success' => true,
                'data' => $transfers,
                'count' => count($transfers)
            ]);
        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'error' => 'Failed to fetch pending transfers: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/transfers/recent
     * Get recent transfers (last 30 days)
     */
    public function recent(): void
    {
        try {
            $days = $_GET['days'] ?? 30;
            $transfers = Transfer::getRecent((int)$days);

            Response::json([
                'success' => true,
                'data' => $transfers,
                'count' => count($transfers),
                'days' => (int)$days
            ]);
        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'error' => 'Failed to fetch recent transfers: ' . $e->getMessage()
            ], 500);
        }
    }
}
