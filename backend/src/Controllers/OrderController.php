<?php

namespace App\Controllers;

use App\Core\Response;
use App\Models\Order;

/**
 * Order Controller
 * Handles HTTP requests for purchase orders (PO)
 * ERP-driven transitions (confirmed/in_transit/delivered) are NOT managed here
 */
class OrderController
{
    /**
     * GET /api/orders
     * Get all orders with optional filters:
     * ?station_id=1 &fuel_type_id=2 &status=planned &date_from=2026-02-01 &date_to=2026-03-31
     */
    public function index(): void
    {
        try {
            // Mark any expired POs on each load (POs past delivery_date with no ERP match)
            Order::markExpiredPOs();

            $filters = [];
            if (!empty($_GET['order_type']))   $filters['order_type']   = $_GET['order_type'];
            if (!empty($_GET['station_id']))   $filters['station_id']   = $_GET['station_id'];
            if (!empty($_GET['fuel_type_id'])) $filters['fuel_type_id'] = $_GET['fuel_type_id'];
            if (!empty($_GET['status']))       $filters['status']       = $_GET['status'];
            if (!empty($_GET['date_from']))    $filters['date_from']    = $_GET['date_from'];
            if (!empty($_GET['date_to']))      $filters['date_to']      = $_GET['date_to'];

            $orders = Order::all($filters);

            Response::json([
                'success' => true,
                'data' => $orders,
                'count' => count($orders)
            ]);
        } catch (\Exception $e) {
            Response::json(['success' => false, 'error' => 'Failed to fetch orders: ' . $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/orders/{id}
     */
    public function show(int $id): void
    {
        try {
            $order = Order::find($id);
            if (!$order) {
                Response::json(['success' => false, 'error' => 'Order not found'], 404);
                return;
            }
            Response::json(['success' => true, 'data' => $order]);
        } catch (\Exception $e) {
            Response::json(['success' => false, 'error' => 'Failed to fetch order: ' . $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/orders
     * Create a new purchase order (PO)
     * Required: station_id, fuel_type_id, quantity_liters, delivery_date
     */
    public function store(): void
    {
        try {
            $body = json_decode(file_get_contents('php://input'), true) ?? [];

            // Validate required fields
            $required = ['station_id', 'fuel_type_id', 'quantity_liters', 'delivery_date'];
            foreach ($required as $field) {
                if (empty($body[$field])) {
                    Response::json(['success' => false, 'error' => "Missing required field: {$field}"], 422);
                    return;
                }
            }

            $order = Order::create($body);
            if (!$order) {
                Response::json(['success' => false, 'error' => 'Failed to create order'], 500);
                return;
            }

            Response::json(['success' => true, 'data' => $order], 201);
        } catch (\Exception $e) {
            Response::json(['success' => false, 'error' => 'Failed to create order: ' . $e->getMessage()], 500);
        }
    }

    /**
     * PUT /api/orders/{id}
     * Update a purchase order (only if not delivered/cancelled)
     */
    public function update(int $id): void
    {
        try {
            $body = json_decode(file_get_contents('php://input'), true) ?? [];

            $order = Order::update($id, $body);
            if (!$order) {
                Response::json(['success' => false, 'error' => 'Order not found or cannot be updated (delivered/cancelled)'], 404);
                return;
            }

            Response::json(['success' => true, 'data' => $order]);
        } catch (\Exception $e) {
            Response::json(['success' => false, 'error' => 'Failed to update order: ' . $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/orders/{id}/cancel
     * Cancel a purchase order with a mandatory reason
     * Body: { "reason": "User made a mistake / wrong quantity / etc." }
     */
    public function cancel(int $id): void
    {
        try {
            $body = json_decode(file_get_contents('php://input'), true) ?? [];
            $reason = trim($body['reason'] ?? '');

            if (empty($reason)) {
                Response::json(['success' => false, 'error' => 'Cancellation reason is required'], 422);
                return;
            }

            $order = Order::cancel($id, $reason);
            if (!$order) {
                Response::json(['success' => false, 'error' => 'Order not found or cannot be cancelled (already delivered/cancelled)'], 404);
                return;
            }

            Response::json(['success' => true, 'data' => $order]);
        } catch (\Exception $e) {
            Response::json(['success' => false, 'error' => 'Failed to cancel order: ' . $e->getMessage()], 500);
        }
    }

    /**
     * DELETE /api/orders/{id}
     * Delete a purchase order (only if status = 'planned')
     */
    public function destroy(int $id): void
    {
        try {
            $deleted = Order::delete($id);
            if (!$deleted) {
                Response::json(['success' => false, 'error' => 'Order not found or cannot be deleted (only planned orders can be deleted)'], 404);
                return;
            }
            Response::json(['success' => true, 'message' => 'Order deleted']);
        } catch (\Exception $e) {
            Response::json(['success' => false, 'error' => 'Failed to delete order: ' . $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/orders/erp
     * Manually create an ERP order — fallback when ERP system is unavailable.
     * Creates order_type = 'erp_order'; auto-matches to a PO if one exists.
     *
     * Required: station_id, fuel_type_id, quantity_liters, delivery_date
     * Optional: supplier_id, depot_id, price_per_ton, notes
     * Optional: status — 'confirmed' (default) | 'in_transit'
     */
    public function storeErp(): void
    {
        try {
            $body = json_decode(file_get_contents('php://input'), true) ?? [];

            $required = ['station_id', 'fuel_type_id', 'quantity_liters', 'delivery_date'];
            foreach ($required as $field) {
                if (empty($body[$field])) {
                    Response::json(['success' => false, 'error' => "Missing required field: {$field}"], 422);
                    return;
                }
            }

            $order = Order::createErpOrder($body);
            if (!$order) {
                Response::json(['success' => false, 'error' => 'Failed to create ERP order'], 500);
                return;
            }

            Response::json(['success' => true, 'data' => $order], 201);
        } catch (\Exception $e) {
            Response::json(['success' => false, 'error' => 'Failed to create ERP order: ' . $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/orders/pending
     */
    public function pending(): void
    {
        try {
            $orders = Order::getPending();
            Response::json(['success' => true, 'data' => $orders, 'count' => count($orders)]);
        } catch (\Exception $e) {
            Response::json(['success' => false, 'error' => 'Failed to fetch pending orders: ' . $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/orders/summary
     */
    public function summary(): void
    {
        try {
            $summary = Order::getSummaryByFuelType();
            Response::json(['success' => true, 'data' => $summary, 'count' => count($summary)]);
        } catch (\Exception $e) {
            Response::json(['success' => false, 'error' => 'Failed to fetch orders summary: ' . $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/orders/recent
     */
    public function recent(): void
    {
        try {
            $days = $_GET['days'] ?? 30;
            $orders = Order::getRecent((int)$days);
            Response::json(['success' => true, 'data' => $orders, 'count' => count($orders), 'days' => (int)$days]);
        } catch (\Exception $e) {
            Response::json(['success' => false, 'error' => 'Failed to fetch recent orders: ' . $e->getMessage()], 500);
        }
    }
}
