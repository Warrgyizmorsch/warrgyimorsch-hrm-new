<?php

namespace App\Console\Commands;

use App\Services\IncrementService;
use Illuminate\Console\Command;

class ApplyDueIncrements extends Command
{
    protected $signature = 'increments:apply-due';

    protected $description = 'Apply salary increments whose effective date has arrived';

    public function handle(IncrementService $increments): int
    {
        $this->info($increments->applyDue() . ' increment(s) applied.');

        return self::SUCCESS;
    }
}
