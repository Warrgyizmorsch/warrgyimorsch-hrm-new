<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('attendance_logs', 'punch')) {
            Schema::table('attendance_logs', function (Blueprint $table) {
                $table->string('punch')->nullable()->after('user_id');
            });
        }

        // Drop leftover duplicate (user_id, timestamp) rows before the unique index is
        // added. These could only have been created by the old device_uid-based upsert
        // key, since pyzk's uid is a positional index (not a stable per-punch id) and
        // could re-insert the same real punch as a new row on a later sync.
        DB::table('attendance_logs')
            ->select('user_id', 'timestamp', DB::raw('MIN(id) as keep_id'))
            ->groupBy('user_id', 'timestamp')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->each(function ($group) {
                DB::table('attendance_logs')
                    ->where('user_id', $group->user_id)
                    ->where('timestamp', $group->timestamp)
                    ->where('id', '!=', $group->keep_id)
                    ->delete();
            });

        Schema::table('attendance_logs', function (Blueprint $table) {
            $table->unique(['user_id', 'timestamp'], 'attendance_logs_user_timestamp_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_logs', function (Blueprint $table) {
            $table->dropUnique('attendance_logs_user_timestamp_unique');
        });
    }
};
