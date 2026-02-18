-- Migration 003: Rename sales_params.liters_per_day → tons_per_day
-- Reason: operators always enter daily consumption in tons, not litres.
-- The stored values (e.g. 817.50) are tons. Rename for honesty.
-- No data conversion needed — numbers stay the same, unit label changes.

ALTER TABLE sales_params
    CHANGE COLUMN liters_per_day tons_per_day DECIMAL(10,2) NOT NULL DEFAULT 0.00
    COMMENT 'Daily fuel consumption in metric tons (t/day)';
