

# Database Schema Design Summary

## 🎯 Цель
Создать правильную структуру БД для хранения данных о поставщиках без дублирования и с точными данными о доставке.

## ❌ Проблема с текущей схемой

**Таблица `suppliers` содержит `avg_delivery_days`** - это **НЕПРАВИЛЬНО**!

Почему:
- Время доставки зависит от маршрута: поставщик → станция
- Орск → Каинда ≠ Орск → Бишкек
- Средняя доставка не имеет смысла для конкретного заказа
- Приводит к неправильным расчётам в Procurement Advisor

## ✅ Правильная схема: 2 таблицы

### Таблица 1: `suppliers` (базовая информация)
```
id, name, departure_station, priority, auto_score, is_active
```
- Без avg_delivery_days!
- Хранит только базовую инфо о поставщике

### Таблица 2: `supplier_station_offers` (предложения по станциям)
```
id, supplier_id, station_id,
delivery_days,               ← ТОЧНОЕ время доставки для ЭТОГО маршрута
distance_km,
price_diesel_b7,            ← Цены могут быть разные для разных станций
price_diesel_b10,
price_gas_92, price_gas_95, price_gas_98,
price_jet, price_lpg, price_mtbe,
currency, min_order_tons, max_order_tons,
is_active, valid_from, valid_until
```

## 📊 Преимущества этой схемы

1. **Точность**: Реальное время доставки для каждого маршрута
2. **Гибкость**: Разные цены для разных станций
3. **История**: Отслеживание изменений цен
4. **Масштабируемость**: Легко добавить новую станцию или поставщика
5. **Простота**: Только 1 JOIN вместо 3
6. **Без дублирования**: Базовая инфо в одном месте

## 📁 Файлы для миграции

Созданы следующие файлы:

### Migrations
1. `database/migrations/create_supplier_station_offers_table.sql`
   - Создаёт таблицу supplier_station_offers
   - Foreign keys, indexes, constraints

### Seeds
2. `database/seeds/supplier_station_offers_seed.sql`
   - Примерные данные на основе реальных поставщиков
   - 4 поставщика × 9 станций = 36 записей

### Documentation
3. `database/SUPPLIER_PRICES_SCHEMA.md`
   - Первоначальная документация (3 таблицы)

4. `database/SCHEMA_COMPARISON.md`
   - Сравнение 3 вариантов схемы
   - Почему выбрали 2 таблицы

5. `database/FINAL_SCHEMA_PLAN.md`
   - Финальный план миграции
   - Примеры запросов
   - Изменения в ProcurementAdvisorService

6. `database/APPLY_MIGRATION.sql`
   - Скрипт для применения миграции
   - Удаление avg_delivery_days

## 🚀 Применение миграции

### Вариант 1: Через MySQL клиент
```bash
mysql -u username -p d105380_fuelv3 < database/APPLY_MIGRATION.sql
```

### Вариант 2: Через phpMyAdmin
1. Открыть phpMyAdmin
2. Выбрать базу d105380_fuelv3
3. SQL → Вставить содержимое create_supplier_station_offers_table.sql
4. Выполнить
5. SQL → Вставить содержимое supplier_station_offers_seed.sql
6. Выполнить
7. SQL → `ALTER TABLE suppliers DROP COLUMN avg_delivery_days;`
8. Выполнить

### Вариант 3: По шагам вручную
```sql
-- 1. Создать таблицу
CREATE TABLE IF NOT EXISTS supplier_station_offers (...);

-- 2. Добавить данные
INSERT INTO supplier_station_offers (...) VALUES (...);

-- 3. Удалить неправильное поле
ALTER TABLE suppliers DROP COLUMN avg_delivery_days;
```

## 🔧 Изменения в коде

### ProcurementAdvisorService.php

#### Было (НЕПРАВИЛЬНО):
```php
$deliveryDays = $supplier['avg_delivery_days']; // Средняя - неточно!
```

#### Стало (ПРАВИЛЬНО):
```php
private static function getBestSupplier(
    int $fuelTypeId,
    int $stationId,     // Добавили параметр!
    string $urgency
): ?array {
    $sql = "
        SELECT
            s.id, s.name, s.departure_station,
            s.priority, s.auto_score,
            sso.delivery_days,           -- Точное время для ЭТОГО маршрута
            sso.price_diesel_b7,         -- Цена для ЭТОЙ станции
            sso.price_diesel_b10,
            sso.price_gas_92,
            sso.price_gas_95,
            sso.currency
        FROM suppliers s
        INNER JOIN supplier_station_offers sso
            ON s.id = sso.supplier_id
        WHERE sso.station_id = ?         -- Фильтр по станции!
          AND sso.is_active = 1
          AND s.is_active = 1
        ORDER BY
            s.priority ASC,
            sso.delivery_days ASC        -- Сортировка по реальному времени
        LIMIT 1
    ";

    $result = Database::fetchAll($sql, [$stationId]);
    // ...
}
```

### Вызов функции
```php
// В getUpcomingShortages():
$bestSupplier = self::getBestSupplier(
    $row['fuel_type_id'],
    $row['station_id'],    // Передаём station_id!
    $urgency
);
```

## 📝 Пример данных

### Supplier 8: НПЗ Кара май Ойл-Тараз

| Station  | Delivery | Price Diesel B7 | Distance |
|----------|----------|-----------------|----------|
| Каинда   | 16 days  | $830/ton        | 450 km   |
| Бишкек   | 18 days  | $830/ton        | 520 km   |
| ОШ       | 20 days  | $840/ton        | 680 km   |

**NOT:** "Average 18 days" ❌

## 🎯 Примеры запросов

### Лучшее предложение для станции Бишкек, дизель
```sql
SELECT
    s.name as supplier,
    s.departure_station,
    sso.delivery_days,
    sso.price_diesel_b7,
    (sso.price_diesel_b7 * 200) as cost_200t
FROM supplier_station_offers sso
INNER JOIN suppliers s ON sso.supplier_id = s.id
WHERE sso.station_id = 250           -- Бишкек
  AND sso.price_diesel_b7 IS NOT NULL
  AND sso.is_active = 1
ORDER BY
    s.priority ASC,
    sso.price_diesel_b7 ASC
LIMIT 3;
```

### Срочная доставка в ОШ (< 21 дня)
```sql
SELECT
    s.name,
    sso.delivery_days,
    sso.price_diesel_b7,
    sso.distance_km
FROM supplier_station_offers sso
INNER JOIN suppliers s ON sso.supplier_id = s.id
WHERE sso.station_id = 252           -- ОШ
  AND sso.delivery_days <= 21
  AND sso.is_active = 1
ORDER BY sso.delivery_days ASC;
```

## ✅ Checklist

- [x] Создан план схемы БД
- [x] Написана SQL миграция
- [x] Подготовлены seed данные
- [x] Документация создана
- [ ] **TODO: Применить миграцию к БД**
- [ ] **TODO: Обновить ProcurementAdvisorService.php**
- [ ] **TODO: Протестировать API**
- [ ] **TODO: Обновить фронтенд (показать supplier name)**

## 📚 Следующие шаги

1. Применить миграцию БД
2. Обновить ProcurementAdvisorService
3. Тестировать API endpoint
4. Обновить фронтенд
5. Деплой на продакшн

---

**Дата создания:** 2026-02-17
**Автор:** Claude Sonnet 4.5
**Статус:** Готово к применению ✅
