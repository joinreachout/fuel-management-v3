-- ============================================================
-- Migration 006: Seed realistic orders for testing
-- ============================================================
-- PURPOSE:
--   Populate the orders table with realistic past, in-transit,
--   and future orders so that:
--   1. Orders Calendar widget shows data
--   2. Forecast chart shows upward jumps on delivery dates
--
-- SUPPLIER: Primarily КрНПЗ (id=7), delivery 30-37 days
-- STATIONS:
--   249=Каинда, 250=Бишкек, 251=Рыбачье, 252=ОШ,
--   253=Жалал-Абад, 254=Кызыл-Кыя, 255=Шопоков,
--   256=Аламедин, 257=Токмок
-- DEPOTS (primary per station):
--   151=Каинда, 153=БиМM(Бишкек), 157=МЦС Ош,
--   165=ГНС Балыкчы(Рыбачье), 160=Жалал-Абад-1,
--   163=Кызыл-Кыя, 164=ГНС Сокулук(Шопоков),
--   174=ГНС Аламедин, 156=НБ Токмок
-- FUEL TYPES:
--   24=GAS92, 25=DIESB7, 26=GAZ
-- QUANTITY:
--   60 wagons × 60 tons = 3600 tons per order
--   GAS92:  3600 t ÷ 0.735 t/kL × 1000 = 4,897,959 L ≈ 4,900,000 L
--   DIESB7: 3600 t ÷ 0.830 t/kL × 1000 = 4,337,349 L ≈ 4,300,000 L
--   GAZ:    1800 t ÷ 0.535 t/kL × 1000 = 3,364,486 L ≈ 3,360,000 L
-- ============================================================

-- ============================================================
-- PAST ORDERS (status='delivered') — last 3 months
-- ============================================================

-- Бишкек — GAS92 — delivered ~75 days ago
INSERT INTO orders (order_number, station_id, depot_id, fuel_type_id, quantity_liters, supplier_id, order_date, delivery_date, status, price_per_ton, total_amount, notes)
VALUES ('ORD-2025-001', 250, 153, 24, 4900000, 7, DATE_SUB(CURDATE(), INTERVAL 75 DAY), DATE_SUB(CURDATE(), INTERVAL 42 DAY), 'delivered', 476.00, 1713600.00, 'Routine delivery БиМM GAS92');

-- Бишкек — DIESB7 — delivered ~70 days ago
INSERT INTO orders (order_number, station_id, depot_id, fuel_type_id, quantity_liters, supplier_id, order_date, delivery_date, status, price_per_ton, total_amount, notes)
VALUES ('ORD-2025-002', 250, 153, 25, 4300000, 7, DATE_SUB(CURDATE(), INTERVAL 70 DAY), DATE_SUB(CURDATE(), INTERVAL 37 DAY), 'delivered', 506.00, 1800580.00, 'Routine delivery БиМM DIESB7');

-- Каинда — GAS92 — delivered ~65 days ago
INSERT INTO orders (order_number, station_id, depot_id, fuel_type_id, quantity_liters, supplier_id, order_date, delivery_date, status, price_per_ton, total_amount, notes)
VALUES ('ORD-2025-003', 249, 151, 24, 4900000, 7, DATE_SUB(CURDATE(), INTERVAL 65 DAY), DATE_SUB(CURDATE(), INTERVAL 35 DAY), 'delivered', 476.00, 1713600.00, 'Routine delivery Каинда GAS92');

-- Каинда — DIESB7 — delivered ~60 days ago
INSERT INTO orders (order_number, station_id, depot_id, fuel_type_id, quantity_liters, supplier_id, order_date, delivery_date, status, price_per_ton, total_amount, notes)
VALUES ('ORD-2025-004', 249, 151, 25, 4300000, 7, DATE_SUB(CURDATE(), INTERVAL 60 DAY), DATE_SUB(CURDATE(), INTERVAL 30 DAY), 'delivered', 506.00, 1800580.00, 'Routine delivery Каинда DIESB7');

-- ОШ — GAS92 — delivered ~58 days ago
INSERT INTO orders (order_number, station_id, depot_id, fuel_type_id, quantity_liters, supplier_id, order_date, delivery_date, status, price_per_ton, total_amount, notes)
VALUES ('ORD-2025-005', 252, 157, 24, 4900000, 7, DATE_SUB(CURDATE(), INTERVAL 58 DAY), DATE_SUB(CURDATE(), INTERVAL 22 DAY), 'delivered', 476.00, 1713600.00, 'Routine delivery ОШ GAS92');

-- ОШ — DIESB7 — delivered ~55 days ago
INSERT INTO orders (order_number, station_id, depot_id, fuel_type_id, quantity_liters, supplier_id, order_date, delivery_date, status, price_per_ton, total_amount, notes)
VALUES ('ORD-2025-006', 252, 157, 25, 4300000, 7, DATE_SUB(CURDATE(), INTERVAL 55 DAY), DATE_SUB(CURDATE(), INTERVAL 19 DAY), 'delivered', 506.00, 1800580.00, 'Routine delivery ОШ DIESB7');

-- Жалал-Абад — GAS92 — delivered ~50 days ago
INSERT INTO orders (order_number, station_id, depot_id, fuel_type_id, quantity_liters, supplier_id, order_date, delivery_date, status, price_per_ton, total_amount, notes)
VALUES ('ORD-2025-007', 253, 160, 24, 4900000, 7, DATE_SUB(CURDATE(), INTERVAL 50 DAY), DATE_SUB(CURDATE(), INTERVAL 15 DAY), 'delivered', 476.00, 1713600.00, 'Routine delivery Жалал-Абад GAS92');

-- Жалал-Абад — DIESB7 — delivered ~48 days ago
INSERT INTO orders (order_number, station_id, depot_id, fuel_type_id, quantity_liters, supplier_id, order_date, delivery_date, status, price_per_ton, total_amount, notes)
VALUES ('ORD-2025-008', 253, 160, 25, 4300000, 7, DATE_SUB(CURDATE(), INTERVAL 48 DAY), DATE_SUB(CURDATE(), INTERVAL 13 DAY), 'delivered', 506.00, 1800580.00, 'Routine delivery Жалал-Абад DIESB7');

-- Жалал-Абад — GAZ — delivered ~45 days ago
INSERT INTO orders (order_number, station_id, depot_id, fuel_type_id, quantity_liters, supplier_id, order_date, delivery_date, status, price_per_ton, total_amount, notes)
VALUES ('ORD-2025-009', 253, 160, 26, 3360000, 7, DATE_SUB(CURDATE(), INTERVAL 45 DAY), DATE_SUB(CURDATE(), INTERVAL 10 DAY), 'delivered', 380.00, 680400.00, 'Routine delivery Жалал-Абад GAZ');

-- Рыбачье — GAS92 — delivered ~42 days ago
INSERT INTO orders (order_number, station_id, depot_id, fuel_type_id, quantity_liters, supplier_id, order_date, delivery_date, status, price_per_ton, total_amount, notes)
VALUES ('ORD-2025-010', 251, 165, 24, 4900000, 7, DATE_SUB(CURDATE(), INTERVAL 42 DAY), DATE_SUB(CURDATE(), INTERVAL 9 DAY), 'delivered', 476.00, 1713600.00, 'Routine delivery Рыбачье GAS92');

-- Рыбачье — GAZ — delivered ~40 days ago
INSERT INTO orders (order_number, station_id, depot_id, fuel_type_id, quantity_liters, supplier_id, order_date, delivery_date, status, price_per_ton, total_amount, notes)
VALUES ('ORD-2025-011', 251, 165, 26, 3360000, 7, DATE_SUB(CURDATE(), INTERVAL 40 DAY), DATE_SUB(CURDATE(), INTERVAL 7 DAY), 'delivered', 380.00, 680400.00, 'Routine delivery Рыбачье GAZ');

-- Кызыл-Кыя — GAS92 — delivered ~38 days ago
INSERT INTO orders (order_number, station_id, depot_id, fuel_type_id, quantity_liters, supplier_id, order_date, delivery_date, status, price_per_ton, total_amount, notes)
VALUES ('ORD-2025-012', 254, 163, 24, 4900000, 7, DATE_SUB(CURDATE(), INTERVAL 38 DAY), DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'delivered', 476.00, 1713600.00, 'Routine delivery Кызыл-Кыя GAS92');

-- Шопоков — GAS92 — delivered ~35 days ago
INSERT INTO orders (order_number, station_id, depot_id, fuel_type_id, quantity_liters, supplier_id, order_date, delivery_date, status, price_per_ton, total_amount, notes)
VALUES ('ORD-2025-013', 255, 164, 24, 4900000, 7, DATE_SUB(CURDATE(), INTERVAL 35 DAY), DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'delivered', 476.00, 1713600.00, 'Routine delivery Шопоков GAS92');

-- Аламедин — GAS92 — delivered ~33 days ago
INSERT INTO orders (order_number, station_id, depot_id, fuel_type_id, quantity_liters, supplier_id, order_date, delivery_date, status, price_per_ton, total_amount, notes)
VALUES ('ORD-2025-014', 256, 174, 24, 4900000, 7, DATE_SUB(CURDATE(), INTERVAL 33 DAY), DATE_SUB(CURDATE(), INTERVAL 0 DAY), 'delivered', 476.00, 1713600.00, 'Routine delivery Аламедин GAS92');

-- Токмок — DIESB7 — delivered ~30 days ago
INSERT INTO orders (order_number, station_id, depot_id, fuel_type_id, quantity_liters, supplier_id, order_date, delivery_date, status, price_per_ton, total_amount, notes)
VALUES ('ORD-2025-015', 257, 156, 25, 4300000, 7, DATE_SUB(CURDATE(), INTERVAL 30 DAY), CURDATE(), 'delivered', 506.00, 1800580.00, 'Routine delivery Токмок DIESB7');

-- ============================================================
-- IN-TRANSIT ORDERS — arriving in next 5-15 days
-- ============================================================

-- Бишкек — GAS92 — arriving in 5 days
INSERT INTO orders (order_number, station_id, depot_id, fuel_type_id, quantity_liters, supplier_id, order_date, delivery_date, status, price_per_ton, total_amount, notes)
VALUES ('ORD-2026-001', 250, 153, 24, 4900000, 7, DATE_SUB(CURDATE(), INTERVAL 28 DAY), DATE_ADD(CURDATE(), INTERVAL 5 DAY), 'in_transit', 476.00, 1713600.00, 'In transit БиМM GAS92');

-- Бишкек — DIESB7 — arriving in 8 days
INSERT INTO orders (order_number, station_id, depot_id, fuel_type_id, quantity_liters, supplier_id, order_date, delivery_date, status, price_per_ton, total_amount, notes)
VALUES ('ORD-2026-002', 250, 153, 25, 4300000, 7, DATE_SUB(CURDATE(), INTERVAL 25 DAY), DATE_ADD(CURDATE(), INTERVAL 8 DAY), 'in_transit', 506.00, 1800580.00, 'In transit БиМM DIESB7');

-- Каинда — GAS92 — arriving in 3 days
INSERT INTO orders (order_number, station_id, depot_id, fuel_type_id, quantity_liters, supplier_id, order_date, delivery_date, status, price_per_ton, total_amount, notes)
VALUES ('ORD-2026-003', 249, 151, 24, 4900000, 7, DATE_SUB(CURDATE(), INTERVAL 27 DAY), DATE_ADD(CURDATE(), INTERVAL 3 DAY), 'in_transit', 476.00, 1713600.00, 'In transit Каинда GAS92');

-- ОШ — GAS92 — arriving in 7 days
INSERT INTO orders (order_number, station_id, depot_id, fuel_type_id, quantity_liters, supplier_id, order_date, delivery_date, status, price_per_ton, total_amount, notes)
VALUES ('ORD-2026-004', 252, 157, 24, 4900000, 7, DATE_SUB(CURDATE(), INTERVAL 29 DAY), DATE_ADD(CURDATE(), INTERVAL 7 DAY), 'in_transit', 476.00, 1713600.00, 'In transit ОШ GAS92');

-- Жалал-Абад — GAS92 — arriving in 10 days
INSERT INTO orders (order_number, station_id, depot_id, fuel_type_id, quantity_liters, supplier_id, order_date, delivery_date, status, price_per_ton, total_amount, notes)
VALUES ('ORD-2026-005', 253, 160, 24, 4900000, 7, DATE_SUB(CURDATE(), INTERVAL 25 DAY), DATE_ADD(CURDATE(), INTERVAL 10 DAY), 'in_transit', 476.00, 1713600.00, 'In transit Жалал-Абад GAS92');

-- Жалал-Абад — GAZ — arriving in 12 days
INSERT INTO orders (order_number, station_id, depot_id, fuel_type_id, quantity_liters, supplier_id, order_date, delivery_date, status, price_per_ton, total_amount, notes)
VALUES ('ORD-2026-006', 253, 160, 26, 3360000, 7, DATE_SUB(CURDATE(), INTERVAL 23 DAY), DATE_ADD(CURDATE(), INTERVAL 12 DAY), 'in_transit', 380.00, 680400.00, 'In transit Жалал-Абад GAZ');

-- Рыбачье — DIESB7 — arriving in 6 days
INSERT INTO orders (order_number, station_id, depot_id, fuel_type_id, quantity_liters, supplier_id, order_date, delivery_date, status, price_per_ton, total_amount, notes)
VALUES ('ORD-2026-007', 251, 165, 25, 4300000, 7, DATE_SUB(CURDATE(), INTERVAL 24 DAY), DATE_ADD(CURDATE(), INTERVAL 6 DAY), 'in_transit', 506.00, 1800580.00, 'In transit Рыбачье DIESB7');

-- Кызыл-Кыя — DIESB7 — arriving in 14 days
INSERT INTO orders (order_number, station_id, depot_id, fuel_type_id, quantity_liters, supplier_id, order_date, delivery_date, status, price_per_ton, total_amount, notes)
VALUES ('ORD-2026-008', 254, 163, 25, 4300000, 7, DATE_SUB(CURDATE(), INTERVAL 23 DAY), DATE_ADD(CURDATE(), INTERVAL 14 DAY), 'in_transit', 506.00, 1800580.00, 'In transit Кызыл-Кыя DIESB7');

-- Шопоков — DIESB7 — arriving in 4 days
INSERT INTO orders (order_number, station_id, depot_id, fuel_type_id, quantity_liters, supplier_id, order_date, delivery_date, status, price_per_ton, total_amount, notes)
VALUES ('ORD-2026-009', 255, 164, 25, 4300000, 7, DATE_SUB(CURDATE(), INTERVAL 26 DAY), DATE_ADD(CURDATE(), INTERVAL 4 DAY), 'in_transit', 506.00, 1800580.00, 'In transit Шопоков DIESB7');

-- Аламедин — DIESB7 — arriving in 9 days
INSERT INTO orders (order_number, station_id, depot_id, fuel_type_id, quantity_liters, supplier_id, order_date, delivery_date, status, price_per_ton, total_amount, notes)
VALUES ('ORD-2026-010', 256, 174, 25, 4300000, 7, DATE_SUB(CURDATE(), INTERVAL 24 DAY), DATE_ADD(CURDATE(), INTERVAL 9 DAY), 'in_transit', 506.00, 1800580.00, 'In transit Аламедин DIESB7');

-- Токмок — GAS92 — arriving in 11 days
INSERT INTO orders (order_number, station_id, depot_id, fuel_type_id, quantity_liters, supplier_id, order_date, delivery_date, status, price_per_ton, total_amount, notes)
VALUES ('ORD-2026-011', 257, 156, 24, 4900000, 7, DATE_SUB(CURDATE(), INTERVAL 22 DAY), DATE_ADD(CURDATE(), INTERVAL 11 DAY), 'in_transit', 476.00, 1713600.00, 'In transit Токмок GAS92');

-- ============================================================
-- CONFIRMED ORDERS — arriving in 25-37 days (future)
-- ============================================================

-- Бишкек — GAS92 — arriving in 33 days
INSERT INTO orders (order_number, station_id, depot_id, fuel_type_id, quantity_liters, supplier_id, order_date, delivery_date, status, price_per_ton, total_amount, notes)
VALUES ('ORD-2026-021', 250, 153, 24, 4900000, 7, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 33 DAY), 'confirmed', 476.00, 1713600.00, 'Planned delivery БиМM GAS92');

-- Бишкек — DIESB7 — arriving in 33 days
INSERT INTO orders (order_number, station_id, depot_id, fuel_type_id, quantity_liters, supplier_id, order_date, delivery_date, status, price_per_ton, total_amount, notes)
VALUES ('ORD-2026-022', 250, 153, 25, 4300000, 7, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 33 DAY), 'confirmed', 506.00, 1800580.00, 'Planned delivery БиМM DIESB7');

-- Каинда — GAS92 — arriving in 30 days
INSERT INTO orders (order_number, station_id, depot_id, fuel_type_id, quantity_liters, supplier_id, order_date, delivery_date, status, price_per_ton, total_amount, notes)
VALUES ('ORD-2026-023', 249, 151, 24, 4900000, 7, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'confirmed', 476.00, 1713600.00, 'Planned delivery Каинда GAS92');

-- Каинда — DIESB7 — arriving in 30 days
INSERT INTO orders (order_number, station_id, depot_id, fuel_type_id, quantity_liters, supplier_id, order_date, delivery_date, status, price_per_ton, total_amount, notes)
VALUES ('ORD-2026-024', 249, 151, 25, 4300000, 7, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'confirmed', 506.00, 1800580.00, 'Planned delivery Каинда DIESB7');

-- ОШ — GAS92 — arriving in 36 days
INSERT INTO orders (order_number, station_id, depot_id, fuel_type_id, quantity_liters, supplier_id, order_date, delivery_date, status, price_per_ton, total_amount, notes)
VALUES ('ORD-2026-025', 252, 157, 24, 4900000, 7, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 36 DAY), 'confirmed', 476.00, 1713600.00, 'Planned delivery ОШ GAS92');

-- ОШ — DIESB7 — arriving in 36 days
INSERT INTO orders (order_number, station_id, depot_id, fuel_type_id, quantity_liters, supplier_id, order_date, delivery_date, status, price_per_ton, total_amount, notes)
VALUES ('ORD-2026-026', 252, 157, 25, 4300000, 7, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 36 DAY), 'confirmed', 506.00, 1800580.00, 'Planned delivery ОШ DIESB7');

-- Жалал-Абад — GAS92 — arriving in 35 days
INSERT INTO orders (order_number, station_id, depot_id, fuel_type_id, quantity_liters, supplier_id, order_date, delivery_date, status, price_per_ton, total_amount, notes)
VALUES ('ORD-2026-027', 253, 160, 24, 4900000, 7, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 35 DAY), 'confirmed', 476.00, 1713600.00, 'Planned delivery Жалал-Абад GAS92');

-- Жалал-Абад — DIESB7 — arriving in 35 days
INSERT INTO orders (order_number, station_id, depot_id, fuel_type_id, quantity_liters, supplier_id, order_date, delivery_date, status, price_per_ton, total_amount, notes)
VALUES ('ORD-2026-028', 253, 160, 25, 4300000, 7, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 35 DAY), 'confirmed', 506.00, 1800580.00, 'Planned delivery Жалал-Абад DIESB7');

-- Жалал-Абад — GAZ — arriving in 35 days
INSERT INTO orders (order_number, station_id, depot_id, fuel_type_id, quantity_liters, supplier_id, order_date, delivery_date, status, price_per_ton, total_amount, notes)
VALUES ('ORD-2026-029', 253, 160, 26, 3360000, 7, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 35 DAY), 'confirmed', 380.00, 680400.00, 'Planned delivery Жалал-Абад GAZ');

-- Рыбачье — GAS92 — arriving in 28 days
INSERT INTO orders (order_number, station_id, depot_id, fuel_type_id, quantity_liters, supplier_id, order_date, delivery_date, status, price_per_ton, total_amount, notes)
VALUES ('ORD-2026-030', 251, 165, 24, 4900000, 7, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 28 DAY), 'confirmed', 476.00, 1713600.00, 'Planned delivery Рыбачье GAS92');

-- Рыбачье — DIESB7 — arriving in 28 days
INSERT INTO orders (order_number, station_id, depot_id, fuel_type_id, quantity_liters, supplier_id, order_date, delivery_date, status, price_per_ton, total_amount, notes)
VALUES ('ORD-2026-031', 251, 165, 25, 4300000, 7, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 28 DAY), 'confirmed', 506.00, 1800580.00, 'Planned delivery Рыбачье DIESB7');

-- Рыбачье — GAZ — arriving in 28 days
INSERT INTO orders (order_number, station_id, depot_id, fuel_type_id, quantity_liters, supplier_id, order_date, delivery_date, status, price_per_ton, total_amount, notes)
VALUES ('ORD-2026-032', 251, 165, 26, 3360000, 7, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 28 DAY), 'confirmed', 380.00, 680400.00, 'Planned delivery Рыбачье GAZ');

-- Кызыл-Кыя — GAS92 — arriving in 37 days
INSERT INTO orders (order_number, station_id, depot_id, fuel_type_id, quantity_liters, supplier_id, order_date, delivery_date, status, price_per_ton, total_amount, notes)
VALUES ('ORD-2026-033', 254, 163, 24, 4900000, 7, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 37 DAY), 'confirmed', 476.00, 1713600.00, 'Planned delivery Кызыл-Кыя GAS92');

-- Шопоков — GAS92 — arriving in 30 days
INSERT INTO orders (order_number, station_id, depot_id, fuel_type_id, quantity_liters, supplier_id, order_date, delivery_date, status, price_per_ton, total_amount, notes)
VALUES ('ORD-2026-034', 255, 164, 24, 4900000, 7, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'confirmed', 476.00, 1713600.00, 'Planned delivery Шопоков GAS92');

-- Аламедин — GAS92 — arriving in 33 days
INSERT INTO orders (order_number, station_id, depot_id, fuel_type_id, quantity_liters, supplier_id, order_date, delivery_date, status, price_per_ton, total_amount, notes)
VALUES ('ORD-2026-035', 256, 174, 24, 4900000, 7, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 33 DAY), 'confirmed', 476.00, 1713600.00, 'Planned delivery Аламедин GAS92');

-- Аламедин — DIESB7 — arriving in 33 days
INSERT INTO orders (order_number, station_id, depot_id, fuel_type_id, quantity_liters, supplier_id, order_date, delivery_date, status, price_per_ton, total_amount, notes)
VALUES ('ORD-2026-036', 256, 174, 25, 4300000, 7, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 33 DAY), 'confirmed', 506.00, 1800580.00, 'Planned delivery Аламедин DIESB7');

-- Токмок — GAS92 — arriving in 33 days
INSERT INTO orders (order_number, station_id, depot_id, fuel_type_id, quantity_liters, supplier_id, order_date, delivery_date, status, price_per_ton, total_amount, notes)
VALUES ('ORD-2026-037', 257, 156, 24, 4900000, 7, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 33 DAY), 'confirmed', 476.00, 1713600.00, 'Planned delivery Токмок GAS92');

-- Токмок — DIESB7 — arriving in 33 days
INSERT INTO orders (order_number, station_id, depot_id, fuel_type_id, quantity_liters, supplier_id, order_date, delivery_date, status, price_per_ton, total_amount, notes)
VALUES ('ORD-2026-038', 257, 156, 25, 4300000, 7, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 33 DAY), 'confirmed', 506.00, 1800580.00, 'Planned delivery Токмок DIESB7');

-- ============================================================
-- VERIFY
-- ============================================================
SELECT
    status,
    COUNT(*) AS cnt,
    MIN(delivery_date) AS earliest_delivery,
    MAX(delivery_date) AS latest_delivery
FROM orders
GROUP BY status
ORDER BY FIELD(status, 'delivered', 'in_transit', 'confirmed');

SELECT COUNT(*) AS total_orders FROM orders;
