<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BiometricSyncService
{
    /**
     * Fetch recent attendance punches from every configured biometric machine
     * (e.g. 'zk' and 'rs9n') via the unified Attendance API and merge them
     * into a single record set. A machine that fails does not block the
     * others — only surfaces as a warning in the aggregated message.
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

        $machines = config('biometric.machines', []);

        if ($machines === []) {
            return [
                'success' => false,
                'message' => 'No biometric machines configured. Set BIOMETRIC_MACHINES in .env',
            ];
        }

        $records = [];
        $failures = [];

        foreach ($machines as $machine) {
            $result = $this->fetchFromMachine($url, $machine);

            if ($result['success']) {
                $records = array_merge($records, $result['records']);
            } else {
                $failures[] = "{$machine}: {$result['message']}";
            }
        }

        if ($records === [] && $failures !== []) {
            return [
                'success' => false,
                'message' => implode(' | ', $failures),
            ];
        }

        return [
            'success' => true,
            'records' => $records,
            'message' => $failures !== [] ? 'Partial sync, some machines failed: ' . implode(' | ', $failures) : null,
        ];
    }

    /**
     * @return array{success: bool, records: array, message?: string}
     */
    private function fetchFromMachine(string $url, string $machine): array
    {
        $token = trim((string) config('biometric.api_secret_token'));

        try {
            $request = Http::timeout((int) config('biometric.timeout', 60))
                ->acceptJson();

            if ($token !== '') {
                $request = $request->withToken($token);
            }

            $response = $request->post($url, [
                'machine' => $machine,
                'days'    => (int) config('biometric.sync_days'),
            ]);
        } catch (ConnectionException $e) {
            Log::error('Biometric API connection failed', ['machine' => $machine, 'error' => $e->getMessage()]);

            return [
                'success' => false,
                'records' => [],
                'message' => 'Could not reach the biometric API: ' . $e->getMessage(),
            ];
        }

        if ($response->failed()) {
            Log::warning('Biometric API returned an error response', [
                'machine' => $machine,
                'status'  => $response->status(),
                'body'    => $response->body(),
            ]);

            return [
                'success' => false,
                'records' => [],
                'message' => 'Biometric API returned HTTP ' . $response->status(),
            ];
        }

        $payload = $response->json();

        if (!is_array($payload) || ($payload['success'] ?? false) !== true) {
            Log::warning('Biometric API returned an unexpected payload', [
                'machine' => $machine,
                'body'    => $response->body(),
            ]);

            return [
                'success' => false,
                'records' => [],
                'message' => $payload['message'] ?? 'Biometric API returned an unexpected response.',
            ];
        }

        return [
            'success' => true,
            'records' => $payload['data'] ?? [],
        ];
    }
}
