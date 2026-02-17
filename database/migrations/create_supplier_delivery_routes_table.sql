-- Create supplier_delivery_routes table
-- Stores delivery time from each supplier to each station
-- CRITICAL: Each supplier has different delivery times to different stations!

CREATE TABLE IF NOT EXISTS supplier_delivery_routes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_id INT NOT NULL,
    station_id INT NOT NULL,
    delivery_days INT NOT NULL COMMENT 'Delivery time in days from this supplier to this station',
    distance_km DECIMAL(10,2) NULL COMMENT 'Distance in kilometers (optional)',
    route_notes TEXT NULL COMMENT 'Route details, restrictions, special conditions',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Foreign keys
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE CASCADE,
    FOREIGN KEY (station_id) REFERENCES stations(id) ON DELETE CASCADE,

    -- Indexes for performance
    INDEX idx_supplier_station (supplier_id, station_id),
    INDEX idx_station (station_id),
    INDEX idx_active_routes (is_active),

    -- Unique constraint: one active route per supplier-station combination
    UNIQUE KEY unique_active_route (supplier_id, station_id, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE supplier_delivery_routes COMMENT = 'Delivery routes and times from suppliers to stations. Critical for procurement planning!';

-- Add check constraint for reasonable delivery days
ALTER TABLE supplier_delivery_routes
ADD CONSTRAINT chk_delivery_days CHECK (delivery_days >= 1 AND delivery_days <= 90);
