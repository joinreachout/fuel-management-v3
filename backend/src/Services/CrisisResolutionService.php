<?php

namespace App\Services;

use App\Core\Database;
use App\Models\CrisisCase;
use App\Models\Order;
use App\Utils\UnitConverter;

/**
 * CrisisResolutionService
 *
 * Finds and evaluates crisis resolution options for CATASTROPHE-level depots.
 * Two resolution types:
 *   1. split_delivery — redirect part of a sibling depot's in-transit ERP order
 *   2. transfer       — move current stock from a sibling depot (fallback)
 *
 * System proposes; humans decide and execute.
 */
class CrisisResolutionService
{
    /** Safety buffer days (spec §3.2) — added on top of delivery lead time */
    private const DELIVERY_BUFFER_DAYS = 15;

    // ─────────────────────────────────────────────────────────────────────────
    // PUBLIC: Find options
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Find all resolution options for a critical depot + fuel type.
     * Returns split_delivery options first (preferred), then transfer options.
     *
     * @param int $depotId        The depot in crisis
     * @param int $fuelTypeId
     * @return array {
     *   split_delivery: array,   <- preferred; each has max_split_tons, donor info
     *   transfer:       array,   <- fallback; each has max_transfer_tons, donor info
     *   receiving_depot: array,  <- critical depot info (qty_needed_tons etc.)
     * }
     */
    public static function findOptions(int $depotId, int $fuelTypeId): array
    {
        $critical = self::getCriticalDepotInfo($depotId, $fuelTypeId);
        if (!$critical) {
            return ['error' => 'Depot or fuel type not found', 'split_delivery' => [], 'transfer' => []];
        }

        return [
            'receiving_depot'  => $critical,
            'split_delivery'   => self::findSplitDeliveryOptions($depotId, $fuelTypeId, $critical),
            'transfer'         => self::findTransferOptions($depotId, $fuelTypeId, $critical),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PUBLIC: Accept a proposal → create crisis case + annotate ERP order
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Accept a split-delivery proposal.
     * Creates a crisis_case record and annotates the donor ERP order's notes.
     * Does NOT create compensating POs (user does that in the next steps via UI).
     *
     * @param int   $receivingDepotId   Critical depot
     * @param int   $fuelTypeId
     * @param int   $donorOrderId       ERP order being split
     * @param float $splitQtyTons       Agreed split quantity
     * @param float $qtyNeededTons      How much critical depot needs to reach target
     * @return int  New crisis_case ID
     */
    public static function acceptSplitDelivery(
        int   $receivingDepotId,
        int   $fuelTypeId,
        int   $donorOrderId,
        float $splitQtyTons,
        float $qtyNeededTons
    ): int {
        // Validate: re-run feasibility check before committing
        $options = self::findOptions($receivingDepotId, $fuelTypeId);
        $eligible = array_filter(
            $options['split_delivery'],
            fn($o) => $o['donor_order_id'] === $donorOrderId
        );

        if (empty($eligible)) {
            throw new \RuntimeException("Donor order #{$donorOrderId} is no longer eligible for splitting.");
        }

        $option = array_values($eligible)[0];
        if ($splitQtyTons > $option['max_split_tons'] + 0.01) { // 0.01 float tolerance
            throw new \RuntimeException(
                "Requested split ({$splitQtyTons}t) exceeds safe maximum ({$option['max_split_tons']}t)."
            );
        }

        Database::beginTransaction();
        try {
            // Create crisis case record
            $caseId = CrisisCase::create([
                'case_type'          => 'split_delivery',
                'status'             => 'accepted',
                'receiving_depot_id' => $receivingDepotId,
                'fuel_type_id'       => $fuelTypeId,
                'qty_needed_tons'    => $qtyNeededTons,
                'donor_order_id'     => $donorOrderId,
                'donor_depot_id'     => $option['donor_depot_id'],
                'split_qty_tons'     => $splitQtyTons,
                'notes'              => "Split {$splitQtyTons}t from order #{$option['donor_order_number']} "
                                      . "→ depot #{$receivingDepotId}. Compensating POs pending.",
            ]);

            // Annotate the donor ERP order with a note
            $existingOrder = Order::find($donorOrderId);
            $existingNotes = $existingOrder['notes'] ?? '';
            $annotation    = "[Crisis Case #{$caseId}] Split proposal: {$splitQtyTons}t to be redirected. "
                            . "Compensating PO to be placed.";
            $newNotes = $existingNotes
                ? $existingNotes . "\n" . $annotation
                : $annotation;

            Order::update($donorOrderId, ['notes' => $newNotes]);

            Database::commit();
            return $caseId;

        } catch (\Exception $e) {
            Database::rollback();
            throw $e;
        }
    }

    /**
     * Accept a transfer proposal.
     * Creates a crisis_case record.
     *
     * @param int   $receivingDepotId
     * @param int   $fuelTypeId
     * @param int   $donorDepotId
     * @param float $transferQtyTons
     * @param float $qtyNeededTons
     * @return int  New crisis_case ID
     */
    public static function acceptTransfer(
        int   $receivingDepotId,
        int   $fuelTypeId,
        int   $donorDepotId,
        float $transferQtyTons,
        float $qtyNeededTons
    ): int {
        return CrisisCase::create([
            'case_type'          => 'transfer',
            'status'             => 'accepted',
            'receiving_depot_id' => $receivingDepotId,
            'fuel_type_id'       => $fuelTypeId,
            'qty_needed_tons'    => $qtyNeededTons,
            'donor_depot_id'     => $donorDepotId,
            'split_qty_tons'     => $transferQtyTons,
            'notes'              => "Transfer {$transferQtyTons}t from depot #{$donorDepotId}. "
                                  . "Compensating POs pending.",
        ]);
    }

    /**
     * Link a compensating PO to an existing crisis case
     *
     * @param int    $caseId
     * @param string $poRole   'critical' or 'donor'
     * @param int    $poId     orders.id of the created PO
     * @return void
     */
    public static function linkCompensatingPO(int $caseId, string $poRole, int $poId): void
    {
        $field  = $poRole === 'critical' ? 'po_for_critical_id' : 'po_for_donor_id';
        CrisisCase::update($caseId, [$field => $poId, 'status' => 'monitoring']);
    }

    /**
     * Get all cases (optionally filtered by status)
     *
     * @param string|null $status
     * @return array
     */
    public static function getCases(?string $status = null): array
    {
        $rows = CrisisCase::findAll($status);
        $density = self::getFuelDensities();

        return array_map(function ($row) use ($density) {
            $d = $density[$row['fuel_type_id']] ?? 0.85;
            return [
                'id'                       => (int)$row['id'],
                'case_type'                => $row['case_type'],
                'status'                   => $row['status'],
                'receiving_depot_id'       => (int)$row['receiving_depot_id'],
                'receiving_depot_name'     => $row['receiving_depot_name'],
                'receiving_station_name'   => $row['receiving_station_name'],
                'fuel_type_name'           => $row['fuel_type_name'],
                'fuel_type_code'           => $row['fuel_type_code'],
                'qty_needed_tons'          => (float)$row['qty_needed_tons'],
                'split_qty_tons'           => (float)$row['split_qty_tons'],
                'donor_order_id'           => $row['donor_order_id'] ? (int)$row['donor_order_id'] : null,
                'donor_order_number'       => $row['donor_order_number'],
                'donor_order_delivery_date'=> $row['donor_order_delivery_date'],
                'donor_depot_id'           => $row['donor_depot_id'] ? (int)$row['donor_depot_id'] : null,
                'donor_depot_name'         => $row['donor_depot_name'],
                'po_for_critical_id'       => $row['po_for_critical_id'] ? (int)$row['po_for_critical_id'] : null,
                'po_critical_number'       => $row['po_critical_number'],
                'po_critical_status'       => $row['po_critical_status'],
                'po_critical_qty_tons'     => $row['po_critical_qty_liters']
                                              ? round((float)$row['po_critical_qty_liters'] * $d / 1000, 2)
                                              : null,
                'po_for_donor_id'          => $row['po_for_donor_id'] ? (int)$row['po_for_donor_id'] : null,
                'po_donor_number'          => $row['po_donor_number'],
                'po_donor_status'          => $row['po_donor_status'],
                'po_donor_qty_tons'        => $row['po_donor_qty_liters']
                                             ? round((float)$row['po_donor_qty_liters'] * $d / 1000, 2)
                                             : null,
                'notes'                    => $row['notes'],
                'created_at'               => $row['created_at'],
                'resolved_at'              => $row['resolved_at'],
            ];
        }, $rows);
    }

    /**
     * Mark a case resolved
     */
    public static function resolveCase(int $caseId): void
    {
        CrisisCase::resolve($caseId);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PRIVATE: Core calculation logic
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Find eligible in-transit ERP orders from sibling depots (same station, same fuel type)
     * that can arrive before the critical depot's crisis date.
     *
     * Formula (from spec + docs/PROJECT.md § "Calculation Formulas"):
     *   donor_min_safe_liters = donor.liters_per_day × (days_to_delivery + DELIVERY_BUFFER_DAYS)
     *   donor_spare_liters    = donor.current_stock_liters - donor_min_safe_liters
     *   max_split_tons        = max(0, donor_spare_liters) / 1000 × density
     *
     * Validation (Step F):
     *   donor_after_split    > critical_threshold_liters   ✓
     *   donor_after_delivery > min_threshold_liters        ✓
     */
    private static function findSplitDeliveryOptions(
        int   $depotId,
        int   $fuelTypeId,
        array $critical
    ): array {
        if (!$critical['critical_level_date']) {
            return []; // No consumption data — no crisis date to beat
        }

        $sql = "
            SELECT
                o.id                AS donor_order_id,
                o.order_number      AS donor_order_number,
                o.quantity_liters   AS order_qty_liters,
                o.delivery_date,
                o.depot_id          AS donor_depot_id,
                d.name              AS donor_depot_name,
                d.station_id        AS donor_station_id,
                ft.density,
                COALESCE(sp.liters_per_day, 0) AS donor_daily_consumption,
                SUM(dt.current_stock_liters)   AS donor_current_stock_liters,
                SUM(dt.capacity_liters)        AS donor_capacity_liters,
                COALESCE(pol.critical_level_liters,
                         SUM(dt.capacity_liters) * 0.20) AS donor_critical_liters,
                COALESCE(pol.min_level_liters,
                         SUM(dt.capacity_liters) * 0.40) AS donor_min_liters
            FROM orders o
            JOIN depots d ON o.depot_id = d.id
            JOIN depot_tanks dt ON dt.depot_id = d.id AND dt.fuel_type_id = o.fuel_type_id
            JOIN fuel_types ft ON o.fuel_type_id = ft.id
            LEFT JOIN sales_params sp ON sp.depot_id = d.id
                AND sp.fuel_type_id = o.fuel_type_id
                AND (sp.effective_to IS NULL OR sp.effective_to >= CURDATE())
            LEFT JOIN stock_policies pol ON pol.depot_id = d.id
                AND pol.fuel_type_id = o.fuel_type_id
            WHERE d.station_id = (SELECT station_id FROM depots WHERE id = ?)
              AND d.id != ?
              AND o.fuel_type_id = ?
              AND o.order_type = 'erp_order'
              AND o.status = 'in_transit'
              AND o.delivery_date <= ?
            GROUP BY o.id, o.order_number, o.quantity_liters, o.delivery_date,
                     o.depot_id, d.name, d.station_id, ft.density,
                     sp.liters_per_day, pol.critical_level_liters, pol.min_level_liters
            ORDER BY o.delivery_date ASC
        ";

        $rows = Database::fetchAll($sql, [
            $depotId,
            $depotId,
            $fuelTypeId,
            $critical['critical_level_date'],
        ]);

        $today   = new \DateTime('today');
        $options = [];

        foreach ($rows as $row) {
            $density            = (float)$row['density'];
            $donorStock         = (float)$row['donor_current_stock_liters'];
            $donorConsumption   = (float)$row['donor_daily_consumption'];
            $orderQtyLiters     = (float)$row['order_qty_liters'];
            $donorCriticalLimit = (float)$row['donor_critical_liters'];
            $donorMinLimit      = (float)$row['donor_min_liters'];

            $deliveryDate     = new \DateTime($row['delivery_date']);
            $daysToDelivery   = max(0, (int)$today->diff($deliveryDate)->days);

            // STEP B: How much can donor safely give from current stock?
            $donorMinSafe    = $donorConsumption * ($daysToDelivery + self::DELIVERY_BUFFER_DAYS);
            $donorSpare      = $donorStock - $donorMinSafe;
            $maxSplitLiters  = max(0.0, $donorSpare);
            $maxSplitTons    = round($maxSplitLiters / 1000 * $density, 2);

            if ($maxSplitTons <= 0) {
                continue; // Donor cannot spare anything safely
            }

            // STEP F: Final safety validation
            $splitLiters          = $maxSplitLiters; // Use max for validation
            $donorAfterSplit      = $donorStock - $splitLiters;
            $donorAfterDelivery   = $donorAfterSplit + ($orderQtyLiters - $splitLiters);

            if ($donorAfterSplit < $donorCriticalLimit) {
                // Would push donor below critical — reduce to what's safe
                $safeSplitLiters = $donorStock - $donorCriticalLimit;
                if ($safeSplitLiters <= 0) {
                    continue;
                }
                $maxSplitLiters = $safeSplitLiters;
                $maxSplitTons   = round($maxSplitLiters / 1000 * $density, 2);

                // Re-validate after delivery
                $donorAfterSplit    = $donorStock - $maxSplitLiters;
                $donorAfterDelivery = $donorAfterSplit + ($orderQtyLiters - $maxSplitLiters);
            }

            if ($donorAfterDelivery < $donorMinLimit) {
                continue; // Even after delivery, donor would be below min — skip
            }

            // Calculate the actual split: min(what donor can give, what receiver needs)
            $qtyNeededLiters = $critical['qty_needed_liters'];
            $splitQtyLiters  = min($maxSplitLiters, $qtyNeededLiters);
            $splitQtyTons    = round($splitQtyLiters / 1000 * $density, 2);

            $options[] = [
                'type'                    => 'split_delivery',
                'donor_order_id'          => (int)$row['donor_order_id'],
                'donor_order_number'      => $row['donor_order_number'],
                'donor_depot_id'          => (int)$row['donor_depot_id'],
                'donor_depot_name'        => $row['donor_depot_name'],
                'donor_current_stock_tons'=> round($donorStock / 1000 * $density, 2),
                'donor_daily_consumption_tons' => round($donorConsumption / 1000 * $density, 2),
                'order_qty_tons'          => round($orderQtyLiters / 1000 * $density, 2),
                'delivery_date'           => $row['delivery_date'],
                'days_to_delivery'        => $daysToDelivery,
                'max_split_tons'          => $maxSplitTons,
                'suggested_split_tons'    => $splitQtyTons,
                // Impact on donor after split + delivery
                'donor_stock_after_split_tons'    => round($donorAfterSplit / 1000 * $density, 2),
                'donor_stock_after_delivery_tons' => round($donorAfterDelivery / 1000 * $density, 2),
                // Pre-calculated compensating PO quantities (for UI pre-fill)
                'po_for_critical_tons'    => self::roundUpTons(
                    max(0, $critical['qty_needed_tons'] - $splitQtyTons)
                ),
                'po_for_donor_tons'       => self::roundUpTons($splitQtyTons),
            ];
        }

        return $options;
    }

    /**
     * Find sibling depots with surplus stock that can be physically transferred.
     * Fallback when no in-transit orders are available.
     *
     * Formula:
     *   donor_min_safe_liters = donor.liters_per_day × (days_until_donor_delivery + BUFFER)
     *   If no upcoming delivery: × (best_delivery_days + BUFFER)
     *   donor_spare = donor.current_stock - donor_min_safe
     */
    private static function findTransferOptions(
        int   $depotId,
        int   $fuelTypeId,
        array $critical
    ): array {
        $sql = "
            SELECT
                d.id                           AS donor_depot_id,
                d.name                         AS donor_depot_name,
                ft.density,
                COALESCE(sp.liters_per_day, 0) AS donor_daily_consumption,
                SUM(dt.current_stock_liters)   AS donor_current_stock_liters,
                SUM(dt.capacity_liters)        AS donor_capacity_liters,
                COALESCE(pol.critical_level_liters,
                         SUM(dt.capacity_liters) * 0.20) AS donor_critical_liters,
                COALESCE(pol.min_level_liters,
                         SUM(dt.capacity_liters) * 0.40) AS donor_min_liters,
                -- Next active PO/ERP delivery for this depot+fuel
                (SELECT MIN(o2.delivery_date)
                 FROM orders o2
                 WHERE o2.depot_id = d.id
                   AND o2.fuel_type_id = ?
                   AND o2.status IN ('planned','confirmed','in_transit')
                   AND o2.delivery_date >= CURDATE()
                ) AS next_delivery_date
            FROM depot_tanks dt
            JOIN depots d ON dt.depot_id = d.id
            JOIN fuel_types ft ON dt.fuel_type_id = ft.id
            LEFT JOIN sales_params sp ON sp.depot_id = d.id
                AND sp.fuel_type_id = dt.fuel_type_id
                AND (sp.effective_to IS NULL OR sp.effective_to >= CURDATE())
            LEFT JOIN stock_policies pol ON pol.depot_id = d.id
                AND pol.fuel_type_id = dt.fuel_type_id
            WHERE d.station_id = (SELECT station_id FROM depots WHERE id = ?)
              AND d.id != ?
              AND dt.fuel_type_id = ?
            GROUP BY d.id, d.name, ft.density, sp.liters_per_day,
                     pol.critical_level_liters, pol.min_level_liters
        ";

        $rows = Database::fetchAll($sql, [$fuelTypeId, $depotId, $depotId, $fuelTypeId]);

        $today   = new \DateTime('today');
        $options = [];

        foreach ($rows as $row) {
            $density          = (float)$row['density'];
            $donorStock       = (float)$row['donor_current_stock_liters'];
            $donorConsumption = (float)$row['donor_daily_consumption'];
            $donorCritical    = (float)$row['donor_critical_liters'];
            $donorMin         = (float)$row['donor_min_liters'];

            // Days until donor's next delivery (or use default lead time if no delivery)
            $daysToDelivery = self::DELIVERY_BUFFER_DAYS + 7; // pessimistic default
            if ($row['next_delivery_date']) {
                $nextDel = new \DateTime($row['next_delivery_date']);
                $daysToDelivery = max(0, (int)$today->diff($nextDel)->days);
            }

            // How much can donor safely give away right now?
            $donorMinSafe   = $donorConsumption * ($daysToDelivery + self::DELIVERY_BUFFER_DAYS);
            $donorSpare     = $donorStock - $donorMinSafe;
            $maxTransferLiters = max(0.0, $donorSpare);

            // Ensure it won't push donor below critical
            if (($donorStock - $maxTransferLiters) < $donorCritical) {
                $maxTransferLiters = max(0.0, $donorStock - $donorCritical);
            }

            $maxTransferTons = round($maxTransferLiters / 1000 * $density, 2);
            if ($maxTransferTons <= 0) {
                continue;
            }

            $qtyNeededLiters  = $critical['qty_needed_liters'];
            $transferLiters   = min($maxTransferLiters, $qtyNeededLiters);
            $transferTons     = round($transferLiters / 1000 * $density, 2);

            $options[] = [
                'type'                         => 'transfer',
                'donor_depot_id'               => (int)$row['donor_depot_id'],
                'donor_depot_name'             => $row['donor_depot_name'],
                'donor_current_stock_tons'     => round($donorStock / 1000 * $density, 2),
                'donor_daily_consumption_tons' => round($donorConsumption / 1000 * $density, 2),
                'next_delivery_date'           => $row['next_delivery_date'],
                'days_to_delivery'             => $daysToDelivery,
                'max_transfer_tons'            => $maxTransferTons,
                'suggested_transfer_tons'      => $transferTons,
                'donor_stock_after_transfer_tons' => round(
                    ($donorStock - $transferLiters) / 1000 * $density, 2
                ),
                // Pre-calculated PO pre-fill quantities
                'po_for_critical_tons' => self::roundUpTons(
                    max(0, $critical['qty_needed_tons'] - $transferTons)
                ),
                'po_for_donor_tons'    => self::roundUpTons($transferTons),
            ];
        }

        return $options;
    }

    /**
     * Get full info about the critical depot needed for calculations.
     */
    private static function getCriticalDepotInfo(int $depotId, int $fuelTypeId): ?array
    {
        $row = Database::fetchOne("
            SELECT
                d.id, d.name, d.station_id, s.name AS station_name,
                ft.density, ft.name AS fuel_type_name,
                COALESCE(sp.liters_per_day, 0) AS daily_consumption,
                SUM(dt.current_stock_liters)   AS current_stock_liters,
                SUM(dt.capacity_liters)        AS capacity_liters,
                COALESCE(pol.critical_level_liters,
                         SUM(dt.capacity_liters) * 0.20) AS critical_liters,
                COALESCE(pol.min_level_liters,
                         SUM(dt.capacity_liters) * 0.40) AS min_liters,
                COALESCE(pol.target_level_liters,
                         SUM(dt.capacity_liters) * 0.80) AS target_liters
            FROM depot_tanks dt
            JOIN depots d ON dt.depot_id = d.id
            JOIN stations s ON d.station_id = s.id
            JOIN fuel_types ft ON dt.fuel_type_id = ft.id
            LEFT JOIN sales_params sp ON sp.depot_id = d.id
                AND sp.fuel_type_id = dt.fuel_type_id
                AND (sp.effective_to IS NULL OR sp.effective_to >= CURDATE())
            LEFT JOIN stock_policies pol ON pol.depot_id = d.id
                AND pol.fuel_type_id = dt.fuel_type_id
            WHERE d.id = ? AND dt.fuel_type_id = ?
            GROUP BY d.id, d.name, d.station_id, s.name, ft.density, ft.name,
                     sp.liters_per_day, pol.critical_level_liters,
                     pol.min_level_liters, pol.target_level_liters
        ", [$depotId, $fuelTypeId]);

        if (!$row) {
            return null;
        }

        $density     = (float)$row['density'];
        $stockLiters = (float)$row['current_stock_liters'];
        $targetLiters= (float)$row['target_liters'];
        $critLiters  = (float)$row['critical_liters'];
        $dailyCons   = (float)$row['daily_consumption'];

        $qtyNeededLiters = max(0.0, $targetLiters - $stockLiters);

        // Days until critical (for determining critical_level_date)
        $daysUntilCritical = null;
        $criticalLevelDate = null;
        if ($dailyCons > 0) {
            $daysUntilCritical = max(0, round(($stockLiters - $critLiters) / $dailyCons, 1));
            $criticalLevelDate = date('Y-m-d', strtotime("+{$daysUntilCritical} days"));
        }

        return [
            'depot_id'             => $depotId,
            'depot_name'           => $row['name'],
            'station_name'         => $row['station_name'],
            'fuel_type_name'       => $row['fuel_type_name'],
            'density'              => $density,
            'current_stock_liters' => $stockLiters,
            'current_stock_tons'   => round($stockLiters / 1000 * $density, 2),
            'target_liters'        => $targetLiters,
            'critical_liters'      => $critLiters,
            'qty_needed_liters'    => $qtyNeededLiters,
            'qty_needed_tons'      => round($qtyNeededLiters / 1000 * $density, 2),
            'days_until_critical'  => $daysUntilCritical,
            'critical_level_date'  => $criticalLevelDate,
        ];
    }

    /**
     * Round up tons to nearest sensible order unit (same as frontend roundUpTons)
     *   < 50t  → nearest 5t
     *   50–200 → nearest 10t
     *   200–500→ nearest 25t
     *   > 500  → nearest 50t
     */
    public static function roundUpTons(float $tons): float
    {
        if ($tons <= 0) {
            return 0.0;
        }
        if ($tons < 50) {
            $step = 5;
        } elseif ($tons < 200) {
            $step = 10;
        } elseif ($tons < 500) {
            $step = 25;
        } else {
            $step = 50;
        }
        return (float)(ceil($tons / $step) * $step);
    }

    /**
     * Get fuel type densities keyed by fuel_type_id
     */
    private static function getFuelDensities(): array
    {
        $rows = Database::fetchAll('SELECT id, density FROM fuel_types');
        $map  = [];
        foreach ($rows as $row) {
            $map[(int)$row['id']] = (float)$row['density'];
        }
        return $map;
    }
}
