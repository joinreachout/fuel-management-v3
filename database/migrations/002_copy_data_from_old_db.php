<?php
/**
 * Migration 002: Copy data from old database
 * Copies master data and recent operational data
 */

require_once __DIR__ . '/../../backend/config/database.php';

echo "=== DATA MIGRATION FROM OLD DATABASE ===\n\n";

try {
    // Connect to NEW database
    $newDb = new PDO(
        "mysql:host=" . getenv('DB_HOST') . ";dbname=" . getenv('DB_NAME') . ";charset=utf8mb4",
        getenv('DB_USER'),
        getenv('DB_PASSWORD'),
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Connect to OLD database
    $oldDb = new PDO(
        "mysql:host=" . getenv('OLD_DB_HOST') . ";dbname=" . getenv('OLD_DB_NAME') . ";charset=utf8mb4",
        getenv('OLD_DB_USER'),
        getenv('OLD_DB_PASSWORD'),
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "✅ Connected to both databases\n\n";

    // Disable foreign key checks temporarily
    $newDb->exec('SET FOREIGN_KEY_CHECKS = 0');

    // =============================================
    // 1. REGIONS
    // =============================================
    echo "1. Copying REGIONS...\n";
    $regions = $oldDb->query("SELECT * FROM regions ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $newDb->prepare("
        INSERT INTO regions (id, name, code, created_at)
        VALUES (:id, :name, :code, NOW())
        ON DUPLICATE KEY UPDATE name = VALUES(name)
    ");

    foreach ($regions as $region) {
        $stmt->execute([
            'id' => $region['id'],
            'name' => $region['name'],
            'code' => $region['code'] ?? null
        ]);
    }
    echo "   ✅ Copied " . count($regions) . " regions\n\n";

    // =============================================
    // 2. STATIONS (railway_stations)
    // =============================================
    echo "2. Copying STATIONS...\n";
    $stations = $oldDb->query("
        SELECT * FROM railway_stations
        ORDER BY id
    ")->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $newDb->prepare("
        INSERT INTO stations (id, name, region_id, code, is_active, created_at)
        VALUES (:id, :name, :region_id, :code, 1, NOW())
        ON DUPLICATE KEY UPDATE name = VALUES(name)
    ");

    foreach ($stations as $station) {
        $stmt->execute([
            'id' => $station['id'],
            'name' => $station['name'],
            'region_id' => $station['region_id'],
            'code' => $station['code'] ?? null
        ]);
    }
    echo "   ✅ Copied " . count($stations) . " stations\n\n";

    // =============================================
    // 3. FUEL TYPES
    // =============================================
    echo "3. Copying FUEL TYPES...\n";
    $fuelTypes = $oldDb->query("
        SELECT * FROM fuel_types
        ORDER BY id
    ")->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $newDb->prepare("
        INSERT INTO fuel_types (id, name, code, density, unit, fuel_group, excel_mapping, created_at)
        VALUES (:id, :name, :code, :density, :unit, :fuel_group, :excel_mapping, NOW())
        ON DUPLICATE KEY UPDATE name = VALUES(name), density = VALUES(density)
    ");

    foreach ($fuelTypes as $ft) {
        $stmt->execute([
            'id' => $ft['id'],
            'name' => $ft['name'],
            'code' => $ft['code'] ?? null,
            'density' => $ft['density'] ?? 0.75,
            'unit' => $ft['unit'] ?? 'liters',
            'fuel_group' => $ft['fuel_group'] ?? null,
            'excel_mapping' => $ft['excel_mapping'] ?? null
        ]);
    }
    echo "   ✅ Copied " . count($fuelTypes) . " fuel types\n\n";

    // =============================================
    // 4. DEPOTS
    // =============================================
    echo "4. Copying DEPOTS...\n";
    $depots = $oldDb->query("
        SELECT * FROM depots
        WHERE is_active = 1
        ORDER BY id
    ")->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $newDb->prepare("
        INSERT INTO depots (id, name, code, station_id, category, capacity_m3, daily_unloading_capacity_tons, is_active, created_at)
        VALUES (:id, :name, :code, :station_id, :category, :capacity_m3, :daily_unloading_capacity_tons, 1, NOW())
        ON DUPLICATE KEY UPDATE name = VALUES(name)
    ");

    foreach ($depots as $depot) {
        $stmt->execute([
            'id' => $depot['id'],
            'name' => $depot['name'],
            'code' => $depot['code'] ?? null,
            'station_id' => $depot['station_id'],
            'category' => $depot['category'] ?? null,
            'capacity_m3' => $depot['capacity'] ?? null,
            'daily_unloading_capacity_tons' => $depot['daily_unloading_capacity_tons'] ?? null
        ]);
    }
    echo "   ✅ Copied " . count($depots) . " depots\n\n";

    // =============================================
    // 5. DEPOT_TANKS (with current stock)
    // =============================================
    echo "5. Copying DEPOT_TANKS...\n";
    $tanks = $oldDb->query("
        SELECT * FROM depot_tanks
        WHERE is_active = 1 AND current_stock > 0
        ORDER BY id
    ")->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $newDb->prepare("
        INSERT INTO depot_tanks (id, depot_id, fuel_type_id, tank_number, capacity_liters, current_stock_liters, is_active, created_at)
        VALUES (:id, :depot_id, :fuel_type_id, :tank_number, :capacity_liters, :current_stock_liters, 1, NOW())
        ON DUPLICATE KEY UPDATE current_stock_liters = VALUES(current_stock_liters)
    ");

    foreach ($tanks as $tank) {
        $stmt->execute([
            'id' => $tank['id'],
            'depot_id' => $tank['depot_id'],
            'fuel_type_id' => $tank['fuel_type_id'],
            'tank_number' => $tank['tank_number'] ?? '1',
            'capacity_liters' => $tank['capacity_liters'],
            'current_stock_liters' => $tank['current_stock'] ?? 0
        ]);
    }
    echo "   ✅ Copied " . count($tanks) . " depot tanks\n\n";

    // =============================================
    // 6. SALES_PARAMS
    // =============================================
    echo "6. Copying SALES_PARAMS...\n";
    $salesParams = $oldDb->query("
        SELECT * FROM sales_params
        ORDER BY depot_id, fuel_type_id
    ")->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $newDb->prepare("
        INSERT INTO sales_params (depot_id, fuel_type_id, liters_per_day, effective_from, created_at)
        VALUES (:depot_id, :fuel_type_id, :liters_per_day, :effective_from, NOW())
        ON DUPLICATE KEY UPDATE liters_per_day = VALUES(liters_per_day)
    ");

    foreach ($salesParams as $sp) {
        $stmt->execute([
            'depot_id' => $sp['depot_id'],
            'fuel_type_id' => $sp['fuel_type_id'],
            'liters_per_day' => $sp['liters_per_day'] ?? 0,
            'effective_from' => date('Y-m-d')
        ]);
    }
    echo "   ✅ Copied " . count($salesParams) . " sales params\n\n";

    // =============================================
    // 7. STOCK_POLICIES (from stock_levels)
    // =============================================
    echo "7. Copying STOCK_POLICIES...\n";
    $stockLevels = $oldDb->query("
        SELECT * FROM stock_levels
        ORDER BY depot_id, fuel_type_id
    ")->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $newDb->prepare("
        INSERT INTO stock_policies (depot_id, fuel_type_id, min_level_liters, critical_level_liters, max_level_liters, created_at)
        VALUES (:depot_id, :fuel_type_id, :min_level_liters, :critical_level_liters, :max_level_liters, NOW())
        ON DUPLICATE KEY UPDATE
            critical_level_liters = VALUES(critical_level_liters),
            max_level_liters = VALUES(max_level_liters)
    ");

    foreach ($stockLevels as $sl) {
        $stmt->execute([
            'depot_id' => $sl['depot_id'],
            'fuel_type_id' => $sl['fuel_type_id'],
            'min_level_liters' => $sl['min_level'] ?? null,
            'critical_level_liters' => $sl['critical_level'] ?? null,
            'max_level_liters' => $sl['max_level'] ?? null
        ]);
    }
    echo "   ✅ Copied " . count($stockLevels) . " stock policies\n\n";

    // =============================================
    // 8. SUPPLIERS
    // =============================================
    echo "8. Copying SUPPLIERS...\n";
    $suppliers = $oldDb->query("
        SELECT * FROM suppliers
        WHERE is_active = 1
        ORDER BY id
    ")->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $newDb->prepare("
        INSERT INTO suppliers (id, name, departure_station, priority, auto_score, avg_delivery_days, is_active, created_at)
        VALUES (:id, :name, :departure_station, :priority, :auto_score, :avg_delivery_days, 1, NOW())
        ON DUPLICATE KEY UPDATE name = VALUES(name)
    ");

    foreach ($suppliers as $supplier) {
        $stmt->execute([
            'id' => $supplier['id'],
            'name' => $supplier['name'],
            'departure_station' => $supplier['departure_station'] ?? null,
            'priority' => $supplier['priority'] ?? 5,
            'auto_score' => $supplier['auto_score'] ?? 5.0,
            'avg_delivery_days' => $supplier['avg_delivery_days'] ?? 28
        ]);
    }
    echo "   ✅ Copied " . count($suppliers) . " suppliers\n\n";

    // =============================================
    // 9. DELIVERY_TIMES
    // =============================================
    echo "9. Copying DELIVERY_TIMES...\n";
    $deliveryTimes = $oldDb->query("
        SELECT * FROM delivery_times
        ORDER BY supplier_id, destination_station_id
    ")->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $newDb->prepare("
        INSERT INTO delivery_times (supplier_id, destination_station_id, delivery_days, created_at)
        VALUES (:supplier_id, :destination_station_id, :delivery_days, NOW())
        ON DUPLICATE KEY UPDATE delivery_days = VALUES(delivery_days)
    ");

    foreach ($deliveryTimes as $dt) {
        $stmt->execute([
            'supplier_id' => $dt['supplier_id'],
            'destination_station_id' => $dt['destination_station_id'],
            'delivery_days' => $dt['delivery_days']
        ]);
    }
    echo "   ✅ Copied " . count($deliveryTimes) . " delivery times\n\n";

    // =============================================
    // 10. RECENT ORDERS (last 3 months)
    // =============================================
    echo "10. Copying RECENT ORDERS (last 3 months)...\n";
    $orders = $oldDb->query("
        SELECT * FROM orders_calendar
        WHERE delivery_date >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
        AND status IN ('confirmed', 'in_transit', 'planned', 'delivered')
        ORDER BY id
    ")->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $newDb->prepare("
        INSERT INTO orders (station_id, depot_id, fuel_type_id, quantity_liters, supplier_id, order_date, delivery_date, status, created_at)
        VALUES (:station_id, :depot_id, :fuel_type_id, :quantity_liters, :supplier_id, :order_date, :delivery_date, :status, NOW())
    ");

    foreach ($orders as $order) {
        $stmt->execute([
            'station_id' => $order['station_id'],
            'depot_id' => $order['depot_id'] ?? null,
            'fuel_type_id' => $order['fuel_type_id'],
            'quantity_liters' => $order['quantity'] ?? 0,
            'supplier_id' => $order['supplier_id'] ?? null,
            'order_date' => $order['order_date'] ?? date('Y-m-d'),
            'delivery_date' => $order['delivery_date'],
            'status' => $order['status'] ?? 'planned'
        ]);
    }
    echo "   ✅ Copied " . count($orders) . " recent orders\n\n";

    // =============================================
    // 11. SYSTEM_PARAMETERS (copy key settings)
    // =============================================
    echo "11. Copying SYSTEM_PARAMETERS...\n";

    // Check if table exists in old DB
    $tableExists = $oldDb->query("SHOW TABLES LIKE 'system_parameters'")->rowCount() > 0;

    if ($tableExists) {
        $params = $oldDb->query("SELECT * FROM system_parameters")->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $newDb->prepare("
            INSERT INTO system_parameters (parameter_name, parameter_value, description, data_type, created_at)
            VALUES (:parameter_name, :parameter_value, :description, :data_type, NOW())
            ON DUPLICATE KEY UPDATE parameter_value = VALUES(parameter_value)
        ");

        foreach ($params as $param) {
            $stmt->execute([
                'parameter_name' => $param['parameter_name'],
                'parameter_value' => $param['parameter_value'],
                'description' => $param['description'] ?? null,
                'data_type' => $param['data_type'] ?? 'string'
            ]);
        }
        echo "   ✅ Copied " . count($params) . " system parameters\n\n";
    } else {
        echo "   ⚠️  Table system_parameters not found in old DB - skipped\n\n";
    }

    // Re-enable foreign key checks
    $newDb->exec('SET FOREIGN_KEY_CHECKS = 1');

    // =============================================
    // SUMMARY
    // =============================================
    echo "\n=== MIGRATION COMPLETE ===\n\n";

    // Show statistics
    $stats = [
        'regions' => $newDb->query("SELECT COUNT(*) FROM regions")->fetchColumn(),
        'stations' => $newDb->query("SELECT COUNT(*) FROM stations")->fetchColumn(),
        'fuel_types' => $newDb->query("SELECT COUNT(*) FROM fuel_types")->fetchColumn(),
        'depots' => $newDb->query("SELECT COUNT(*) FROM depots")->fetchColumn(),
        'depot_tanks' => $newDb->query("SELECT COUNT(*) FROM depot_tanks")->fetchColumn(),
        'sales_params' => $newDb->query("SELECT COUNT(*) FROM sales_params")->fetchColumn(),
        'stock_policies' => $newDb->query("SELECT COUNT(*) FROM stock_policies")->fetchColumn(),
        'suppliers' => $newDb->query("SELECT COUNT(*) FROM suppliers")->fetchColumn(),
        'delivery_times' => $newDb->query("SELECT COUNT(*) FROM delivery_times")->fetchColumn(),
        'orders' => $newDb->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
    ];

    echo "Final record counts:\n";
    foreach ($stats as $table => $count) {
        echo "  - " . str_pad($table, 20) . ": " . $count . "\n";
    }

    echo "\n✅ All data migrated successfully!\n";

} catch (PDOException $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
