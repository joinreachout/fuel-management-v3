-- Apply Database Migration for Supplier Schema
-- Execute these statements in order

-- Step 1: Create supplier_station_offers table
SOURCE ./migrations/create_supplier_station_offers_table.sql;

-- Step 2: Populate with seed data
SOURCE ./seeds/supplier_station_offers_seed.sql;

-- Step 3: Remove misleading avg_delivery_days column from suppliers table
-- IMPORTANT: This field is WRONG because delivery time depends on route!
ALTER TABLE suppliers DROP COLUMN avg_delivery_days;

-- Step 4: Verify data
SELECT 'Suppliers count:' as info, COUNT(*) as count FROM suppliers;
SELECT 'Offers count:' as info, COUNT(*) as count FROM supplier_station_offers;

-- Step 5: Sample query to verify correct data
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

-- Migration complete!
