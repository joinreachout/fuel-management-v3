-- Create supplier_prices table to store fuel prices per supplier
-- This avoids duplicating supplier data and follows proper normalization

CREATE TABLE IF NOT EXISTS supplier_prices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_id INT NOT NULL,
    fuel_type_id INT NOT NULL,
    price_per_ton DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'USD',
    min_quantity_tons DECIMAL(10,2) NULL COMMENT 'Minimum order quantity in tons',
    max_quantity_tons DECIMAL(10,2) NULL COMMENT 'Maximum order quantity in tons',
    is_active TINYINT(1) DEFAULT 1,
    valid_from DATE NOT NULL COMMENT 'Price validity start date',
    valid_until DATE NULL COMMENT 'Price validity end date (NULL = indefinite)',
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Foreign keys
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE CASCADE,
    FOREIGN KEY (fuel_type_id) REFERENCES fuel_types(id) ON DELETE CASCADE,

    -- Indexes for performance
    INDEX idx_supplier_fuel (supplier_id, fuel_type_id),
    INDEX idx_active_prices (is_active, valid_from, valid_until),
    INDEX idx_fuel_type (fuel_type_id),

    -- Unique constraint: one active price per supplier-fuel combination
    UNIQUE KEY unique_active_price (supplier_id, fuel_type_id, is_active, valid_from)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add some comments
ALTER TABLE supplier_prices COMMENT = 'Stores fuel prices from suppliers. Allows price history tracking.';
