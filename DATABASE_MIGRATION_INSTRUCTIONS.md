# Database Migration Instructions

## Цель
Создать таблицу `supplier_station_offers` для хранения точных данных о доставке и ценах от каждого поставщика до каждой станции.

---

## Вариант 1: Через phpMyAdmin (Рекомендуется) ✅

### Шаг 1: Открыть phpMyAdmin
1. Перейти на https://my.zone.ee
2. Войти в аккаунт
3. Выбрать базу данных `d105380_fuelv3`

### Шаг 2: Создать таблицу
1. Перейти на вкладку **SQL**
2. Открыть файл `database/MIGRATION_STEP_BY_STEP.sql`
3. Скопировать **STEP 1** (CREATE TABLE statement)
4. Вставить в SQL окно phpMyAdmin
5. Нажать **Go / Выполнить**
6. Должно появиться: "1 row affected" или "Table created"

### Шаг 3: Проверить создание таблицы
Выполнить в SQL:
```sql
SHOW TABLES LIKE 'supplier_station_offers';
DESCRIBE supplier_station_offers;
```

Должна появиться таблица с 20+ колонками.

### Шаг 4: Добавить тестовые данные
1. Скопировать **STEP 3** из `MIGRATION_STEP_BY_STEP.sql` (INSERT INTO...)
2. Вставить в SQL окно
3. Выполнить
4. Должно показать: "9 rows inserted"

### Шаг 5: Проверить данные
Выполнить:
```sql
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
```

Должны увидеть данные вроде:
```
НПЗ Кара май Ойл-Тараз | Станция Каинда  | 16 | 830.00 | USD
НПЗ Кара май Ойл-Тараз | Станция Бишкек  | 18 | 830.00 | USD
...
```

### Шаг 6 (Опционально): Удалить avg_delivery_days
⚠️ **ВНИМАНИЕ**: Это удалит колонку из таблицы suppliers!

Выполнить только если уверены:
```sql
ALTER TABLE suppliers DROP COLUMN avg_delivery_days;
```

---

## Вариант 2: Через MySQL CLI

### Если есть SSH доступ к серверу:

```bash
# На локальной машине
cd "/Volumes/WORK/Projects/Handshake/AI/GAS STATIONS PROJECT/REV 3.0/.claude/worktrees/romantic-heyrovsky"

# Запустить миграцию
./apply_migration.sh

# Введите пароль MySQL когда попросит
```

Скрипт автоматически:
1. Создаст таблицу
2. Вставит seed данные
3. Проверит результат

---

## После применения миграции

### Проверить что всё работает:

#### 1. Проверить через API
```bash
curl "https://fuel.kittykat.tech/rev3/backend/public/api/suppliers/8"
```

#### 2. Проверить offers для supplier 8:
```sql
SELECT
    st.name as station,
    sso.delivery_days,
    sso.price_diesel_b7,
    sso.price_gas_95
FROM supplier_station_offers sso
INNER JOIN stations st ON sso.station_id = st.id
WHERE sso.supplier_id = 8
ORDER BY sso.delivery_days;
```

Должны увидеть 9 станций с разным временем доставки.

---

## Следующий шаг: Обновить код

После успешной миграции БД, нужно обновить:

### 1. ProcurementAdvisorService.php

Изменить метод `getBestSupplier()`:

```php
private static function getBestSupplier(
    int $fuelTypeId,
    int $stationId,     // Добавить параметр!
    string $urgency
): ?array {
    $sql = "
        SELECT
            s.id,
            s.name,
            s.departure_station,
            s.priority,
            s.auto_score,
            sso.delivery_days,
            sso.price_diesel_b7,
            sso.price_diesel_b10,
            sso.price_gas_92,
            sso.price_gas_95,
            sso.currency
        FROM suppliers s
        INNER JOIN supplier_station_offers sso
            ON s.id = sso.supplier_id
        WHERE sso.station_id = ?
          AND sso.is_active = 1
          AND s.is_active = 1
        ORDER BY
            s.priority ASC,
            sso.delivery_days ASC
        LIMIT 1
    ";

    $result = Database::fetchAll($sql, [$stationId]);

    if (empty($result)) {
        return null;
    }

    $supplier = $result[0];

    // Определить правильную цену на основе fuel_type_id
    $pricePerTon = null;
    switch ($fuelTypeId) {
        case 25: // Diesel B7
            $pricePerTon = $supplier['price_diesel_b7'];
            break;
        case 33: // Diesel B10
            $pricePerTon = $supplier['price_diesel_b10'];
            break;
        case 23: // A-92
            $pricePerTon = $supplier['price_gas_92'];
            break;
        case 31: // A-95
            $pricePerTon = $supplier['price_gas_95'];
            break;
        // ... добавить остальные
    }

    return [
        'id' => $supplier['id'],
        'name' => $supplier['name'],
        'departure_station' => $supplier['departure_station'],
        'priority' => $supplier['priority'],
        'score' => (float)$supplier['auto_score'],
        'avg_delivery_days' => (int)$supplier['delivery_days'], // Теперь точное!
        'price_per_ton' => $pricePerTon,
        'currency' => $supplier['currency']
    ];
}
```

### 2. Обновить вызов функции

В `getUpcomingShortages()`:

```php
// БЫЛО:
// $bestSupplier = self::getBestSupplier($row['fuel_type_id'], $urgency);

// СТАЛО:
$bestSupplier = self::getBestSupplier(
    $row['fuel_type_id'],
    $row['station_id'],    // Добавить!
    $urgency
);
```

---

## Rollback Plan (если что-то пошло не так)

### Откатить миграцию:
```sql
-- Удалить таблицу
DROP TABLE IF EXISTS supplier_station_offers;

-- Проверить что таблица удалена
SHOW TABLES LIKE 'supplier_station_offers';
```

---

## FAQ

**Q: Нужно ли удалять avg_delivery_days?**
A: Не обязательно сразу. Можно оставить для совместимости, пока не обновите весь код.

**Q: Как добавить данные для других поставщиков?**
A: Скопируйте INSERT блок из seed файла и измените supplier_id, цены и delivery_days.

**Q: Что если нет данных о ценах?**
A: Можно оставить NULL в колонках price_*, они опциональные.

**Q: Как обновить цену?**
A:
```sql
UPDATE supplier_station_offers
SET price_diesel_b7 = 840.00,
    updated_at = NOW()
WHERE supplier_id = 8
  AND station_id = 249;
```

---

## Checklist

После миграции проверить:

- [ ] Таблица `supplier_station_offers` создана
- [ ] Есть хотя бы 9 записей (для supplier 8)
- [ ] Foreign keys работают (supplier_id → suppliers.id)
- [ ] Indexes созданы
- [ ] Данные можно прочитать через JOIN
- [ ] API работает (если код обновлён)

---

**Готово к применению!** ✅

Выберите Вариант 1 (phpMyAdmin) если не уверены.
