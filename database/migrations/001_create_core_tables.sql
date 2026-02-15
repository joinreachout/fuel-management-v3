-- Migration 001: Create Core Tables
-- Fuel Management System v3.0
-- Date: 2025-02-15

-- ============================================
-- СПРАВОЧНЫЕ ТАБЛИЦЫ
-- ============================================

-- Регионы
CREATE TABLE IF NOT EXISTS regions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    code VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Железнодорожные станции
CREATE TABLE IF NOT EXISTS stations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    region_id INT NOT NULL,
    code VARCHAR(50),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (region_id) REFERENCES regions(id),
    INDEX idx_region (region_id),
    INDEX idx_active (is_active),
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Типы топлива
CREATE TABLE IF NOT EXISTS fuel_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(20),
    density DECIMAL(5,3) NOT NULL DEFAULT 0.750 COMMENT 'Плотность в кг/л',
    unit VARCHAR(20) DEFAULT 'liters',
    fuel_group VARCHAR(50),
    excel_mapping VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uk_code (code),
    INDEX idx_name (name),
    INDEX idx_group (fuel_group)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Склады (депо)
CREATE TABLE IF NOT EXISTS depots (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(50),
    station_id INT NOT NULL,
    category VARCHAR(50),
    capacity_m3 DECIMAL(10,2) COMMENT 'Общая вместимость в м³',
    daily_unloading_capacity_tons DECIMAL(10,2) COMMENT 'Суточная разгрузочная способность (тонн)',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (station_id) REFERENCES stations(id),
    INDEX idx_station (station_id),
    INDEX idx_active (is_active),
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- ИНВЕНТАРЬ (SOURCE OF TRUTH)
-- ============================================

-- Резервуары - ЕДИНСТВЕННЫЙ источник правды по остаткам
CREATE TABLE IF NOT EXISTS depot_tanks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    depot_id INT NOT NULL,
    fuel_type_id INT NOT NULL,
    tank_number VARCHAR(20),
    capacity_liters DECIMAL(12,2) NOT NULL COMMENT 'Ёмкость резервуара (литры)',
    current_stock_liters DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT 'Текущий остаток (литры) - SOURCE OF TRUTH',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (depot_id) REFERENCES depots(id),
    FOREIGN KEY (fuel_type_id) REFERENCES fuel_types(id),
    UNIQUE KEY uk_depot_fuel_tank (depot_id, fuel_type_id, tank_number),
    INDEX idx_depot_fuel (depot_id, fuel_type_id),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Триггер: проверка что остаток не превышает ёмкость
DELIMITER //
CREATE TRIGGER IF NOT EXISTS check_tank_capacity_before_update
BEFORE UPDATE ON depot_tanks
FOR EACH ROW
BEGIN
    IF NEW.current_stock_liters > NEW.capacity_liters THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Current stock cannot exceed tank capacity';
    END IF;
END//
DELIMITER ;

-- ============================================
-- ПАРАМЕТРЫ И ПОЛИТИКИ
-- ============================================

-- Параметры продаж/потребления
CREATE TABLE IF NOT EXISTS sales_params (
    id INT PRIMARY KEY AUTO_INCREMENT,
    depot_id INT NOT NULL,
    fuel_type_id INT NOT NULL,
    liters_per_day DECIMAL(10,2) NOT NULL COMMENT 'Потребление литров/день',
    effective_from DATE NOT NULL,
    effective_to DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by VARCHAR(100),
    updated_by VARCHAR(100),

    FOREIGN KEY (depot_id) REFERENCES depots(id),
    FOREIGN KEY (fuel_type_id) REFERENCES fuel_types(id),
    UNIQUE KEY uk_depot_fuel_date (depot_id, fuel_type_id, effective_from),
    INDEX idx_depot_fuel (depot_id, fuel_type_id),
    INDEX idx_effective_dates (effective_from, effective_to)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Политики уровней запасов (не факт, а правила!)
CREATE TABLE IF NOT EXISTS stock_policies (
    id INT PRIMARY KEY AUTO_INCREMENT,
    depot_id INT NOT NULL,
    fuel_type_id INT NOT NULL,
    min_level_liters DECIMAL(12,2) COMMENT 'Минимальный уровень (литры)',
    critical_level_liters DECIMAL(12,2) COMMENT 'Критический уровень (литры)',
    target_level_liters DECIMAL(12,2) COMMENT 'Целевой уровень (литры)',
    max_level_liters DECIMAL(12,2) COMMENT 'Максимальный уровень (литры)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (depot_id) REFERENCES depots(id),
    FOREIGN KEY (fuel_type_id) REFERENCES fuel_types(id),
    UNIQUE KEY uk_depot_fuel (depot_id, fuel_type_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Системные параметры (конфигурация)
CREATE TABLE IF NOT EXISTS system_parameters (
    id INT PRIMARY KEY AUTO_INCREMENT,
    parameter_name VARCHAR(100) NOT NULL UNIQUE,
    parameter_value TEXT NOT NULL,
    description TEXT,
    data_type ENUM('int', 'float', 'string', 'boolean', 'json') DEFAULT 'string',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_name (parameter_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- ПОСТАВЩИКИ
-- ============================================

CREATE TABLE IF NOT EXISTS suppliers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    departure_station VARCHAR(100),
    priority INT DEFAULT 5,
    auto_score DECIMAL(5,2) DEFAULT 5.00,
    avg_delivery_days INT DEFAULT 28,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_name (name),
    INDEX idx_active (is_active),
    INDEX idx_priority (priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Сроки доставки по направлениям
CREATE TABLE IF NOT EXISTS delivery_times (
    id INT PRIMARY KEY AUTO_INCREMENT,
    supplier_id INT NOT NULL,
    destination_station_id INT NOT NULL,
    delivery_days INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    FOREIGN KEY (destination_station_id) REFERENCES stations(id),
    UNIQUE KEY uk_supplier_station (supplier_id, destination_station_id),
    INDEX idx_supplier (supplier_id),
    INDEX idx_station (destination_station_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- ЗАКАЗЫ
-- ============================================

CREATE TABLE IF NOT EXISTS orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_number VARCHAR(50) UNIQUE,
    station_id INT NOT NULL,
    depot_id INT,
    fuel_type_id INT NOT NULL,
    quantity_liters DECIMAL(12,2) NOT NULL COMMENT 'Количество (литры)',
    supplier_id INT,
    order_date DATE NOT NULL,
    delivery_date DATE NOT NULL,
    status ENUM('planned', 'confirmed', 'in_transit', 'delivered', 'cancelled') DEFAULT 'planned',
    price_per_ton DECIMAL(10,2),
    total_amount DECIMAL(12,2),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by VARCHAR(100),
    updated_by VARCHAR(100),

    FOREIGN KEY (station_id) REFERENCES stations(id),
    FOREIGN KEY (depot_id) REFERENCES depots(id),
    FOREIGN KEY (fuel_type_id) REFERENCES fuel_types(id),
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    INDEX idx_delivery_date (delivery_date),
    INDEX idx_status (status),
    INDEX idx_station_fuel (station_id, fuel_type_id),
    INDEX idx_delivery_status (delivery_date, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- ТРАНСФЕРЫ
-- ============================================

CREATE TABLE IF NOT EXISTS transfers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    from_station_id INT NOT NULL,
    to_station_id INT NOT NULL,
    fuel_type_id INT NOT NULL,
    transfer_amount_liters DECIMAL(12,2) NOT NULL COMMENT 'Количество переброски (литры)',
    status ENUM('pending', 'in_progress', 'in_process', 'completed', 'cancelled') DEFAULT 'pending',
    urgency ENUM('NORMAL', 'MUST_ORDER', 'CRITICAL', 'CATASTROPHE') NOT NULL,
    estimated_days DECIMAL(3,1) NOT NULL,
    from_station_level_before DECIMAL(12,2),
    to_station_level_before DECIMAL(12,2),
    from_station_level_after DECIMAL(12,2),
    to_station_level_after DECIMAL(12,2),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    cancelled_at TIMESTAMP NULL,
    created_by VARCHAR(100) DEFAULT 'system',

    FOREIGN KEY (from_station_id) REFERENCES stations(id),
    FOREIGN KEY (to_station_id) REFERENCES stations(id),
    FOREIGN KEY (fuel_type_id) REFERENCES fuel_types(id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    INDEX idx_from_station (from_station_id),
    INDEX idx_to_station (to_station_id),
    INDEX idx_urgency (urgency)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Логи трансферов
CREATE TABLE IF NOT EXISTS transfer_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    transfer_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by VARCHAR(100) DEFAULT 'system',

    FOREIGN KEY (transfer_id) REFERENCES transfers(id) ON DELETE CASCADE,
    INDEX idx_transfer_id (transfer_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- АУДИТ
-- ============================================

-- Аудит изменений остатков
CREATE TABLE IF NOT EXISTS stock_audit (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    depot_tank_id INT NOT NULL,
    old_stock_liters DECIMAL(12,2),
    new_stock_liters DECIMAL(12,2),
    change_liters DECIMAL(12,2),
    change_reason ENUM('order', 'transfer', 'adjustment', 'consumption', 'manual'),
    reference_id INT COMMENT 'ID заказа или трансфера',
    changed_by VARCHAR(100),
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (depot_tank_id) REFERENCES depot_tanks(id),
    INDEX idx_tank (depot_tank_id),
    INDEX idx_date (changed_at),
    INDEX idx_reason (change_reason)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Триггер: автоматическая запись аудита при изменении остатков
DELIMITER //
CREATE TRIGGER IF NOT EXISTS audit_stock_changes
AFTER UPDATE ON depot_tanks
FOR EACH ROW
BEGIN
    IF OLD.current_stock_liters != NEW.current_stock_liters THEN
        INSERT INTO stock_audit (
            depot_tank_id,
            old_stock_liters,
            new_stock_liters,
            change_liters,
            change_reason
        ) VALUES (
            NEW.id,
            OLD.current_stock_liters,
            NEW.current_stock_liters,
            NEW.current_stock_liters - OLD.current_stock_liters,
            'manual'
        );
    END IF;
END//
DELIMITER ;

-- ============================================
-- VIEWS (для удобства)
-- ============================================

-- View: Текущие остатки с автоматическим пересчётом в тонны
CREATE OR REPLACE VIEW v_current_stock AS
SELECT
    dt.id as tank_id,
    dt.depot_id,
    d.name as depot_name,
    d.station_id,
    s.name as station_name,
    r.name as region_name,
    dt.fuel_type_id,
    ft.name as fuel_type_name,
    ft.density,
    dt.current_stock_liters,
    ROUND((dt.current_stock_liters * ft.density) / 1000, 2) as current_stock_tons,
    dt.capacity_liters,
    ROUND((dt.capacity_liters * ft.density) / 1000, 2) as capacity_tons,
    ROUND(dt.current_stock_liters / dt.capacity_liters * 100, 1) as fill_percentage,
    dt.is_active
FROM depot_tanks dt
JOIN depots d ON dt.depot_id = d.id
JOIN stations s ON d.station_id = s.id
JOIN regions r ON s.region_id = r.id
JOIN fuel_types ft ON dt.fuel_type_id = ft.id
WHERE dt.is_active = 1 AND d.is_active = 1;

-- View: Агрегированные остатки по станциям
CREATE OR REPLACE VIEW v_station_stock AS
SELECT
    s.id as station_id,
    s.name as station_name,
    r.name as region_name,
    ft.id as fuel_type_id,
    ft.name as fuel_type_name,
    ft.density,
    COALESCE(SUM(dt.current_stock_liters), 0) as total_stock_liters,
    COALESCE(SUM(dt.capacity_liters), 0) as total_capacity_liters,
    ROUND(COALESCE(SUM(dt.current_stock_liters * ft.density) / 1000, 0), 2) as total_stock_tons,
    ROUND(COALESCE(SUM(dt.capacity_liters * ft.density) / 1000, 0), 2) as total_capacity_tons,
    ROUND(
        CASE
            WHEN SUM(dt.capacity_liters) > 0
            THEN SUM(dt.current_stock_liters) / SUM(dt.capacity_liters) * 100
            ELSE 0
        END, 1
    ) as fill_percentage
FROM stations s
JOIN regions r ON s.region_id = r.id
JOIN depots d ON s.id = d.station_id AND d.is_active = 1
JOIN depot_tanks dt ON d.id = dt.depot_id AND dt.is_active = 1
JOIN fuel_types ft ON dt.fuel_type_id = ft.id
WHERE s.is_active = 1
GROUP BY s.id, s.name, r.name, ft.id, ft.name, ft.density;
