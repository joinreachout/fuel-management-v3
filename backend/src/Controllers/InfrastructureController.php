<?php

namespace App\Controllers;

use App\Services\InfrastructureService;

/**
 * Infrastructure Controller
 * Handles the Station → Depot → Tank hierarchy.
 *
 * Routes:
 *   GET  /api/infrastructure/hierarchy          → full tree with stats
 *   PUT  /api/infrastructure/stations/:id       → update station name/code/active
 *   PUT  /api/infrastructure/depots/:id         → update depot name/code/category/active
 *   PUT  /api/infrastructure/tanks/:id          → update tank capacity + stock
 *   POST /api/infrastructure/tanks              → add new tank to depot
 */
class InfrastructureController
{
    public function getHierarchy(): void
    {
        try {
            $data = InfrastructureService::getHierarchy();
            $this->ok(['data' => $data]);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    public function updateStation(int $id): void
    {
        try {
            $body = $this->parseBody();
            $this->require($body, ['name', 'code']);

            $updated = InfrastructureService::updateStation(
                $id,
                trim($body['name']),
                trim($body['code']),
                isset($body['is_active']) ? (int)$body['is_active'] : 1
            );
            $this->ok(['updated' => $updated, 'id' => $id]);
        } catch (\InvalidArgumentException $e) {
            $this->badRequest($e->getMessage());
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    public function updateDepot(int $id): void
    {
        try {
            $body = $this->parseBody();
            $this->require($body, ['name', 'code']);

            $updated = InfrastructureService::updateDepot(
                $id,
                trim($body['name']),
                trim($body['code']),
                isset($body['category']) ? trim($body['category']) : null,
                isset($body['is_active']) ? (int)$body['is_active'] : 1
            );
            $this->ok(['updated' => $updated, 'id' => $id]);
        } catch (\InvalidArgumentException $e) {
            $this->badRequest($e->getMessage());
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    public function updateTank(int $id): void
    {
        try {
            $body = $this->parseBody();
            $this->require($body, ['capacity_liters', 'current_stock_liters']);

            $updated = InfrastructureService::updateTank(
                $id,
                (float)$body['capacity_liters'],
                (float)$body['current_stock_liters']
            );
            $this->ok(['updated' => $updated, 'id' => $id]);
        } catch (\InvalidArgumentException $e) {
            $this->badRequest($e->getMessage());
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    public function addTank(): void
    {
        try {
            $body = $this->parseBody();
            $this->require($body, ['depot_id', 'fuel_type_id', 'capacity_liters']);

            $newId = InfrastructureService::addTank(
                (int)$body['depot_id'],
                (int)$body['fuel_type_id'],
                (float)$body['capacity_liters'],
                isset($body['current_stock_liters']) ? (float)$body['current_stock_liters'] : 0
            );

            http_response_code(201);
            echo json_encode(['success' => true, 'id' => $newId], JSON_PRETTY_PRINT);
        } catch (\InvalidArgumentException $e) {
            $this->badRequest($e->getMessage());
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function parseBody(): array
    {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) {
            throw new \InvalidArgumentException("Invalid JSON body");
        }
        return $data;
    }

    private function require(array $body, array $fields): void
    {
        foreach ($fields as $f) {
            if (!isset($body[$f])) {
                throw new \InvalidArgumentException("Missing required field: $f");
            }
        }
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
