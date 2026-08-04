<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BiometricSyncService
{
    /**
     * Fetch recent attendance punches from the biometric webhook.
     *
     * @return array{success: bool, records?: array, message?: string}
     */
    public function fetchAttendance(): array
    {
        $url = trim((string) config('biometric.webhook_url'));

        if ($url === '') {
            return [
                'success' => false,
                'message' => 'Biometric webhook URL is not configured. Set BIOMETRIC_WEBHOOK_URL in .env',
            ];
        }

        try {
            $response = Http::timeout((int) config('biometric.timeout', 60))
                ->acceptJson()
                ->post($url, [
                    'host'     => config('biometric.device_host'),
                    'port'     => (int) config('biometric.device_port'),
                    'password' => (int) config('biometric.device_password'),
                    'days'     => (int) config('biometric.sync_days'),
                ]);
        } catch (ConnectionException $e) {
            Log::error('Biometric webhook connection failed', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'Could not reach the biometric webhook: ' . $e->getMessage(),
            ];
        }

        if ($response->failed()) {
            Log::warning('Biometric webhook returned an error response', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return [
                'success' => false,
                'message' => 'Biometric webhook returned HTTP ' . $response->status(),
            ];
        }

        $payload = $response->json();

        if (!is_array($payload) || ($payload['success'] ?? false) !== true) {
            Log::warning('Biometric webhook returned an unexpected payload', [
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'message' => $payload['message'] ?? 'Biometric webhook returned an unexpected response.',
            ];
        }

        return [
            'success' => true,
            'records' => $payload['data'] ?? [],
            'message' => $payload['message'] ?? null,
        ];
    }
}
