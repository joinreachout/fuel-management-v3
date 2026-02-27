<?php

namespace App\Controllers;

use App\Services\CrisisResolutionService;
use App\Models\CrisisCase;

/**
 * CrisisController
 * Handles API requests for crisis resolution workflow.
 *
 * Routes:
 *   GET  /api/crisis/options          → findOptions (split + transfer candidates)
 *   POST /api/crisis/accept           → accept a proposal, create crisis case
 *   POST /api/crisis/link-po          → link a compensating PO to a case
 *   GET  /api/crisis/cases            → list all cases
 *   POST /api/crisis/cases/{id}/resolve → mark a case resolved
 */
class CrisisController
{
    /**
     * GET /api/crisis/options?depot_id=X&fuel_type_id=Y
     * Returns split_delivery and transfer options for a critical depot.
     */
    public function getOptions(): void
    {
        try {
            $depotId     = isset($_GET['depot_id'])     ? (int)$_GET['depot_id']     : null;
            $fuelTypeId  = isset($_GET['fuel_type_id']) ? (int)$_GET['fuel_type_id'] : null;

            if (!$depotId || !$fuelTypeId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'depot_id and fuel_type_id are required']);
                return;
            }

            $options = CrisisResolutionService::findOptions($depotId, $fuelTypeId);

            http_response_code(200);
            echo json_encode(['success' => true, 'data' => $options]);

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * POST /api/crisis/accept
     * Accept a split-delivery or transfer proposal → creates crisis_case record.
     *
     * Body (JSON):
     *   type               'split_delivery' | 'transfer'
     *   receiving_depot_id int
     *   fuel_type_id       int
     *   qty_needed_tons    float
     *   split_qty_tons     float   (agreed quantity)
     *   donor_order_id     int     (split_delivery only)
     *   donor_depot_id     int     (transfer only)
     */
    public function acceptProposal(): void
    {
        try {
            $body = json_decode(file_get_contents('php://input'), true);

            if (!$body) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid JSON body']);
                return;
            }

            $type             = $body['type']               ?? null;
            $receivingDepotId = (int)($body['receiving_depot_id'] ?? 0);
            $fuelTypeId       = (int)($body['fuel_type_id']       ?? 0);
            $qtyNeededTons    = (float)($body['qty_needed_tons']  ?? 0);
            $splitQtyTons     = (float)($body['split_qty_tons']   ?? 0);

            if (!$type || !$receivingDepotId || !$fuelTypeId || $splitQtyTons <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Missing required fields']);
                return;
            }

            if ($type === 'split_delivery') {
                $donorOrderId = (int)($body['donor_order_id'] ?? 0);
                if (!$donorOrderId) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'donor_order_id required for split_delivery']);
                    return;
                }
                $caseId = CrisisResolutionService::acceptSplitDelivery(
                    $receivingDepotId, $fuelTypeId, $donorOrderId, $splitQtyTons, $qtyNeededTons
                );

            } elseif ($type === 'transfer') {
                $donorDepotId = (int)($body['donor_depot_id'] ?? 0);
                if (!$donorDepotId) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'donor_depot_id required for transfer']);
                    return;
                }
                $caseId = CrisisResolutionService::acceptTransfer(
                    $receivingDepotId, $fuelTypeId, $donorDepotId, $splitQtyTons, $qtyNeededTons
                );

            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => "Unknown type: {$type}"]);
                return;
            }

            $case = CrisisCase::findById($caseId);

            http_response_code(201);
            echo json_encode([
                'success' => true,
                'case_id' => $caseId,
                'data'    => $case,
            ]);

        } catch (\RuntimeException $e) {
            http_response_code(409); // Conflict — validation failed
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * POST /api/crisis/link-po
     * Link a compensating PO (just created) to an existing crisis case.
     *
     * Body (JSON):
     *   case_id   int
     *   po_role   'critical' | 'donor'
     *   po_id     int   (orders.id of the newly created PO)
     */
    public function linkPO(): void
    {
        try {
            $body     = json_decode(file_get_contents('php://input'), true);
            $caseId   = (int)($body['case_id'] ?? 0);
            $poRole   = $body['po_role'] ?? '';
            $poId     = (int)($body['po_id'] ?? 0);

            if (!$caseId || !in_array($poRole, ['critical', 'donor']) || !$poId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'case_id, po_role (critical|donor), and po_id are required']);
                return;
            }

            CrisisResolutionService::linkCompensatingPO($caseId, $poRole, $poId);

            http_response_code(200);
            echo json_encode(['success' => true, 'case_id' => $caseId, 'po_role' => $poRole, 'po_id' => $poId]);

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * GET /api/crisis/cases
     * Returns all crisis cases, optionally filtered by status.
     *
     * Query params:
     *   status  'proposed'|'accepted'|'monitoring'|'resolved'  (optional)
     */
    public function getCases(): void
    {
        try {
            $status = $_GET['status'] ?? null;
            $cases  = CrisisResolutionService::getCases($status);

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data'    => $cases,
                'count'   => count($cases),
            ]);

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * POST /api/crisis/cases/{id}/resolve
     * Mark a crisis case as resolved.
     */
    public function resolveCase(int $id): void
    {
        try {
            CrisisResolutionService::resolveCase($id);

            http_response_code(200);
            echo json_encode(['success' => true, 'case_id' => $id, 'status' => 'resolved']);

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}
