-- ============================================
-- MIGRATION 002: Normalize supplier_station_offers
-- + Clean up depots table
-- ============================================
--
-- Changes:
-- 1. DROP depots.capacity_m3 (redundant, always NULL, derivable from sum of depot_tanks)
-- 2. DROP depots.daily_unloading_capacity_tons (always NULL, never used)
-- 3. REPLACE supplier_station_offers with normalized structure:
--    Old: one row per (supplier, station) with price_diesel_b7, price_gas_92... columns
--    New: one row per (supplier, station, fuel_type) with price_per_ton
--
-- See ARCHITECTURE_UNITS_AND_CONVERSIONS.md for rationale
-- ============================================

-- Step 1: Clean up depots table
ALTER TABLE depots
    DROP COLUMN IF EXISTS capacity_m3,
    DROP COLUMN IF EXISTS daily_unloading_capacity_tons;

-- Step 2: Drop old supplier_station_offers table
DROP TABLE IF EXISTS supplier_station_offers;

-- Step 3: Create normalized supplier_station_offers
CREATE TABLE supplier_station_offers (
    id INT AUTO_INCREMENT PRIMARY KEY,

    -- Relations
    supplier_id INT NOT NULL,
    station_id  INT NOT NULL,
    fuel_type_id INT NOT NULL,

    -- Delivery (per route, not per fuel type)
    delivery_days INT NOT NULL COMMENT 'Days from supplier to this station',
    distance_km   DECIMAL(10,2) NULL COMMENT 'Distance in km (informational)',

    -- Price (per ton, as per industry standard)
    price_per_ton DECIMAL(10,2) NOT NULL COMMENT 'Price per ton in currency below',
    currency      VARCHAR(3)    NOT NULL DEFAULT 'USD',

    -- Order constraints (can override system_parameters defaults per supplier)
    min_order_tons DECIMAL(10,2) NULL COMMENT 'NULL = use system_parameters.min_order_tons',
    max_order_tons DECIMAL(10,2) NULL COMMENT 'NULL = no upper limit',

    -- Validity
    valid_from DATE NOT NULL DEFAULT (CURDATE()),
    valid_to   DATE NULL COMMENT 'NULL = currently active',
    is_active  TINYINT(1) NOT NULL DEFAULT 1,

    -- Audit
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- One active price per (supplier, station, fuel_type)
    UNIQUE KEY uk_supplier_station_fuel (supplier_id, station_id, fuel_type_id, valid_from),

    FOREIGN KEY (supplier_id)  REFERENCES suppliers(id)   ON DELETE CASCADE,
    FOREIGN KEY (station_id)   REFERENCES stations(id)    ON DELETE CASCADE,
    FOREIGN KEY (fuel_type_id) REFERENCES fuel_types(id)  ON DELETE CASCADE,

    INDEX idx_station_fuel    (station_id, fuel_type_id),
    INDEX idx_supplier_active (supplier_id, is_active),
    INDEX idx_active          (is_active)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Supplier prices and delivery times per station per fuel type. Prices in tons (industry standard).';

-- Step 4: Seed with real data
-- Supplier 8 = НПЗ Кара май Ойл-Тараз (departure: Бурыл/Бурул)
-- Station 252 = Станция ОШ
-- Station 250 = Станция Бишкек
-- Fuel types: 25=Diesel B7, 24=А-92, 31=А-95, 33=Diesel B10

INSERT INTO supplier_station_offers
    (supplier_id, station_id, fuel_type_id, delivery_days, price_per_ton, currency, valid_from)
VALUES
-- НПЗ Кара май Ойл-Тараз → Станция ОШ
(8, 252, 25, 20, 840.00, 'USD', '2026-01-01'),  -- Diesel B7
(8, 252, 24, 20, 820.00, 'USD', '2026-01-01'),  -- А-92
(8, 252, 31, 20, 850.00, 'USD', '2026-01-01'),  -- А-95

-- НПЗ Кара май Ойл-Тараз → Станция Бишкек
(8, 250, 25, 18, 830.00, 'USD', '2026-01-01'),  -- Diesel B7
(8, 250, 24, 18, 810.00, 'USD', '2026-01-01'),  -- А-92
(8, 250, 31, 18, 840.00, 'USD', '2026-01-01');  -- А-95

-- ============================================
-- NOTE: Add other suppliers/stations/fuels via Parameters UI
-- ============================================
