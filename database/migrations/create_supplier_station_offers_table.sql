-- RECOMMENDED SCHEMA: supplier_station_offers
-- Combines pricing and delivery info in one table
-- Avoids duplication while keeping queries simple

CREATE TABLE IF NOT EXISTS supplier_station_offers (
    id INT AUTO_INCREMENT PRIMARY KEY,

    -- Relations
    supplier_id INT NOT NULL,
    station_id INT NOT NULL,

    -- Delivery Information
    delivery_days INT NOT NULL COMMENT 'Delivery time from supplier to this station',
    distance_km DECIMAL(10,2) NULL COMMENT 'Distance in kilometers',
    route_notes TEXT NULL COMMENT 'Route details, restrictions',

    -- Pricing (common fuel types as columns - easier queries)
    price_diesel_b7 DECIMAL(10,2) NULL COMMENT 'Price per ton for Diesel B7',
    price_diesel_b10 DECIMAL(10,2) NULL COMMENT 'Price per ton for Diesel B10',
    price_gas_80 DECIMAL(10,2) NULL COMMENT 'Price per ton for A-80',
    price_gas_92 DECIMAL(10,2) NULL COMMENT 'Price per ton for A-92',
    price_gas_95 DECIMAL(10,2) NULL COMMENT 'Price per ton for A-95',
    price_gas_98 DECIMAL(10,2) NULL COMMENT 'Price per ton for A-98',
    price_jet DECIMAL(10,2) NULL COMMENT 'Price per ton for Jet Fuel',
    price_lpg DECIMAL(10,2) NULL COMMENT 'Price per ton for LPG',
    price_mtbe DECIMAL(10,2) NULL COMMENT 'Price per ton for MTBE',
    currency VARCHAR(3) DEFAULT 'USD' COMMENT 'Currency code (USD, KGS, etc)',

    -- Order Constraints
    min_order_tons DECIMAL(10,2) NULL COMMENT 'Minimum order quantity',
    max_order_tons DECIMAL(10,2) NULL COMMENT 'Maximum order quantity',

    -- Validity & Status
    is_active TINYINT(1) DEFAULT 1 COMMENT '1=active, 0=historical',
    valid_from DATE NOT NULL COMMENT 'Price/route valid from date',
    valid_until DATE NULL COMMENT 'Price/route valid until date (NULL=indefinite)',

    notes TEXT NULL COMMENT 'Additional notes about this offer',

    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Foreign Keys
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE CASCADE,
    FOREIGN KEY (station_id) REFERENCES stations(id) ON DELETE CASCADE,

    -- Indexes
    INDEX idx_supplier_station (supplier_id, station_id),
    INDEX idx_station_active (station_id, is_active),
    INDEX idx_active_offers (is_active, valid_from, valid_until),

    -- Unique constraint: one active offer per supplier-station
    UNIQUE KEY unique_active_offer (supplier_id, station_id, is_active)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Supplier offers: combines pricing and delivery info per station';

-- Add constraints
ALTER TABLE supplier_station_offers
ADD CONSTRAINT chk_delivery_days CHECK (delivery_days >= 1 AND delivery_days <= 90),
ADD CONSTRAINT chk_valid_dates CHECK (valid_until IS NULL OR valid_until >= valid_from);
