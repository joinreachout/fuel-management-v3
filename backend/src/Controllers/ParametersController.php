<?php

namespace App\Controllers;

use App\Services\ParametersService;

/**
 * Parameters Controller
 * All GET endpoints return real data from DB — no mocks, no hardcoding.
 * PUT endpoints update individual rows inline.
 *
 * Routes:
 *   GET  /api/parameters/system          → system_parameters grouped by category
 *   PUT  /api/parameters/system/:key     → update one system_parameter
 *   GET  /api/parameters/fuel-types      → fuel_types (density only — pricing in supplier_station_offers)
 *   PUT  /api/parameters/fuel-types/:id  → update density only
 *   GET  /api/parameters/sales-params    → sales_params with names
 *   PUT  /api/parameters/sales-params/:id → update liters_per_day
 *   GET  /api/parameters/stock-policies  → stock_policies with names + capacity
 *   PUT  /api/parameters/stock-policies/:id → update thresholds
 *   GET  /api/parameters/supplier-offers → supplier_station_offers with names
 *   PUT  /api/parameters/supplier-offers/:id → update price_per_ton + delivery_days
 *   GET  /api/parameters/depot-tanks     → depot_tanks reference view
 */
class ParametersController
{
    // ─── GET endpoints ───────────────────────────

    public function getSystemParameters(): void
    {
        try {
            $data = ParametersService::getSystemParameters();
            $this->ok(['data' => $data]);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    public function getFuelTypes(): void
    {
        try {
            $data = ParametersService::getFuelTypes();
            $this->ok(['data' => $data, 'count' => count($data)]);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    public function getSalesParams(): void
    {
        try {
            $data = ParametersService::getSalesParams();
            $this->ok(['data' => $data, 'count' => count($data)]);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    public function getStockPolicies(): void
    {
        try {
            $data = ParametersService::getStockPolicies();
            $this->ok(['data' => $data, 'count' => count($data)]);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    public function getSupplierOffers(): void
    {
        try {
            $data = ParametersService::getSupplierOffers();
            $this->ok(['data' => $data, 'count' => count($data)]);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    public function getDepotTanks(): void
    {
        try {
            $data = ParametersService::getDepotTanks();
            $this->ok(['data' => $data, 'count' => count($data)]);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    // ─── PUT endpoints ───────────────────────────

    public function updateSystemParameter(string $key): void
    {
        try {
            $body = $this->parseBody();
            if (!isset($body['value'])) {
                throw new \InvalidArgumentException("Missing field: value");
            }
            $updated = ParametersService::updateSystemParameter($key, (string)$body['value']);
            $this->ok(['updated' => $updated, 'key' => $key, 'value' => $body['value']]);
        } catch (\InvalidArgumentException $e) {
            $this->badRequest($e->getMessage());
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    public function updateFuelType(int $id): void
    {
        try {
            $body = $this->parseBody();
            if (!isset($body['density'])) {
                throw new \InvalidArgumentException("Missing field: density");
            }
            $updated = ParametersService::updateFuelType(
                $id,
                (float)$body['density']
            );
            $this->ok(['updated' => $updated, 'id' => $id]);
        } catch (\InvalidArgumentException $e) {
            $this->badRequest($e->getMessage());
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    public function updateSalesParam(int $id): void
    {
        try {
            $body = $this->parseBody();
            if (!isset($body['liters_per_day'])) {
                throw new \InvalidArgumentException("Missing field: liters_per_day");
            }
            $updated = ParametersService::updateSalesParam($id, (float)$body['liters_per_day']);
            $this->ok(['updated' => $updated, 'id' => $id]);
        } catch (\InvalidArgumentException $e) {
            $this->badRequest($e->getMessage());
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    public function updateStockPolicy(int $id): void
    {
        try {
            $body = $this->parseBody();
            $updated = ParametersService::updateStockPolicy(
                $id,
                isset($body['min_level_liters'])      ? (float)$body['min_level_liters']      : null,
                isset($body['critical_level_liters']) ? (float)$body['critical_level_liters'] : null,
                isset($body['target_level_liters'])   ? (float)$body['target_level_liters']   : null
            );
            $this->ok(['updated' => $updated, 'id' => $id]);
        } catch (\InvalidArgumentException $e) {
            $this->badRequest($e->getMessage());
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    public function updateSupplierOffer(int $id): void
    {
        try {
            $body = $this->parseBody();
            if (!isset($body['price_per_ton']) || !isset($body['delivery_days'])) {
                throw new \InvalidArgumentException("Missing fields: price_per_ton, delivery_days");
            }
            $updated = ParametersService::updateSupplierOffer(
                $id,
                (float)$body['price_per_ton'],
                (int)$body['delivery_days']
            );
            $this->ok(['updated' => $updated, 'id' => $id]);
        } catch (\InvalidArgumentException $e) {
            $this->badRequest($e->getMessage());
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    // ─── Helpers ─────────────────────────────────

    private function parseBody(): array
    {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            throw new \InvalidArgumentException("Invalid JSON body");
        }
        return $data;
    }

    private function ok(array $payload): void
    {
        http_response_code(200);
        echo json_encode(array_merge(['success' => true], $payload), JSON_PRETTY_PRINT);
    }

    private function badRequest(string $message): void
    {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $message], JSON_PRETTY_PRINT);
    }

    private function error(string $message): void
    {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $message], JSON_PRETTY_PRINT);
    }
}
