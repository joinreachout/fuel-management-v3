<?php

namespace App\Controllers;

use App\Services\ImportService;

/**
 * ImportController — handles data import endpoints.
 *
 * POST /api/import/sync-erp   Sync orders from the 1C / ERP system
 */
class ImportController
{
    /**
     * POST /api/import/sync-erp
     *
     * Request body (JSON):
     *   base_url    string   ERP base URL  (default: https://erp.kittykat.tech)
     *   period_days int      Days to look back (default: 7, max: 90)
     */
    public function syncErp(): void
    {
        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        $baseUrl    = trim($body['base_url']    ?? 'https://erp.kittykat.tech');
        $periodDays = max(1, min((int)($body['period_days'] ?? 7), 90));

        if (!preg_match('#^https?://#i', $baseUrl)) {
            $this->error('base_url must start with http:// or https://');
            return;
        }

        try {
            $result = ImportService::syncFromErp($baseUrl, $periodDays);
            $this->ok(['data' => $result]);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
        }
    }

    // ─── Response helpers ────────────────────────────────────────────────────

    private function ok(array $payload): void
    {
        http_response_code(200);
        echo json_encode(array_merge(['success' => true], $payload), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    private function error(string $message): void
    {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $message], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}
