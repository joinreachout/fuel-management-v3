<?php

namespace App\Services;

use App\Core\Database;

/**
 * Parameters Service
 * Provides read/write access to all system configuration tables:
 *   - system_parameters  (global planning thresholds)
 *   - fuel_types         (density, cost_per_ton — editable)
 *   - sales_params       (daily consumption per depot/fuel)
 *   - stock_policies     (min/critical/target levels per depot/fuel)
 *   - supplier_station_offers (prices per supplier/station/fuel)
 *   - depot_tanks        (capacity reference, read-only here)
 */
class ParametersService
{
    // ─────────────────────────────────────────────
    // SYSTEM PARAMETERS
    // ─────────────────────────────────────────────

    /**
     * GET all system parameters, grouped by category
     */
    public static function getSystemParameters(): array
    {
        // Real schema: parameter_name, parameter_value, data_type, description (no category)
        $rows = Database::fetchAll(
            "SELECT parameter_name, parameter_value, data_type, description
             FROM system_parameters
             ORDER BY parameter_name"
        );

        // Flat list (no category in this schema version)
        $grouped = ['general' => []];
        foreach ($rows as $row) {
            $grouped['general'][] = [
                'key'         => $row['parameter_name'],
                'value'       => self::castValue($row['parameter_value'], $row['data_type']),
                'raw_value'   => $row['parameter_value'],
                'type'        => $row['data_type'],
                'description' => $row['description'],
            ];
        }
        return $grouped;
    }

    /**
     * UPDATE a single system parameter
     */
    public static function updateSystemParameter(string $key, string $value): bool
    {
        $affected = Database::execute(
            "UPDATE system_parameters SET parameter_value = ? WHERE parameter_name = ?",
            [$value, $key]
        );
        return $affected > 0;
    }

    // ─────────────────────────────────────────────
    // FUEL TYPES  (density + cost_per_ton editable)
    // ─────────────────────────────────────────────

    /**
     * GET all fuel types with editable fields
     */
    public static function getFuelTypes(): array
    {
        // Real schema: no is_active column in fuel_types
        return Database::fetchAll(
            "SELECT id, name, code, density, cost_per_ton
             FROM fuel_types
             ORDER BY name"
        );
    }

    /**
     * UPDATE fuel type: only density and cost_per_ton are editable here
     */
    public static function updateFuelType(int $id, float $density, ?float $costPerTon): bool
    {
        if ($density <= 0 || $density > 2) {
            throw new \InvalidArgumentException("Density must be between 0 and 2 kg/L");
        }
        $affected = Database::execute(
            "UPDATE fuel_types SET density = ?, cost_per_ton = ? WHERE id = ?",
            [$density, $costPerTon, $id]
        );
        return $affected > 0;
    }

    // ─────────────────────────────────────────────
    // SALES PARAMS  (daily consumption per depot/fuel)
    // ─────────────────────────────────────────────

    /**
     * GET all active sales params with depot and fuel type names
     */
    public static function getSalesParams(): array
    {
        return Database::fetchAll(
            "SELECT
                sp.id,
                sp.depot_id,
                d.name  AS depot_name,
                s.name  AS station_name,
                sp.fuel_type_id,
                ft.name AS fuel_type_name,
                ft.code AS fuel_type_code,
                sp.liters_per_day,
                sp.effective_from,
                sp.effective_to
             FROM sales_params sp
             INNER JOIN depots d      ON sp.depot_id      = d.id
             INNER JOIN stations s    ON d.station_id     = s.id
             INNER JOIN fuel_types ft ON sp.fuel_type_id  = ft.id
             WHERE sp.effective_to IS NULL OR sp.effective_to >= CURDATE()
             ORDER BY s.name, d.name, ft.name"
        );
    }

    /**
     * UPDATE liters_per_day for a specific sales_param row
     */
    public static function updateSalesParam(int $id, float $litersPerDay): bool
    {
        if ($litersPerDay < 0) {
            throw new \InvalidArgumentException("liters_per_day cannot be negative");
        }
        $affected = Database::execute(
            "UPDATE sales_params SET liters_per_day = ? WHERE id = ?",
            [$litersPerDay, $id]
        );
        return $affected > 0;
    }

    // ─────────────────────────────────────────────
    // STOCK POLICIES  (per depot/fuel thresholds)
    // ─────────────────────────────────────────────

    /**
     * GET all stock policies with names
     */
    public static function getStockPolicies(): array
    {
        return Database::fetchAll(
            "SELECT
                pol.id,
                pol.depot_id,
                d.name  AS depot_name,
                s.name  AS station_name,
                pol.fuel_type_id,
                ft.name AS fuel_type_name,
                ft.code AS fuel_type_code,
                pol.min_level_liters,
                pol.critical_level_liters,
                pol.target_level_liters,
                dt.capacity_liters
             FROM stock_policies pol
             INNER JOIN depots d      ON pol.depot_id     = d.id
             INNER JOIN stations s    ON d.station_id     = s.id
             INNER JOIN fuel_types ft ON pol.fuel_type_id = ft.id
             LEFT JOIN (
                SELECT depot_id, fuel_type_id, SUM(capacity_liters) AS capacity_liters
                FROM depot_tanks GROUP BY depot_id, fuel_type_id
             ) dt ON dt.depot_id = pol.depot_id AND dt.fuel_type_id = pol.fuel_type_id
             ORDER BY s.name, d.name, ft.name"
        );
    }

    /**
     * UPDATE stock policy thresholds
     */
    public static function updateStockPolicy(
        int $id,
        ?float $minLevel,
        ?float $criticalLevel,
        ?float $targetLevel
    ): bool {
        $affected = Database::execute(
            "UPDATE stock_policies
             SET min_level_liters = ?, critical_level_liters = ?, target_level_liters = ?
             WHERE id = ?",
            [$minLevel, $criticalLevel, $targetLevel, $id]
        );
        return $affected > 0;
    }

    // ─────────────────────────────────────────────
    // SUPPLIER OFFERS  (price_per_ton editable)
    // ─────────────────────────────────────────────

    /**
     * GET all supplier offers with names
     */
    public static function getSupplierOffers(): array
    {
        return Database::fetchAll(
            "SELECT
                sso.id,
                sso.supplier_id,
                sup.name   AS supplier_name,
                sso.station_id,
                st.name    AS station_name,
                sso.fuel_type_id,
                ft.name    AS fuel_type_name,
                ft.code    AS fuel_type_code,
                sso.price_per_ton,
                sso.delivery_days,
                sso.currency,
                sso.is_active
             FROM supplier_station_offers sso
             INNER JOIN suppliers  sup ON sso.supplier_id  = sup.id
             INNER JOIN stations   st  ON sso.station_id   = st.id
             INNER JOIN fuel_types ft  ON sso.fuel_type_id = ft.id
             ORDER BY sup.name, st.name, ft.name"
        );
    }

    /**
     * UPDATE price_per_ton and delivery_days for a supplier offer
     */
    public static function updateSupplierOffer(
        int $id,
        float $pricePerTon,
        int $deliveryDays
    ): bool {
        if ($pricePerTon <= 0) {
            throw new \InvalidArgumentException("price_per_ton must be positive");
        }
        if ($deliveryDays < 1 || $deliveryDays > 90) {
            throw new \InvalidArgumentException("delivery_days must be between 1 and 90");
        }
        $affected = Database::execute(
            "UPDATE supplier_station_offers
             SET price_per_ton = ?, delivery_days = ?
             WHERE id = ?",
            [$pricePerTon, $deliveryDays, $id]
        );
        return $affected > 0;
    }

    // ─────────────────────────────────────────────
    // DEPOT TANKS  (reference data, read-only)
    // ─────────────────────────────────────────────

    /**
     * GET all depot tanks with depot and fuel type names (reference view)
     */
    public static function getDepotTanks(): array
    {
        return Database::fetchAll(
            "SELECT
                dt.id,
                dt.depot_id,
                d.name  AS depot_name,
                s.name  AS station_name,
                dt.fuel_type_id,
                ft.name AS fuel_type_name,
                ft.code AS fuel_type_code,
                dt.current_stock_liters,
                dt.capacity_liters,
                ROUND(dt.current_stock_liters / dt.capacity_liters * 100, 1) AS fill_pct
             FROM depot_tanks dt
             INNER JOIN depots d      ON dt.depot_id     = d.id
             INNER JOIN stations s    ON d.station_id    = s.id
             INNER JOIN fuel_types ft ON dt.fuel_type_id = ft.id
             ORDER BY s.name, d.name, ft.name"
        );
    }

    // ─────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────

    private static function castValue(string $value, ?string $type): mixed
    {
        return match ($type) {
            'integer' => (int)$value,
            'float'   => (float)$value,
            'boolean' => (bool)$value,
            default   => $value,
        };
    }
}
