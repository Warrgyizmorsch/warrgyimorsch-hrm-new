<?php

namespace App\Console\Commands;

use App\Services\WebPush\Vapid;
use Illuminate\Console\Command;

class GenerateVapidKeys extends Command
{
    protected $signature = 'webpush:vapid {--force : Show new keys even if keys are already set}';

    protected $description = 'Generate the VAPID key pair for browser push notifications';

    public function handle(): int
    {
        if (config('webpush.public_key') && !$this->option('force')) {
            $this->warn('VAPID keys are already set in .env. Changing them makes every browser re-subscribe.');
            $this->line('Run with --force if you really want new keys.');

            return self::SUCCESS;
        }

        // Windows/XAMPP PHP often needs an explicit openssl.cnf to create keys.
        $config = null;
        foreach ([
            getenv('OPENSSL_CONF') ?: null,
            'C:/xampp/php/extras/ssl/openssl.cnf',
            'C:/xampp/apache/conf/openssl.cnf',
            'C:/xampp/apache/bin/openssl.cnf',
            dirname(PHP_BINARY) . '/extras/ssl/openssl.cnf',
        ] as $candidate) {
            if ($candidate && is_file($candidate)) {
                $config = $candidate;
                break;
            }
        }

        try {
            $keys = Vapid::createKeys($config);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            $this->line('On Windows, set OPENSSL_CONF to the path of openssl.cnf (e.g. C:\\xampp\\php\\extras\\ssl\\openssl.cnf) and try again.');

            return self::FAILURE;
        }

        $this->info('Add these lines to your .env, then run: php artisan config:clear');
        $this->newLine();
        $this->line('VAPID_PUBLIC_KEY=' . $keys['publicKey']);
        $this->line('VAPID_PRIVATE_KEY=' . $keys['privateKey']);
        $this->line('VAPID_SUBJECT=mailto:' . (config('mail.from.address') ?: 'admin@example.com'));
        $this->newLine();
        $this->comment('Keep VAPID_PRIVATE_KEY secret, and use the same keys on every server of this app.');

        return self::SUCCESS;
    }
}
