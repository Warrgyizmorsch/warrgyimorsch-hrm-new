<?php

namespace App\Console\Commands;

use App\Models\LoginActivity;
use Illuminate\Console\Command;

class CloseStaleLoginSessions extends Command
{
    protected $signature = 'login-activity:close-stale';

    protected $description = 'Close login-activity sessions whose heartbeat has gone silent (tab/browser closed without logout)';

    public function handle(): int
    {
        $cutoff = now()->subMinutes(6);

        $stale = LoginActivity::where('is_active', true)
            ->where(function ($q) use ($cutoff) {
                $q->where('last_seen_at', '<', $cutoff)
                    ->orWhereNull('last_seen_at');
            })
            ->get();

        foreach ($stale as $activity) {
            $activity->closeSession($activity->last_seen_at ?? $activity->login_at);
        }

        $this->info("Closed {$stale->count()} stale login session(s).");

        return Command::SUCCESS;
    }
}
