<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Two-level leave approval: Level 1 = employee's Team Leader, Level 2 = Manager/HR (admin roles).
 * `status` stays the final outcome used everywhere else (attendance, balances, reports).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_applications', function (Blueprint $table) {
            // tl | manager | done
            $table->string('approval_stage', 20)->nullable()->after('status');

            // pending | approved | rejected | skipped (no TL for this employee) | bypassed (manager acted first)
            $table->string('tl_status', 20)->nullable()->after('approval_stage');
            $table->unsignedBigInteger('tl_action_by')->nullable()->after('tl_status'); // users.id
            $table->timestamp('tl_action_at')->nullable()->after('tl_action_by');
            $table->text('tl_remarks')->nullable()->after('tl_action_at');

            $table->unsignedBigInteger('manager_action_by')->nullable()->after('tl_remarks'); // users.id
            $table->timestamp('manager_action_at')->nullable()->after('manager_action_by');
            $table->text('manager_remarks')->nullable()->after('manager_action_at');

            $table->index(['status', 'approval_stage']);
        });

        // Applications decided before this change are complete; open ones continue at the manager
        // level so nothing already waiting on a manager gets sent back to a TL.
        DB::table('leave_applications')->where('status', 'pending')
            ->update(['approval_stage' => 'manager', 'tl_status' => 'skipped']);
        DB::table('leave_applications')->where('status', '!=', 'pending')
            ->update(['approval_stage' => 'done']);
    }

    public function down(): void
    {
        Schema::table('leave_applications', function (Blueprint $table) {
            $table->dropIndex(['status', 'approval_stage']);
            $table->dropColumn([
                'approval_stage', 'tl_status', 'tl_action_by', 'tl_action_at', 'tl_remarks',
                'manager_action_by', 'manager_action_at', 'manager_remarks',
            ]);
        });
    }
};
