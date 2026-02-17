-- Seed data for supplier_prices table
-- Insert sample prices for existing suppliers and fuel types

-- Get current date for valid_from
SET @today = CURDATE();

-- Supplier 1: OPCK (id=1) - Diesel B7 and Diesel B10
INSERT INTO supplier_prices (supplier_id, fuel_type_id, price_per_ton, currency, min_quantity_tons, max_quantity_tons, is_active, valid_from, notes)
VALUES
    -- Diesel B7 (id=25)
    (1, 25, 850.00, 'USD', 50, 500, 1, @today, 'Standard diesel price for large orders'),
    -- Diesel B10 (id=33)
    (1, 33, 870.00, 'USD', 50, 500, 1, @today, 'Bio-diesel blend, slightly higher price');

-- Supplier 8: НПЗ Кара май Ойл-Тараз (id=8) - Multiple fuel types
INSERT INTO supplier_prices (supplier_id, fuel_type_id, price_per_ton, currency, min_quantity_tons, max_quantity_tons, is_active, valid_from, notes)
VALUES
    -- Diesel B7 (id=25)
    (8, 25, 830.00, 'USD', 100, 1000, 1, @today, 'Competitive diesel pricing for volume orders'),
    -- Diesel B10 (id=33)
    (8, 33, 850.00, 'USD', 100, 1000, 1, @today, 'Bio-diesel with volume discount'),
    -- A-92 (id=23)
    (8, 23, 920.00, 'USD', 50, 800, 1, @today, 'Regular gasoline'),
    -- A-95 (id=31)
    (8, 31, 980.00, 'USD', 50, 800, 1, @today, 'Premium gasoline'),
    -- A-98 (id=32)
    (8, 32, 1050.00, 'USD', 30, 500, 1, @today, 'Super premium gasoline');

-- Add more suppliers with their price lists
-- Note: Adjust supplier_id and fuel_type_id based on your actual data

-- Example: Historical price (expired)
-- INSERT INTO supplier_prices (supplier_id, fuel_type_id, price_per_ton, currency, is_active, valid_from, valid_until, notes)
-- VALUES (1, 25, 820.00, 'USD', 0, DATE_SUB(@today, INTERVAL 3 MONTHS), DATE_SUB(@today, INTERVAL 1 DAY), 'Old price - superseded');
