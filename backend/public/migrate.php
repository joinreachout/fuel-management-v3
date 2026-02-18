<?php
/**
 * Migration runner - run once, then delete
 * Access: https://fuel.kittykat.tech/rev3/backend/public/migrate.php?key=migrate2026
 */

if (($_GET['key'] ?? '') !== 'migrate2026') {
    http_response_code(403);
    die(json_encode(['error' => 'Forbidden']));
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Core/Database.php';

header('Content-Type: application/json');

$results = [];
$errors = [];

$steps = [

    // ============================================
    // STEP 1: Clean depots table
    // ============================================
    '1a_drop_capacity_m3' => "
        ALTER TABLE depots DROP COLUMN IF EXISTS capacity_m3
    ",
    '1b_drop_unloading_tons' => "
        ALTER TABLE depots DROP COLUMN IF EXISTS daily_unloading_capacity_tons
    ",

    // ============================================
    // STEP 2: Drop old supplier_station_offers
    // ============================================
    '2_drop_old_table' => "
        DROP TABLE IF EXISTS supplier_station_offers
    ",

    // ============================================
    // STEP 3: Create normalized table
    // ============================================
    '3_create_normalized_table' => "
        CREATE TABLE supplier_station_offers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            supplier_id  INT NOT NULL,
            station_id   INT NOT NULL,
            fuel_type_id INT NOT NULL,
            delivery_days  INT          NOT NULL COMMENT 'Days from supplier to this station',
            distance_km    DECIMAL(10,2) NULL     COMMENT 'Distance in km (informational)',
            price_per_ton  DECIMAL(10,2) NOT NULL COMMENT 'Price per ton in currency',
            currency       VARCHAR(3)    NOT NULL DEFAULT 'USD',
            min_order_tons DECIMAL(10,2) NULL     COMMENT 'NULL = use system default',
            max_order_tons DECIMAL(10,2) NULL     COMMENT 'NULL = no upper limit',
            valid_from DATE    NOT NULL DEFAULT (CURDATE()),
            valid_to   DATE    NULL     COMMENT 'NULL = currently active',
            is_active  TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uk_supplier_station_fuel (supplier_id, station_id, fuel_type_id, valid_from),
            FOREIGN KEY (supplier_id)  REFERENCES suppliers(id)   ON DELETE CASCADE,
            FOREIGN KEY (station_id)   REFERENCES stations(id)    ON DELETE CASCADE,
            FOREIGN KEY (fuel_type_id) REFERENCES fuel_types(id)  ON DELETE CASCADE,
            INDEX idx_station_fuel    (station_id, fuel_type_id),
            INDEX idx_supplier_active (supplier_id, is_active),
            INDEX idx_active          (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",

    // ============================================
    // STEP 4: Seed data
    // НПЗ Кара май Ойл-Тараз (id=8) → ОШ (id=252), Бишкек (id=250)
    // fuel_types: 25=Diesel B7, 24=А-92, 31=А-95
    // ============================================
    '4_seed_offers' => "
        INSERT INTO supplier_station_offers
            (supplier_id, station_id, fuel_type_id, delivery_days, price_per_ton, currency, valid_from)
        VALUES
            (8, 252, 25, 20, 840.00, 'USD', '2026-01-01'),
            (8, 252, 24, 20, 820.00, 'USD', '2026-01-01'),
            (8, 252, 31, 20, 850.00, 'USD', '2026-01-01'),
            (8, 250, 25, 18, 830.00, 'USD', '2026-01-01'),
            (8, 250, 24, 18, 810.00, 'USD', '2026-01-01'),
            (8, 250, 31, 18, 840.00, 'USD', '2026-01-01')
    ",

    // ============================================
    // STEP 5: Add cost_per_ton to fuel_types
    // ============================================
    '5_add_cost_per_ton' => "
        ALTER TABLE fuel_types
            ADD COLUMN IF NOT EXISTS cost_per_ton DECIMAL(10,2) NULL
                COMMENT 'Average market cost per ton in USD. Updated manually. Used for Working Capital calculations.'
    ",

    // ============================================
    // STEP 6: Seed system_parameters
    // ============================================
    '6_seed_system_parameters' => "
        INSERT INTO system_parameters (parameter_name, parameter_value, description, data_type) VALUES
            ('planning_horizon_days',    '45',              'Days ahead to project and plan stock levels', 'int'),
            ('delivery_buffer_days',     '2',               'Safety buffer days added to supplier delivery time', 'int'),
            ('critical_fill_pct',        '40',              'Stock below this % = CATASTROPHE/CRITICAL urgency', 'float'),
            ('planned_fill_pct',         '80',              'Target operating stock level % (order up to this)', 'float'),
            ('max_useful_volume_pct',    '95',              'Do not fill tanks above this % (overfill risk)', 'float'),
            ('catastrophe_threshold_days','0',              'Already below critical level → CATASTROPHE', 'int'),
            ('critical_threshold_days',  '2',               'Days left ≤ this → CRITICAL', 'int'),
            ('must_order_threshold_days','5',               'Days left ≤ this → MUST ORDER', 'int'),
            ('warning_threshold_days',   '7',               'Days left ≤ this → WARNING', 'int'),
            ('order_step_tons',          '60',              'Order granularity in tons (60 = 1 railway wagon)', 'int'),
            ('min_order_tons',           '500',             'Minimum order size in tons', 'int'),
            ('supplier_priority_mode',   'COMPOSITE_SCORE', 'Supplier ranking: DELIVERY_TIME | COMPOSITE_SCORE | DELIVERY_WEIGHTED', 'string'),
            ('wc_enabled',               '1',               'Enable Working Capital module', 'boolean'),
            ('opportunity_cost_rate',    '8.0',             'Annual opportunity cost rate % for working capital', 'float'),
            ('working_capital_currency', 'USD',             'Currency for working capital display', 'string'),
            ('stockout_warning_days',    '5',               'Alert X days before projected stockout', 'int'),
            ('delivery_delay_tolerance', '3',               'Alert if delivery delay > X days', 'int'),
            ('consumption_anomaly_pct',  '30',              'Alert if consumption > X% above normal', 'int')
        ON DUPLICATE KEY UPDATE description = VALUES(description), data_type = VALUES(data_type)
    ",
];

$pdo = \App\Core\Database::getConnection();

foreach ($steps as $name => $sql) {
    try {
        $pdo->exec(trim($sql));
        $results[$name] = 'OK';
    } catch (\PDOException $e) {
        $errors[$name] = $e->getMessage();
    }
}

// Verify results
try {
    $tableCheck = $pdo->query("SELECT COUNT(*) as cnt FROM supplier_station_offers")->fetch();
    $paramsCheck = $pdo->query("SELECT COUNT(*) as cnt FROM system_parameters")->fetch();
    $depotCols = $pdo->query("SHOW COLUMNS FROM depots")->fetchAll(PDO::FETCH_COLUMN);
    $fuelCols = $pdo->query("SHOW COLUMNS FROM fuel_types")->fetchAll(PDO::FETCH_COLUMN);
} catch (\Exception $e) {
    $errors['verify'] = $e->getMessage();
}

echo json_encode([
    'success' => empty($errors),
    'steps' => $results,
    'errors' => $errors,
    'verification' => [
        'supplier_station_offers_rows' => $tableCheck['cnt'] ?? null,
        'system_parameters_rows' => $paramsCheck['cnt'] ?? null,
        'depots_columns' => $depotCols ?? [],
        'fuel_types_has_cost_per_ton' => in_array('cost_per_ton', $fuelCols ?? []),
    ]
], JSON_PRETTY_PRINT);
