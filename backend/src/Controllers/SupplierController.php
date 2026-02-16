<?php

namespace App\Controllers;

use App\Core\Response;
use App\Models\Supplier;

/**
 * Supplier Controller
 * Handles HTTP requests for supplier resources
 */
class SupplierController
{
    /**
     * GET /api/suppliers
     * Get all suppliers
     */
    public function index(): void
    {
        try {
            $suppliers = Supplier::all();

            Response::json([
                'success' => true,
                'data' => $suppliers,
                'count' => count($suppliers)
            ]);
        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'error' => 'Failed to fetch suppliers: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/suppliers/{id}
     * Get single supplier by ID
     */
    public function show(int $id): void
    {
        try {
            $supplier = Supplier::find($id);

            if (!$supplier) {
                Response::json([
                    'success' => false,
                    'error' => 'Supplier not found'
                ], 404);
                return;
            }

            Response::json([
                'success' => true,
                'data' => $supplier
            ]);
        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'error' => 'Failed to fetch supplier: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/suppliers/active
     * Get active suppliers only
     */
    public function active(): void
    {
        try {
            $suppliers = Supplier::getActive();

            Response::json([
                'success' => true,
                'data' => $suppliers,
                'count' => count($suppliers)
            ]);
        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'error' => 'Failed to fetch active suppliers: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/suppliers/{id}/orders
     * Get all orders for a supplier
     */
    public function orders(int $id): void
    {
        try {
            $orders = Supplier::getOrders($id);

            Response::json([
                'success' => true,
                'data' => $orders,
                'count' => count($orders),
                'supplier_id' => $id
            ]);
        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'error' => 'Failed to fetch supplier orders: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/suppliers/{id}/stats
     * Get supplier statistics
     */
    public function stats(int $id): void
    {
        try {
            $stats = Supplier::getStats($id);

            Response::json([
                'success' => true,
                'data' => $stats,
                'supplier_id' => $id
            ]);
        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'error' => 'Failed to fetch supplier stats: ' . $e->getMessage()
            ], 500);
        }
    }
}
