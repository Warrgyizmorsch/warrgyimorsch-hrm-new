<?php

namespace App\Console\Commands;

use App\Models\PushSubscription;
use App\Models\User;
use App\Services\WebPush\WebPushService;
use Illuminate\Console\Command;

class SendTestPush extends Command
{
    protected $signature = 'webpush:test {email : Login e-mail of the user to notify}';

    protected $description = 'Send a test browser push notification to every device of one user';

    public function handle(WebPushService $push): int
    {
        if (!$push->isConfigured()) {
            $this->error('Web push is not ready: run "php artisan migrate", then "php artisan webpush:vapid" and put the keys in .env (then php artisan config:clear).');

            return self::FAILURE;
        }

        $user = User::where('email', $this->argument('email'))->first();
        if (!$user) {
            $this->error('No user with that login e-mail.');

            return self::FAILURE;
        }

        $devices = PushSubscription::where('user_id', $user->id)->count();
        if (!$devices) {
            $this->warn("{$user->name} has not turned on desktop alerts yet. Log in as them, open the bell and click \"Desktop off\" → Allow.");

            return self::FAILURE;
        }

        \Illuminate\Support\Facades\DB::table('push_messages')->insert([
            'user_id' => $user->id,
            'title' => 'Test notification from ' . config('leave.mail_brand', config('app.name')),
            'body' => 'Push notifications are working on this device. ' . now()->format('d M Y, h:i A'),
            'url' => url('/dashboard'),
            'tag' => 'hrm-test',
            'created_at' => now(),
        ]);

        $result = $push->wake([$user->id]);
        $this->info("Sent to {$result['sent']} of {$devices} device(s)" . ($result['removed'] ? ", removed {$result['removed']} expired" : '') . '.');
        foreach ($result['errors'] as $error) {
            $this->error($error);
        }

        return $result['failed'] ? self::FAILURE : self::SUCCESS;
    }
}
