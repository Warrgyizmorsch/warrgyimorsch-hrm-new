<?php

namespace App\Services\WebPush;

use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Sends browser push notifications.
 *
 * Each push is an empty "wake-up" signed with VAPID; the service worker (public/sw.js) then
 * fetches the real messages from /push/pending using the user's session. This keeps message
 * text off third-party push servers and avoids payload encryption altogether.
 */
class WebPushService
{
    public function isConfigured(): bool
    {
        return (bool) config('webpush.enabled', true)
            && config('webpush.public_key')
            && config('webpush.private_key')
            && $this->tablesExist();
    }

    private function tablesExist(): bool
    {
        static $exists = null;

        return $exists ??= Schema::hasTable('push_subscriptions') && Schema::hasTable('push_messages');
    }

    /** Queue a message for these users and wake their devices (sent after the HTTP response). */
    public function notifyUsers(iterable $users, string $title, ?string $body = null, ?string $url = null, ?string $tag = null): void
    {
        if (!$this->isConfigured()) {
            return;
        }

        $userIds = collect($users)->map(fn ($u) => $u instanceof User ? $u->id : (int) $u)->filter()->unique()->values();
        if ($userIds->isEmpty()) {
            return;
        }

        $now = now();
        DB::table('push_messages')->insert($userIds->map(fn ($id) => [
            'user_id' => $id,
            'title' => Str::limit($title, 250, '…'),
            'body' => $body ? Str::limit($body, 480, '…') : null,
            'url' => $url ? Str::limit($url, 500, '') : null,
            'tag' => $tag ? Str::limit($tag, 100, '') : null,
            'created_at' => $now,
        ])->all());

        $ids = $userIds->all();
        app()->terminating(fn () => $this->wake($ids));
    }

    /** Convenience: notify whoever logs in with this e-mail address. */
    public function notifyEmail(string $email, string $title, ?string $body = null, ?string $url = null, ?string $tag = null): void
    {
        $user = User::where('email', $email)->where('account_status', 'active')->first();
        if ($user) {
            $this->notifyUsers([$user], $title, $body, $url, $tag);
        }
    }

    /**
     * Send the wake-up push to every device of these users.
     *
     * @return array{sent: int, failed: int, removed: int, errors: string[]}
     */
    public function wake(array $userIds): array
    {
        $result = ['sent' => 0, 'failed' => 0, 'removed' => 0, 'errors' => []];
        if (!$this->isConfigured()) {
            $result['errors'][] = 'Web push is not configured (VAPID keys missing or migration not run).';

            return $result;
        }

        $public = config('webpush.public_key');
        $private = config('webpush.private_key');
        $subject = config('webpush.subject');
        $ttl = (int) config('webpush.ttl', 86400);

        foreach (PushSubscription::whereIn('user_id', $userIds)->get() as $sub) {
            try {
                $response = Http::timeout((int) config('webpush.timeout', 10))
                    ->withHeaders([
                        'Authorization' => Vapid::authorizationHeader($sub->endpoint, $public, $private, $subject),
                        'TTL' => (string) $ttl,
                        'Urgency' => 'high',
                    ])
                    ->withBody('', 'application/octet-stream')
                    ->post($sub->endpoint);

                if ($response->successful()) {
                    $sub->forceFill(['last_used_at' => now()])->save();
                    $result['sent']++;
                } elseif (in_array($response->status(), [404, 410], true)) {
                    // The browser dropped this subscription (cleared data, uninstalled) — forget it.
                    $sub->delete();
                    $result['removed']++;
                } else {
                    $result['failed']++;
                    $result['errors'][] = 'HTTP ' . $response->status() . ' from ' . parse_url($sub->endpoint, PHP_URL_HOST) . ': ' . Str::limit($response->body(), 200);
                }
            } catch (\Throwable $e) {
                $result['failed']++;
                $result['errors'][] = $e->getMessage();
            }
        }

        if ($result['failed']) {
            Log::warning('Web push delivery problems', $result);
        }

        return $result;
    }
}
