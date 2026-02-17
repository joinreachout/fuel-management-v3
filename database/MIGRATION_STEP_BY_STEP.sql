-- ===============================================
-- DATABASE MIGRATION: Supplier Schema Upgrade
-- Date: 2026-02-17
-- Execute these statements ONE BY ONE in phpMyAdmin
-- ===============================================

-- STEP 1: Create supplier_station_offers table
-- Copy and execute this entire CREATE TABLE statement
-- -----------------------------------------------

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


-- STEP 2: Verify table was created
-- Execute this to check
-- -----------------------------------------------

SHOW TABLES LIKE 'supplier_station_offers';
DESCRIBE supplier_station_offers;


-- STEP 3: Insert seed data (example offers)
-- Execute this entire INSERT block
-- -----------------------------------------------

-- Supplier 8: НПЗ Кара май Ойл-Тараз (closest, avg 18 days)
INSERT INTO supplier_station_offers
(supplier_id, station_id, delivery_days, distance_km, price_diesel_b7, price_diesel_b10, price_gas_92, price_gas_95, price_gas_98, currency, is_active, valid_from)
VALUES
    (8, 249, 16, 450, 830.00, 850.00, 920.00, 980.00, 1050.00, 'USD', 1, CURDATE()),  -- Каинда
    (8, 250, 18, 520, 830.00, 850.00, 920.00, 980.00, 1050.00, 'USD', 1, CURDATE()),  -- Бишкек
    (8, 251, 17, 480, 830.00, 850.00, 920.00, 980.00, 1050.00, 'USD', 1, CURDATE()),  -- Рыбачье
    (8, 252, 20, 680, 840.00, 860.00, 930.00, 990.00, 1060.00, 'USD', 1, CURDATE()),  -- ОШ
    (8, 253, 19, 640, 835.00, 855.00, 925.00, 985.00, 1055.00, 'USD', 1, CURDATE()),  -- Жалал-Абад
    (8, 254, 21, 720, 845.00, 865.00, 935.00, 995.00, 1065.00, 'USD', 1, CURDATE()),  -- Кызыл-Кыя
    (8, 255, 16, 440, 825.00, 845.00, 915.00, 975.00, 1045.00, 'USD', 1, CURDATE()),  -- Шопоков
    (8, 256, 17, 500, 830.00, 850.00, 920.00, 980.00, 1050.00, 'USD', 1, CURDATE()),  -- Аламедин
    (8, 257, 17, 490, 830.00, 850.00, 920.00, 980.00, 1050.00, 'USD', 1, CURDATE());  -- Токмок


-- STEP 4: Verify data was inserted
-- Execute this to check
-- -----------------------------------------------

SELECT COUNT(*) as total_offers FROM supplier_station_offers;

SELECT
    s.name as supplier,
    st.name as station,
    sso.delivery_days,
    sso.price_diesel_b7,
    sso.currency
FROM supplier_station_offers sso
INNER JOIN suppliers s ON sso.supplier_id = s.id
INNER JOIN stations st ON sso.station_id = st.id
WHERE sso.is_active = 1
LIMIT 10;


-- STEP 5: OPTIONAL - Remove avg_delivery_days column
-- CAUTION: This will delete the column from suppliers table
-- Only execute if you're sure you want to remove it
-- -----------------------------------------------

-- ALTER TABLE suppliers DROP COLUMN avg_delivery_days;


-- STEP 6: Verify final schema
-- Execute this to confirm everything is OK
-- -----------------------------------------------

SHOW CREATE TABLE supplier_station_offers;

SELECT
    'Suppliers' as table_name,
    COUNT(*) as count
FROM suppliers
UNION ALL
SELECT
    'Supplier Offers' as table_name,
    COUNT(*) as count
FROM supplier_station_offers;


-- ===============================================
-- MIGRATION COMPLETE!
-- ===============================================
