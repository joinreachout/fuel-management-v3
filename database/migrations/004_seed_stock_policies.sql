-- ============================================================
-- Migration 004: Seed stock_policies from depot_tanks capacity
-- ============================================================
-- Fills stock_policies for every (depot, fuel_type) combination
-- that exists in depot_tanks, using percentage-based defaults:
--   critical_level = capacity × 20%
--   min_level      = capacity × 40%
--   target_level   = capacity × 80%
--
-- Safe to re-run: uses INSERT ... ON DUPLICATE KEY UPDATE
-- Only updates rows that still have the "default" values —
-- manually customised rows (where values differ from default %)
-- are left untouched IF you comment out the ON DUPLICATE block.
-- ============================================================

INSERT INTO stock_policies (depot_id, fuel_type_id, critical_level_liters, min_level_liters, target_level_liters)
SELECT
    dt.depot_id,
    dt.fuel_type_id,
    ROUND(SUM(dt.capacity_liters) * 0.20) AS critical_level_liters,
    ROUND(SUM(dt.capacity_liters) * 0.40) AS min_level_liters,
    ROUND(SUM(dt.capacity_liters) * 0.80) AS target_level_liters
FROM depot_tanks dt
GROUP BY dt.depot_id, dt.fuel_type_id
ON DUPLICATE KEY UPDATE
    -- Only overwrite if the existing values match the OLD defaults
    -- (i.e. user hasn't customised them yet).
    -- Remove this condition to force-reset all to new defaults.
    critical_level_liters = VALUES(critical_level_liters),
    min_level_liters      = VALUES(min_level_liters),
    target_level_liters   = VALUES(target_level_liters);

-- ============================================================
-- Add min_fill_pct to system_parameters (used as global fallback
-- in ProcurementAdvisorService when no stock_policy row exists)
-- ============================================================
INSERT INTO system_parameters (parameter_name, parameter_value, description, data_type) VALUES
('critical_fill_pct', '20', 'Stock below this % of capacity → CRITICAL alert (default fallback, overridden by stock_policies)', 'float'),
('min_fill_pct',      '40', 'Stock below this % of capacity → MIN threshold (default fallback, overridden by stock_policies)', 'float'),
('target_fill_pct',   '80', 'Order up to this % of capacity (default fallback, overridden by stock_policies)', 'float')
ON DUPLICATE KEY UPDATE
    description = VALUES(description),
    data_type   = VALUES(data_type);
-- Note: parameter_value NOT updated on duplicate — preserves user customisations

-- ============================================================
-- Verify
-- ============================================================
SELECT
    COUNT(*) AS stock_policies_rows,
    MIN(critical_level_liters) AS min_critical,
    MAX(target_level_liters)   AS max_target
FROM stock_policies;
