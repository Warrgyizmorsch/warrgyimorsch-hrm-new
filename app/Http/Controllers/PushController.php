<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Browser push: register devices and hand pending messages to the service worker. */
class PushController extends Controller
{
    public function key()
    {
        return response()->json([
            'enabled' => (bool) (config('webpush.enabled', true) && config('webpush.public_key') && config('webpush.private_key')),
            'publicKey' => config('webpush.public_key'),
        ]);
    }

    public function subscribe(Request $request)
    {
        $data = $request->validate([
            'endpoint' => 'required|url|max:2000|starts_with:https://',
            'keys.p256dh' => 'nullable|string|max:255',
            'keys.auth' => 'nullable|string|max:255',
        ]);

        PushSubscription::updateOrCreate(
            ['endpoint_hash' => hash('sha256', $data['endpoint'])],
            [
                'user_id' => $request->user()->id, // a shared PC: the device follows whoever is logged in
                'endpoint' => $data['endpoint'],
                'public_key' => $data['keys']['p256dh'] ?? null,
                'auth_token' => $data['keys']['auth'] ?? null,
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
            ]
        );

        return response()->json(['success' => true]);
    }

    public function unsubscribe(Request $request)
    {
        $endpoint = (string) $request->input('endpoint');
        if ($endpoint !== '') {
            PushSubscription::where('endpoint_hash', hash('sha256', $endpoint))
                ->where('user_id', $request->user()->id)
                ->delete();
        }

        return response()->json(['success' => true]);
    }

    /** Called by the service worker when a push arrives: returns and marks this user's new messages. */
    public function pending(Request $request)
    {
        $userId = $request->user()->id;

        $messages = DB::table('push_messages')
            ->where('user_id', $userId)
            ->whereNull('delivered_at')
            ->where('created_at', '>=', now()->subDays(2))
            ->orderBy('id')
            ->limit(10)
            ->get(['id', 'title', 'body', 'url', 'tag', 'created_at']);

        if ($messages->isNotEmpty()) {
            DB::table('push_messages')->whereIn('id', $messages->pluck('id'))->update(['delivered_at' => now()]);
        }

        return response()->json(['messages' => $messages])->header('Cache-Control', 'no-store');
    }
}
