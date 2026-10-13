<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Project-wide guard against double submissions (double clicks, repeated Enter, two tabs,
 * slow networks). For logged-in users, an identical write request — same user, same URL,
 * same data — is refused:
 *   - while the first one is still running, and
 *   - for a few seconds after it succeeded.
 * A request that failed (validation error, server error) can be retried immediately.
 *
 * The browser-side guard (public/assets/js/form-submit-guard.js) stops most repeats before
 * they leave the page; this is the safety net that also covers two tabs and scripted clicks.
 */
class PreventDuplicateSubmissions
{
    /** Seconds an identical successful request stays blocked. */
    private const REPEAT_WINDOW = 5;

    /** Upper bound for "still running" (long syncs, imports). */
    private const IN_FLIGHT_TTL = 300;

    /** Paths that legitimately repeat the same payload. */
    private const EXCEPT = [
        'login',
        'logout',
        'login-activity/heartbeat',
        'notifications/*/read',
        'broadcasting/*',
        'push/*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (
            in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)
            || !$request->user()
            || $request->is(...self::EXCEPT)
        ) {
            return $next($request);
        }

        $key = 'dup-submit:' . $this->fingerprint($request);

        if (!Cache::add($key, 'running', self::IN_FLIGHT_TTL)) {
            return $this->duplicateResponse($request, Cache::get($key) === 'running');
        }

        try {
            $response = $next($request);
        } catch (\Throwable $e) {
            Cache::forget($key);
            throw $e;
        }

        if ($response->getStatusCode() >= 400) {
            Cache::forget($key); // let the user correct and resubmit straight away
        } else {
            Cache::put($key, 'done', self::REPEAT_WINDOW);
        }

        return $response;
    }

    private function fingerprint(Request $request): string
    {
        $data = $request->except(['_token', '_method']);
        $this->sortRecursive($data);

        $files = collect($request->allFiles())
            ->flatten()
            ->filter(fn ($f) => $f instanceof UploadedFile)
            ->map(fn (UploadedFile $f) => $f->getClientOriginalName() . ':' . $f->getSize())
            ->sort()
            ->values()
            ->all();

        return sha1(implode('|', [
            $request->user()->getAuthIdentifier(),
            $request->method(),
            $request->path(),
            json_encode($data),
            implode(',', $files),
        ]));
    }

    private function sortRecursive(array &$data): void
    {
        ksort($data);
        foreach ($data as &$value) {
            if (is_array($value)) {
                $this->sortRecursive($value);
            }
        }
    }

    private function duplicateResponse(Request $request, bool $stillRunning): Response
    {
        $message = $stillRunning
            ? 'This request is already being processed. Please wait — it will not be submitted twice.'
            : 'This was already submitted a moment ago, so the repeat was ignored.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'duplicate' => true,
                'message' => $message,
            ], 409);
        }

        return redirect()->back()->withInput()->with('warning', $message);
    }
}
