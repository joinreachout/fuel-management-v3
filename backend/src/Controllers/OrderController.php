<?php

namespace App\Controllers;

use App\Core\Response;
use App\Models\Order;

/**
 * Order Controller
 * Handles HTTP requests for order resources
 */
class OrderController
{
    /**
     * GET /api/orders
     * Get all orders
     */
    public function index(): void
    {
        try {
            $orders = Order::all();

            Response::json([
                'success' => true,
                'data' => $orders,
                'count' => count($orders)
            ]);
        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'error' => 'Failed to fetch orders: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/orders/{id}
     * Get single order by ID
     */
    public function show(int $id): void
    {
        try {
            $order = Order::find($id);

            if (!$order) {
                Response::json([
                    'success' => false,
                    'error' => 'Order not found'
                ], 404);
                return;
            }

            Response::json([
                'success' => true,
                'data' => $order
            ]);
        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'error' => 'Failed to fetch order: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/orders/pending
     * Get pending orders
     */
    public function pending(): void
    {
        try {
            $orders = Order::getPending();

            Response::json([
                'success' => true,
                'data' => $orders,
                'count' => count($orders)
            ]);
        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'error' => 'Failed to fetch pending orders: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/orders/summary
     * Get orders summary by fuel type
     */
    public function summary(): void
    {
        try {
            $summary = Order::getSummaryByFuelType();

            Response::json([
                'success' => true,
                'data' => $summary,
                'count' => count($summary)
            ]);
        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'error' => 'Failed to fetch orders summary: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/orders/recent
     * Get recent orders (last 30 days)
     */
    public function recent(): void
    {
        try {
            $days = $_GET['days'] ?? 30;
            $orders = Order::getRecent((int)$days);

            Response::json([
                'success' => true,
                'data' => $orders,
                'count' => count($orders),
                'days' => (int)$days
            ]);
        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'error' => 'Failed to fetch recent orders: ' . $e->getMessage()
            ], 500);
        }
    }
}
