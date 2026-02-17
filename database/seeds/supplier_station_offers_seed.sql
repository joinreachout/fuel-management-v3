-- Seed data for supplier_station_offers table
-- Based on actual suppliers and stations from REV 3.0 database

SET @today = CURDATE();

-- Supplier 8: НПЗ Кара май Ойл-Тараз (closest, avg 18 days)
-- Good prices, fast delivery from Kazakhstan
INSERT INTO supplier_station_offers
(supplier_id, station_id, delivery_days, distance_km, price_diesel_b7, price_diesel_b10, price_gas_92, price_gas_95, price_gas_98, currency, is_active, valid_from)
VALUES
    (8, 249, 16, 450, 830.00, 850.00, 920.00, 980.00, 1050.00, 'USD', 1, @today),  -- Каинда
    (8, 250, 18, 520, 830.00, 850.00, 920.00, 980.00, 1050.00, 'USD', 1, @today),  -- Бишкек
    (8, 251, 17, 480, 830.00, 850.00, 920.00, 980.00, 1050.00, 'USD', 1, @today),  -- Рыбачье
    (8, 252, 20, 680, 840.00, 860.00, 930.00, 990.00, 1060.00, 'USD', 1, @today),  -- ОШ (дальше)
    (8, 253, 19, 640, 835.00, 855.00, 925.00, 985.00, 1055.00, 'USD', 1, @today),  -- Жалал-Абад
    (8, 254, 21, 720, 845.00, 865.00, 935.00, 995.00, 1065.00, 'USD', 1, @today),  -- Кызыл-Кыя
    (8, 255, 16, 440, 825.00, 845.00, 915.00, 975.00, 1045.00, 'USD', 1, @today),  -- Шопоков
    (8, 256, 17, 500, 830.00, 850.00, 920.00, 980.00, 1050.00, 'USD', 1, @today),  -- Аламедин
    (8, 257, 17, 490, 830.00, 850.00, 920.00, 980.00, 1050.00, 'USD', 1, @today);  -- Токмок

-- Supplier 1: OPCK (avg 26 days, Russia)
-- Reliable but longer delivery, slightly higher prices
INSERT INTO supplier_station_offers
(supplier_id, station_id, delivery_days, distance_km, price_diesel_b7, price_diesel_b10, price_gas_92, price_gas_95, price_gas_98, currency, min_order_tons, is_active, valid_from)
VALUES
    (1, 249, 25, 2200, 850.00, 870.00, 940.00, 1000.00, 1070.00, 'USD', 100, 1, @today),
    (1, 250, 26, 2280, 850.00, 870.00, 940.00, 1000.00, 1070.00, 'USD', 100, 1, @today),
    (1, 251, 25, 2230, 850.00, 870.00, 940.00, 1000.00, 1070.00, 'USD', 100, 1, @today),
    (1, 252, 28, 2550, 860.00, 880.00, 950.00, 1010.00, 1080.00, 'USD', 100, 1, @today),
    (1, 253, 27, 2480, 855.00, 875.00, 945.00, 1005.00, 1075.00, 'USD', 100, 1, @today),
    (1, 254, 29, 2620, 865.00, 885.00, 955.00, 1015.00, 1085.00, 'USD', 100, 1, @today),
    (1, 255, 24, 2150, 845.00, 865.00, 935.00, 995.00, 1065.00, 'USD', 100, 1, @today),
    (1, 256, 25, 2260, 850.00, 870.00, 940.00, 1000.00, 1070.00, 'USD', 100, 1, @today),
    (1, 257, 25, 2220, 850.00, 870.00, 940.00, 1000.00, 1070.00, 'USD', 100, 1, @today);

-- Supplier 6: ООО НС Ойл (avg 25 days)
-- Competitive pricing, good for northern stations
INSERT INTO supplier_station_offers
(supplier_id, station_id, delivery_days, distance_km, price_diesel_b7, price_diesel_b10, price_gas_92, price_gas_95, currency, is_active, valid_from)
VALUES
    (6, 249, 24, 1800, 845.00, 865.00, 935.00, 995.00, 'USD', 1, @today),
    (6, 250, 25, 1850, 845.00, 865.00, 935.00, 995.00, 'USD', 1, @today),
    (6, 251, 24, 1820, 845.00, 865.00, 935.00, 995.00, 'USD', 1, @today),
    (6, 252, 27, 2100, 855.00, 875.00, 945.00, 1005.00, 'USD', 1, @today),
    (6, 253, 26, 2050, 850.00, 870.00, 940.00, 1000.00, 'USD', 1, @today),
    (6, 254, 28, 2150, 860.00, 880.00, 950.00, 1010.00, 'USD', 1, @today),
    (6, 255, 23, 1780, 840.00, 860.00, 930.00, 990.00, 'USD', 1, @today),
    (6, 256, 24, 1830, 845.00, 865.00, 935.00, 995.00, 'USD', 1, @today),
    (6, 257, 24, 1810, 845.00, 865.00, 935.00, 995.00, 'USD', 1, @today);

-- Supplier 9: ТОО Актобе нефтепереработка (avg 25 days, Kazakhstan)
-- Good alternative to supplier 8
INSERT INTO supplier_station_offers
(supplier_id, station_id, delivery_days, distance_km, price_diesel_b7, price_diesel_b10, price_gas_92, price_gas_95, currency, is_active, valid_from)
VALUES
    (9, 249, 23, 920, 835.00, 855.00, 925.00, 985.00, 'USD', 1, @today),
    (9, 250, 25, 1050, 840.00, 860.00, 930.00, 990.00, 'USD', 1, @today),
    (9, 251, 24, 980, 838.00, 858.00, 928.00, 988.00, 'USD', 1, @today),
    (9, 252, 27, 1280, 850.00, 870.00, 940.00, 1000.00, 'USD', 1, @today),
    (9, 253, 26, 1230, 845.00, 865.00, 935.00, 995.00, 'USD', 1, @today),
    (9, 254, 28, 1340, 855.00, 875.00, 945.00, 1005.00, 'USD', 1, @today),
    (9, 255, 22, 890, 833.00, 853.00, 923.00, 983.00, 'USD', 1, @today),
    (9, 256, 24, 1020, 840.00, 860.00, 930.00, 990.00, 'USD', 1, @today),
    (9, 257, 23, 950, 835.00, 855.00, 925.00, 985.00, 'USD', 1, @today);

-- Add other suppliers as needed...

-- Notes:
-- 1. Prices generally increase with distance (delivery cost)
-- 2. Supplier 8 (Kara may Oil) has best prices for most stations (closest)
-- 3. Supplier 1 (OPCK) has min_order_tons=100 (large orders only)
-- 4. All prices in USD for consistency
-- 5. Adjust based on real market data!
