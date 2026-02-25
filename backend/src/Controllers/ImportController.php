<?php

namespace App\Controllers;

use App\Core\Response;
use App\Services\ImportService;

/**
 * ImportController — handles data import endpoints.
 *
 * POST /api/import/sync-erp   Sync orders from the 1C / ERP system
 */
class ImportController extends Response
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
            $this->ok($result);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
        }
    }
}
