<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // `timestamp` was created without an explicit DEFAULT/ON UPDATE clause, so MySQL/MariaDB's
        // legacy behavior for the first TIMESTAMP column in a table silently applied
        // `DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP`. That meant every UPDATE that
        // touched an existing punch row (e.g. re-syncing an already-seen device record) silently
        // overwrote the real punch time with "now" — the actual source of the corrupted/duplicated
        // timestamps found in this table. Keep an explicit DEFAULT for convenience but drop ON UPDATE.
        DB::statement('ALTER TABLE attendance_logs MODIFY `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE attendance_logs MODIFY `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
    }
};
