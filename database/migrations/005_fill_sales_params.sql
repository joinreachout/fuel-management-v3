-- ============================================================
-- Migration 005: Fill sales_params with modelled consumption
-- ============================================================
-- SOURCE: Real station-level totals from Excel model_v02.xls
--         Split into fuel types by agreed proportions.
--
-- SPLIT RULES:
--   Автобензины total → GAS80=25%, GAS92=45%, GAS95=15%, GAS95EUR=10%, GAS98=5%
--   MTBE             → 2% of Автобензины total (trace additive)
--   ДТ total         → DIESB7=70%, DIESB10=30%
--   GAZ              → as-is from station total (LPG infrastructure)
--   JET              → only Бишкек + ОШ (aviation hubs)
--   0 or missing     → fuel type does not exist at that station
--
-- STATION TOTALS (litres/day from Excel):
--   Каинда           : Бензин=215000, ДТ=170000, Газ=0
--   Бишкек (все депо): Бензин=166600, ДТ=65000,  Газ=0
--   Токмок           : Бензин=75000,  ДТ=60000,   Газ=0
--   Рыбачье/Балыкчы  : Бензин=110000, ДТ=90000,   Газ=40000
--   ОШ               : Бензин=190000, ДТ=105000,  Газ=0
--   Жалал-Абад       : Бензин=220000, ДТ=100000,  Газ=55000
--   Кызыл-Кыя        : Бензин=110000, ДТ=35000,   Газ=35000
--   Шопоков          : Бензин=200000, ДТ=60000,   Газ=0
--   Аламедин         : Бензин=150000, ДТ=55000,   Газ=0  (estimated)
--
-- SAFETY: Only updates WHERE liters_per_day = 0
--         Real data already in DB is preserved.
-- ============================================================

-- ============================================================
-- КАИНДА   Бензин=215000  ДТ=170000  Газ=0
-- ============================================================
UPDATE sales_params sp
JOIN depots d ON sp.depot_id = d.id
JOIN fuel_types f ON sp.fuel_type_id = f.id
SET sp.liters_per_day = CASE f.code
    WHEN 'GAS80'    THEN ROUND(215000 * 0.25)   --  53750
    WHEN 'GAS92'    THEN ROUND(215000 * 0.45)   --  96750
    WHEN 'GAS95'    THEN ROUND(215000 * 0.15)   --  32250
    WHEN 'GAS95EUR' THEN ROUND(215000 * 0.10)   --  21500
    WHEN 'GAS98'    THEN ROUND(215000 * 0.05)   --  10750
    WHEN 'MTBE'     THEN ROUND(215000 * 0.02)   --   4300
    WHEN 'DIESB7'   THEN ROUND(170000 * 0.70)   -- 119000
    WHEN 'DIESB10'  THEN ROUND(170000 * 0.30)   --  51000
    WHEN 'GAZ'      THEN 0
    WHEN 'JET'      THEN 0
    ELSE 0
END
WHERE sp.liters_per_day = 0
  AND sp.effective_to IS NULL
  AND d.name LIKE '%Каинда%';

-- ============================================================
-- БИШКЕК   Бензин=166600  ДТ=65000  Газ=0
-- (БиМM и ХОП уже имеют реальные данные — WHERE = 0 защищает)
-- ============================================================
UPDATE sales_params sp
JOIN depots d ON sp.depot_id = d.id
JOIN fuel_types f ON sp.fuel_type_id = f.id
SET sp.liters_per_day = CASE f.code
    WHEN 'GAS80'    THEN ROUND(166600 * 0.25)   --  41650
    WHEN 'GAS92'    THEN ROUND(166600 * 0.45)   --  74970
    WHEN 'GAS95'    THEN ROUND(166600 * 0.15)   --  24990
    WHEN 'GAS95EUR' THEN ROUND(166600 * 0.10)   --  16660
    WHEN 'GAS98'    THEN ROUND(166600 * 0.05)   --   8330
    WHEN 'MTBE'     THEN ROUND(166600 * 0.02)   --   3332
    WHEN 'DIESB7'   THEN ROUND(65000  * 0.70)   --  45500
    WHEN 'DIESB10'  THEN ROUND(65000  * 0.30)   --  19500
    WHEN 'GAZ'      THEN 0
    WHEN 'JET'      THEN 1500   -- Manas airport
    ELSE 0
END
WHERE sp.liters_per_day = 0
  AND sp.effective_to IS NULL
  AND d.name LIKE '%Бишкек%';

-- ============================================================
-- ТОКМОК   Бензин=75000  ДТ=60000  Газ=0
-- ============================================================
UPDATE sales_params sp
JOIN depots d ON sp.depot_id = d.id
JOIN fuel_types f ON sp.fuel_type_id = f.id
SET sp.liters_per_day = CASE f.code
    WHEN 'GAS80'    THEN ROUND(75000 * 0.25)    --  18750
    WHEN 'GAS92'    THEN ROUND(75000 * 0.45)    --  33750
    WHEN 'GAS95'    THEN ROUND(75000 * 0.15)    --  11250
    WHEN 'GAS95EUR' THEN ROUND(75000 * 0.10)    --   7500
    WHEN 'GAS98'    THEN ROUND(75000 * 0.05)    --   3750
    WHEN 'MTBE'     THEN ROUND(75000 * 0.02)    --   1500
    WHEN 'DIESB7'   THEN ROUND(60000 * 0.70)    --  42000
    WHEN 'DIESB10'  THEN ROUND(60000 * 0.30)    --  18000
    WHEN 'GAZ'      THEN 0
    WHEN 'JET'      THEN 0
    ELSE 0
END
WHERE sp.liters_per_day = 0
  AND sp.effective_to IS NULL
  AND d.name LIKE '%Токмок%';

-- ============================================================
-- РЫБАЧЬЕ / БАЛЫКЧЫ   Бензин=110000  ДТ=90000  Газ=40000
-- ============================================================
UPDATE sales_params sp
JOIN depots d ON sp.depot_id = d.id
JOIN fuel_types f ON sp.fuel_type_id = f.id
SET sp.liters_per_day = CASE f.code
    WHEN 'GAS80'    THEN ROUND(110000 * 0.25)   --  27500
    WHEN 'GAS92'    THEN ROUND(110000 * 0.45)   --  49500
    WHEN 'GAS95'    THEN ROUND(110000 * 0.15)   --  16500
    WHEN 'GAS95EUR' THEN ROUND(110000 * 0.10)   --  11000
    WHEN 'GAS98'    THEN ROUND(110000 * 0.05)   --   5500
    WHEN 'MTBE'     THEN ROUND(110000 * 0.02)   --   2200
    WHEN 'DIESB7'   THEN ROUND(90000  * 0.70)   --  63000
    WHEN 'DIESB10'  THEN ROUND(90000  * 0.30)   --  27000
    WHEN 'GAZ'      THEN 40000
    WHEN 'JET'      THEN 0
    ELSE 0
END
WHERE sp.liters_per_day = 0
  AND sp.effective_to IS NULL
  AND (d.name LIKE '%Рыбачье%' OR d.name LIKE '%Балыкч%');

-- ============================================================
-- ОШ   Бензин=190000  ДТ=105000  Газ=0
-- ============================================================
UPDATE sales_params sp
JOIN depots d ON sp.depot_id = d.id
JOIN fuel_types f ON sp.fuel_type_id = f.id
SET sp.liters_per_day = CASE f.code
    WHEN 'GAS80'    THEN ROUND(190000 * 0.25)   --  47500
    WHEN 'GAS92'    THEN ROUND(190000 * 0.45)   --  85500
    WHEN 'GAS95'    THEN ROUND(190000 * 0.15)   --  28500
    WHEN 'GAS95EUR' THEN ROUND(190000 * 0.10)   --  19000
    WHEN 'GAS98'    THEN ROUND(190000 * 0.05)   --   9500
    WHEN 'MTBE'     THEN ROUND(190000 * 0.02)   --   3800
    WHEN 'DIESB7'   THEN ROUND(105000 * 0.70)   --  73500
    WHEN 'DIESB10'  THEN ROUND(105000 * 0.30)   --  31500
    WHEN 'GAZ'      THEN 0
    WHEN 'JET'      THEN 800    -- Osh airport
    ELSE 0
END
WHERE sp.liters_per_day = 0
  AND sp.effective_to IS NULL
  AND d.name LIKE '%ОШ%';

-- ============================================================
-- ЖАЛАЛ-АБА Д   Бензин=220000  ДТ=100000  Газ=55000
-- (все депо: -1, -2, ГНС — WHERE = 0 защищает уже заполненные)
-- ============================================================
UPDATE sales_params sp
JOIN depots d ON sp.depot_id = d.id
JOIN fuel_types f ON sp.fuel_type_id = f.id
SET sp.liters_per_day = CASE f.code
    WHEN 'GAS80'    THEN ROUND(220000 * 0.25)   --  55000
    WHEN 'GAS92'    THEN ROUND(220000 * 0.45)   --  99000
    WHEN 'GAS95'    THEN ROUND(220000 * 0.15)   --  33000
    WHEN 'GAS95EUR' THEN ROUND(220000 * 0.10)   --  22000
    WHEN 'GAS98'    THEN ROUND(220000 * 0.05)   --  11000
    WHEN 'MTBE'     THEN ROUND(220000 * 0.02)   --   4400
    WHEN 'DIESB7'   THEN ROUND(100000 * 0.70)   --  70000
    WHEN 'DIESB10'  THEN ROUND(100000 * 0.30)   --  30000
    WHEN 'GAZ'      THEN 55000
    WHEN 'JET'      THEN 0
    ELSE 0
END
WHERE sp.liters_per_day = 0
  AND sp.effective_to IS NULL
  AND d.name LIKE '%Жалал-Абад%';

-- ============================================================
-- КЫЗЫЛ-КЫЯ   Бензин=110000  ДТ=35000  Газ=35000
-- ============================================================
UPDATE sales_params sp
JOIN depots d ON sp.depot_id = d.id
JOIN fuel_types f ON sp.fuel_type_id = f.id
SET sp.liters_per_day = CASE f.code
    WHEN 'GAS80'    THEN ROUND(110000 * 0.25)   --  27500
    WHEN 'GAS92'    THEN ROUND(110000 * 0.45)   --  49500
    WHEN 'GAS95'    THEN ROUND(110000 * 0.15)   --  16500
    WHEN 'GAS95EUR' THEN ROUND(110000 * 0.10)   --  11000
    WHEN 'GAS98'    THEN ROUND(110000 * 0.05)   --   5500
    WHEN 'MTBE'     THEN ROUND(110000 * 0.02)   --   2200
    WHEN 'DIESB7'   THEN ROUND(35000  * 0.70)   --  24500
    WHEN 'DIESB10'  THEN ROUND(35000  * 0.30)   --  10500
    WHEN 'GAZ'      THEN 35000
    WHEN 'JET'      THEN 0
    ELSE 0
END
WHERE sp.liters_per_day = 0
  AND sp.effective_to IS NULL
  AND d.name LIKE '%Кызыл-Кыя%';

-- ============================================================
-- ШОПОКОВ   Бензин=200000  ДТ=60000  Газ=0
-- ============================================================
UPDATE sales_params sp
JOIN depots d ON sp.depot_id = d.id
JOIN fuel_types f ON sp.fuel_type_id = f.id
SET sp.liters_per_day = CASE f.code
    WHEN 'GAS80'    THEN ROUND(200000 * 0.25)   --  50000
    WHEN 'GAS92'    THEN ROUND(200000 * 0.45)   --  90000
    WHEN 'GAS95'    THEN ROUND(200000 * 0.15)   --  30000
    WHEN 'GAS95EUR' THEN ROUND(200000 * 0.10)   --  20000
    WHEN 'GAS98'    THEN ROUND(200000 * 0.05)   --  10000
    WHEN 'MTBE'     THEN ROUND(200000 * 0.02)   --   4000
    WHEN 'DIESB7'   THEN ROUND(60000  * 0.70)   --  42000
    WHEN 'DIESB10'  THEN ROUND(60000  * 0.30)   --  18000
    WHEN 'GAZ'      THEN 0
    WHEN 'JET'      THEN 0
    ELSE 0
END
WHERE sp.liters_per_day = 0
  AND sp.effective_to IS NULL
  AND d.name LIKE '%Шопоков%';

-- ============================================================
-- АЛАМЕДИН   Бензин=150000  ДТ=55000  Газ=0  (оценка)
-- ============================================================
UPDATE sales_params sp
JOIN depots d ON sp.depot_id = d.id
JOIN fuel_types f ON sp.fuel_type_id = f.id
SET sp.liters_per_day = CASE f.code
    WHEN 'GAS80'    THEN ROUND(150000 * 0.25)   --  37500
    WHEN 'GAS92'    THEN ROUND(150000 * 0.45)   --  67500
    WHEN 'GAS95'    THEN ROUND(150000 * 0.15)   --  22500
    WHEN 'GAS95EUR' THEN ROUND(150000 * 0.10)   --  15000
    WHEN 'GAS98'    THEN ROUND(150000 * 0.05)   --   7500
    WHEN 'MTBE'     THEN ROUND(150000 * 0.02)   --   3000
    WHEN 'DIESB7'   THEN ROUND(55000  * 0.70)   --  38500
    WHEN 'DIESB10'  THEN ROUND(55000  * 0.30)   --  16500
    WHEN 'GAZ'      THEN 0
    WHEN 'JET'      THEN 0
    ELSE 0
END
WHERE sp.liters_per_day = 0
  AND sp.effective_to IS NULL
  AND d.name LIKE '%Аламедин%';

-- ============================================================
-- CATCH-ALL: любые оставшиеся нули (неизвестные депо)
-- Используем средние значения ~150000 бензин / 60000 ДТ
-- ============================================================
UPDATE sales_params sp
JOIN depots d ON sp.depot_id = d.id
JOIN fuel_types f ON sp.fuel_type_id = f.id
SET sp.liters_per_day = CASE f.code
    WHEN 'GAS80'    THEN ROUND(150000 * 0.25)
    WHEN 'GAS92'    THEN ROUND(150000 * 0.45)
    WHEN 'GAS95'    THEN ROUND(150000 * 0.15)
    WHEN 'GAS95EUR' THEN ROUND(150000 * 0.10)
    WHEN 'GAS98'    THEN ROUND(150000 * 0.05)
    WHEN 'MTBE'     THEN ROUND(150000 * 0.02)
    WHEN 'DIESB7'   THEN ROUND(60000  * 0.70)
    WHEN 'DIESB10'  THEN ROUND(60000  * 0.30)
    WHEN 'GAZ'      THEN 0
    WHEN 'JET'      THEN 0
    ELSE 0
END
WHERE sp.liters_per_day = 0
  AND sp.effective_to IS NULL;

-- ============================================================
-- ПРОВЕРКА после запуска
-- ============================================================
SELECT
    s.name                                          AS station,
    d.name                                          AS depot,
    f.code                                          AS fuel,
    sp.liters_per_day                               AS l_per_day,
    ROUND(sp.liters_per_day * f.density / 1000, 1) AS t_per_day
FROM sales_params sp
JOIN depots d     ON sp.depot_id     = d.id
JOIN stations s   ON d.station_id    = s.id
JOIN fuel_types f ON sp.fuel_type_id = f.id
WHERE sp.effective_to IS NULL
  AND sp.liters_per_day > 0
ORDER BY s.name, d.name, f.code;

-- Строк с нулём (должно быть 0):
SELECT COUNT(*) AS still_zero
FROM sales_params
WHERE effective_to IS NULL AND liters_per_day = 0;
