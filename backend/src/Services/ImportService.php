<?php

namespace App\Services;

use App\Core\Database;
use App\Models\Order;
use App\Utils\UnitConverter;

/**
 * ImportService — sync orders from the 1C / ERP system (erp.kittykat.tech)
 *
 * Fetches orders from ERP API, maps station / fuel / supplier names to local
 * IDs, converts tons → liters via fuel density, and creates erp_order records.
 * Idempotent: re-running the same sync will skip already-imported orders.
 */
class ImportService
{
    private const ERP_DEFAULT_URL = 'https://erp.kittykat.tech';

    /**
     * Fuel name normalisation: Russian / ERP-code variants → canonical DB names.
     * DB fuel_types names: A-80, A-92, A-92 Euro, A-95, A-98,
     *                      Diesel B7, Diesel B10, LPG, Jet Fuel, MTBE
     */
    private const FUEL_MAP = [
        // Russian names
        'аи-80'             => 'A-80',
        'аи-92'             => 'A-92',
        'аи-92 евро'        => 'A-92 Euro',
        'аи-95'             => 'A-95',
        'аи-98'             => 'A-98',
        'дт'                => 'Diesel B7',
        'дизель'            => 'Diesel B7',
        'дизельное топливо' => 'Diesel B7',
        'дт зимнее'         => 'Diesel B7',
        'дт летнее'         => 'Diesel B7',
        'газ'               => 'LPG',
        'газ (спбт)'        => 'LPG',
        'спбт'              => 'LPG',
        'сжиженный газ'     => 'LPG',
        'керосин'           => 'Jet Fuel',
        'авиакеросин'       => 'Jet Fuel',
        'мтбэ'              => 'MTBE',
        // English variants
        'diesel b7'         => 'Diesel B7',
        'diesel b10'        => 'Diesel B10',
        'diesel'            => 'Diesel B7',
        'a-80'              => 'A-80',
        'a-92'              => 'A-92',
        'a-92 euro'         => 'A-92 Euro',
        'a-95'              => 'A-95',
        'a-98'              => 'A-98',
        'lpg'               => 'LPG',
        'jet fuel'          => 'Jet Fuel',
        'mtbe'              => 'MTBE',
        // ERP fuel_code values (from erp.kittykat.tech catalogs)
        'gas80'             => 'A-80',
        'gas92'             => 'A-92',
        'gas92eur'          => 'A-92 Euro',
        'gas95'             => 'A-95',
        'gas98'             => 'A-98',
        'diesb7'            => 'Diesel B7',
        'diesb7w'           => 'Diesel B7',
        'diesb10'           => 'Diesel B10',
    ];

    // ─── Public API ──────────────────────────────────────────────────────────

    /**
     * Synchronise orders from ERP into local orders table as erp_order records.
     *
     * @param  string $baseUrl     ERP base URL
     * @param  int    $periodDays  Fetch orders with order_date OR delivery_date >= today − N days
     * @return array  Result summary
     */
    public static function syncFromErp(string $baseUrl, int $periodDays): array
    {
        $start = microtime(true);

        // Load reference catalogs once
        $stations  = Database::fetchAll("SELECT id, name, code FROM stations  ORDER BY name");
        $fuelTypes = Database::fetchAll("SELECT id, name, code, density FROM fuel_types ORDER BY name");
        $suppliers = Database::fetchAll("SELECT id, name FROM suppliers ORDER BY name");
        $depots    = Database::fetchAll("SELECT id, name, station_id FROM depots    ORDER BY station_id, name");

        $erpOrders = self::fetchFromErp($baseUrl);

        // Filter by date window
        $cutoff   = date('Y-m-d', strtotime("-{$periodDays} days"));
        $filtered = array_values(array_filter(
            $erpOrders,
            static fn(array $o): bool =>
                ($o['order_date']    ?? '') >= $cutoff ||
                ($o['delivery_date'] ?? '') >= $cutoff
        ));

        $imported = 0;
        $skipped  = 0;
        $errors   = [];

        foreach ($filtered as $erpOrder) {
            $erpId = $erpOrder['order_id'] ?? '';
            try {
                // Map station
                $station = self::matchStation($erpOrder['station'] ?? '', $stations);
                if (!$station) {
                    $errors[] = "[{$erpId}] Station not matched: '{$erpOrder['station']}'";
                    continue;
                }

                // Map fuel type (try name, fuel_type, fuel_code fields)
                $fuelRaw  = $erpOrder['fuel_type'] ?? $erpOrder['fuel_name'] ?? $erpOrder['fuel_code'] ?? '';
                $fuelType = self::matchFuelType($fuelRaw, $fuelTypes);
                if (!$fuelType) {
                    $errors[] = "[{$erpId}] Fuel type not matched: '{$fuelRaw}'";
                    continue;
                }

                // Map supplier (optional)
                $supplierRaw = $erpOrder['supplier'] ?? $erpOrder['supplier_name'] ?? '';
                $supplier    = self::matchSupplier($supplierRaw, $suppliers);

                // First depot for this station
                $depot = null;
                foreach ($depots as $d) {
                    if ((int)$d['station_id'] === (int)$station['id']) {
                        $depot = $d;
                        break;
                    }
                }

                // Quantity: ERP stores tons → convert to liters
                $qtyTons   = (float)($erpOrder['quantity'] ?? 0);
                $density   = (float)$fuelType['density'];
                $qtyLiters = UnitConverter::tonsToLiters($qtyTons, $density > 0 ? $density : 0.84);

                $deliveryDate = $erpOrder['delivery_date'] ?? date('Y-m-d', strtotime('+7 days'));
                $orderDate    = $erpOrder['order_date']    ?? date('Y-m-d');

                if (self::isDuplicate($erpId, (int)$station['id'], (int)$fuelType['id'], $deliveryDate, $qtyLiters)) {
                    $skipped++;
                    continue;
                }

                // Map ERP status → local status
                $erpStatus  = strtolower(trim($erpOrder['status'] ?? ''));
                $statusMap  = [
                    'pending'    => 'confirmed',
                    'confirmed'  => 'confirmed',
                    'in_transit' => 'in_transit',
                    'delivered'  => 'delivered',
                    'cancelled'  => 'cancelled',
                ];
                $status = $statusMap[$erpStatus] ?? 'confirmed';

                $notes = "ERP Sync: {$erpId} | {$erpOrder['station']} | {$fuelRaw}"
                       . ($supplierRaw !== '' ? " | {$supplierRaw}" : '');

                Order::createErpOrder([
                    'station_id'      => (int)$station['id'],
                    'depot_id'        => $depot ? (int)$depot['id'] : null,
                    'fuel_type_id'    => (int)$fuelType['id'],
                    'supplier_id'     => $supplier ? (int)$supplier['id'] : null,
                    'quantity_liters' => $qtyLiters,
                    'price_per_ton'   => !empty($erpOrder['price_per_ton']) ? (float)$erpOrder['price_per_ton'] : null,
                    'order_date'      => $orderDate,
                    'delivery_date'   => $deliveryDate,
                    'status'          => $status,
                    'notes'           => $notes,
                ]);

                $imported++;
            } catch (\Throwable $e) {
                $errors[] = "[{$erpId}] " . $e->getMessage();
            }
        }

        return [
            'imported'       => $imported,
            'skipped'        => $skipped,
            'total_found'    => count($erpOrders),
            'filtered_count' => count($filtered),
            'errors'         => $errors,
            'execution_ms'   => (int)round((microtime(true) - $start) * 1000),
            'synced_at'      => date('Y-m-d H:i:s'),
            'mapping_stats'  => [
                'stations'   => count($stations),
                'fuel_types' => count($fuelTypes),
                'suppliers'  => count($suppliers),
            ],
        ];
    }

    // ─── Private helpers ─────────────────────────────────────────────────────

    /**
     * Fetch orders array from ERP REST API (GET /api/orders).
     *
     * @throws \RuntimeException on network or JSON error
     */
    private static function fetchFromErp(string $baseUrl): array
    {
        $url = rtrim($baseUrl, '/') . '/api/orders';
        $ctx = stream_context_create([
            'http' => [
                'method'  => 'GET',
                'timeout' => 30,
                'header'  => "User-Agent: FuelSystemREV3/1.0 ERP-Sync\r\nAccept: application/json\r\n",
            ],
        ]);

        $raw = @file_get_contents($url, false, $ctx);
        if ($raw === false) {
            throw new \RuntimeException("Cannot connect to ERP at {$url}. Check URL and network.");
        }

        $data = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException("Invalid JSON from ERP: " . json_last_error_msg());
        }
        if (!is_array($data)) {
            throw new \RuntimeException("ERP returned unexpected format (expected JSON array).");
        }

        return $data;
    }

    /**
     * Fuzzy-match station name.
     * Handles ERP format: "АЗС Name (Depot, Station)" or "БиММ (Станция Бишкек)".
     */
    private static function matchStation(string $raw, array $stations): ?array
    {
        if (trim($raw) === '') return null;
        $name = mb_strtolower(trim($raw));

        // Build candidate list by extracting parts from parentheses
        $candidates = [];
        if (preg_match('/\(([^,)]+),\s*([^)]+)\)/', $name, $m)) {
            $candidates[] = mb_strtolower(trim($m[2])); // "Станция Бишкек"
            $candidates[] = mb_strtolower(trim($m[1]));
        } elseif (preg_match('/\(([^)]+)\)/', $name, $m)) {
            $candidates[] = mb_strtolower(trim($m[1]));
        }
        $candidates[] = preg_replace('/\s*\(.*\)\s*/', '', $name); // strip parens
        $candidates[] = $name;

        // Strip common prefixes
        $prefixes = ['станция ', 'жд ', 'нпз ', 'нб ', 'азс '];
        $extra = [];
        foreach ($candidates as $c) {
            foreach ($prefixes as $p) {
                if (str_starts_with($c, $p)) {
                    $extra[] = mb_substr($c, mb_strlen($p));
                }
            }
        }
        $candidates = array_unique(array_filter(array_map('trim', array_merge($candidates, $extra))));

        // Exact match
        foreach ($candidates as $c) {
            foreach ($stations as $s) {
                if (mb_strtolower($s['name']) === $c || mb_strtolower($s['code'] ?? '') === $c) {
                    return $s;
                }
            }
        }
        // Substring match
        foreach ($candidates as $c) {
            foreach ($stations as $s) {
                $n = mb_strtolower($s['name']);
                if (str_contains($n, $c) || str_contains($c, $n)) {
                    return $s;
                }
            }
        }

        return null;
    }

    /**
     * Match fuel type by name or ERP code.
     */
    private static function matchFuelType(string $raw, array $fuelTypes): ?array
    {
        if (trim($raw) === '') return null;
        $key      = mb_strtolower(trim($raw));
        $resolved = self::FUEL_MAP[$key] ?? $raw;
        $search   = mb_strtolower($resolved);

        foreach ($fuelTypes as $ft) {
            if (mb_strtolower($ft['name']) === $search) return $ft;
        }
        // Try code field
        foreach ($fuelTypes as $ft) {
            if (mb_strtolower($ft['code'] ?? '') === $key) return $ft;
        }
        // Substring fallback
        foreach ($fuelTypes as $ft) {
            $n = mb_strtolower($ft['name']);
            if (str_contains($n, $search) || str_contains($search, $n)) return $ft;
        }

        return null;
    }

    /**
     * Match supplier by name (fuzzy).
     */
    private static function matchSupplier(string $raw, array $suppliers): ?array
    {
        if (trim($raw) === '') return null;
        $name = mb_strtolower(trim($raw));

        foreach ($suppliers as $s) {
            if (mb_strtolower($s['name']) === $name) return $s;
        }
        foreach ($suppliers as $s) {
            $n = mb_strtolower($s['name']);
            if (str_contains($n, $name) || str_contains($name, $n)) return $s;
        }

        return null;
    }

    /**
     * Check if this ERP order was already imported.
     * Primary: ERP order_id embedded in notes ("ERP Sync: {id} | ...").
     * Fallback: same station + fuel + date + qty ±5%.
     */
    private static function isDuplicate(
        string $erpId,
        int    $stationId,
        int    $fuelTypeId,
        string $deliveryDate,
        float  $qtyLiters
    ): bool {
        if ($erpId !== '') {
            $safe = str_replace(['%', '_'], ['\\%', '\\_'], $erpId);
            $row  = Database::fetchOne(
                "SELECT id FROM orders
                 WHERE order_type = 'erp_order' AND notes LIKE ?
                 LIMIT 1",
                ["%ERP Sync: {$safe}%"]
            );
            if ($row) return true;
        }

        // Overlap: same station+fuel+date+qty within ±5%
        $min = $qtyLiters * 0.95;
        $max = $qtyLiters * 1.05;
        $row = Database::fetchOne(
            "SELECT id FROM orders
             WHERE order_type    = 'erp_order'
               AND station_id   = ?
               AND fuel_type_id  = ?
               AND delivery_date = ?
               AND quantity_liters BETWEEN ? AND ?
             LIMIT 1",
            [$stationId, $fuelTypeId, $deliveryDate, $min, $max]
        );

        return (bool)$row;
    }
}
