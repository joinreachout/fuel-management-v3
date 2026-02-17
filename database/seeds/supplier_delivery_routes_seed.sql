-- Seed data for supplier_delivery_routes table
-- IMPORTANT: These are example delivery times based on avg_delivery_days
-- In production, get actual delivery times from Excel model or logistics data!

-- Supplier 8: НПЗ Кара май Ойл-Тараз (closest, avg 18 days)
-- This is a Kazakhstan supplier, closer to Kyrgyzstan stations
INSERT INTO supplier_delivery_routes (supplier_id, station_id, delivery_days, distance_km, route_notes, is_active)
VALUES
    (8, 249, 16, 450, 'Via Алматы-Бишкек highway', 1),  -- Каинда
    (8, 250, 18, 520, 'Via Алматы-Бишкек highway', 1),  -- Бишкек
    (8, 251, 17, 480, 'Via Алматы route', 1),           -- Рыбачье
    (8, 252, 20, 680, 'Via Бишкек-Ош highway', 1),      -- ОШ
    (8, 253, 19, 640, 'Via Бишкек-Жалал-Абад', 1),      -- Жалал-Абад
    (8, 254, 21, 720, 'Via Ош route', 1),               -- Кызыл-Кыя
    (8, 255, 16, 440, 'Short route via Шопоков', 1),    -- Шопоков
    (8, 256, 17, 500, 'Via Бишкек', 1),                 -- Аламедин
    (8, 257, 17, 490, 'Via Токмок route', 1);           -- Токмок

-- Supplier 6: ООО НС Ойл (avg 25 days)
INSERT INTO supplier_delivery_routes (supplier_id, station_id, delivery_days, distance_km, route_notes, is_active)
VALUES
    (6, 249, 24, 1800, 'Via Russia-Kazakhstan-Kyrgyzstan', 1),
    (6, 250, 25, 1850, 'Via Russia-Kazakhstan-Kyrgyzstan', 1),
    (6, 251, 24, 1820, 'Via Russia-Kazakhstan route', 1),
    (6, 252, 27, 2100, 'Longest route to southern station', 1),
    (6, 253, 26, 2050, 'Via southern route', 1),
    (6, 254, 28, 2150, 'Most distant station', 1),
    (6, 255, 23, 1780, 'Shorter northern route', 1),
    (6, 256, 24, 1830, 'Via Бишкек', 1),
    (6, 257, 24, 1810, 'Via Токмок', 1);

-- Supplier 9: ТОО Актобе нефтепереработка (avg 25 days)
INSERT INTO supplier_delivery_routes (supplier_id, station_id, delivery_days, distance_km, route_notes, is_active)
VALUES
    (9, 249, 23, 920, 'Via Kazakhstan direct route', 1),
    (9, 250, 25, 1050, 'Via Актобе-Бишкек', 1),
    (9, 251, 24, 980, 'Via Kazakhstan', 1),
    (9, 252, 27, 1280, 'Southern route', 1),
    (9, 253, 26, 1230, 'Via Жалал-Абад', 1),
    (9, 254, 28, 1340, 'Most distant', 1),
    (9, 255, 22, 890, 'Shortest route', 1),
    (9, 256, 24, 1020, 'Via Бишкек', 1),
    (9, 257, 23, 950, 'Via Токмок', 1);

-- Supplier 1: OPCK (avg 26 days) - Russia
INSERT INTO supplier_delivery_routes (supplier_id, station_id, delivery_days, distance_km, route_notes, is_active)
VALUES
    (1, 249, 25, 2200, 'Via Russia-Kazakhstan', 1),
    (1, 250, 26, 2280, 'Standard route', 1),
    (1, 251, 25, 2230, 'Via northern route', 1),
    (1, 252, 28, 2550, 'Longest delivery', 1),
    (1, 253, 27, 2480, 'Southern route', 1),
    (1, 254, 29, 2620, 'Most distant', 1),
    (1, 255, 24, 2150, 'Shortest route', 1),
    (1, 256, 25, 2260, 'Via Бишкек', 1),
    (1, 257, 25, 2220, 'Via Токмок', 1);

-- Add more suppliers as needed...
-- Note: Adjust delivery days based on actual logistics data

-- ВАЖНО: Для production данных:
-- 1. Импортировать реальные данные из Excel модели планирования
-- 2. Учитывать сезонность (зима/лето)
-- 3. Учитывать состояние дорог
-- 4. Учитывать таможенные задержки
