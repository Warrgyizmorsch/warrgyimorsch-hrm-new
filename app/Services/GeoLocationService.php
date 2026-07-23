<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeoLocationService
{
    /**
     * Resolve city/region/country for an IP address.
     *
     * @return array{city: ?string, region: ?string, country: ?string}
     */
    public function resolve(?string $ip): array
    {
        $empty = ['city' => null, 'region' => null, 'country' => null];

        if (blank($ip) || $this->isPrivate($ip)) {
            return ['city' => 'Local Network', 'region' => null, 'country' => null];
        }

        return Cache::remember("geoip:{$ip}", now()->addHours(12), function () use ($ip, $empty) {
            try {
                $response = Http::timeout(3)->get("http://ip-api.com/json/{$ip}", [
                    'fields' => 'status,country,regionName,city',
                ]);

                if (!$response->ok() || $response->json('status') !== 'success') {
                    return $empty;
                }

                return [
                    'city' => $response->json('city'),
                    'region' => $response->json('regionName'),
                    'country' => $response->json('country'),
                ];
            } catch (\Throwable $e) {
                Log::warning('GeoLocationService lookup failed', ['ip' => $ip, 'error' => $e->getMessage()]);

                return $empty;
            }
        });
    }

    private function isPrivate(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false;
    }
}
