<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kpis', function (Blueprint $table) {
            // Key identifying which built-in computation auto-fills this KPI's actual value
            // (e.g. 'tasks_completed_on_time'). Null = manually entered, as before.
            $table->string('auto_metric', 50)->nullable()->after('weightage');
        });

        Schema::table('kpi_assignment_items', function (Blueprint $table) {
            // Snapshot of the metric key at assignment time, same reasoning as the other
            // snapshotted fields — so a later edit to the master KPI doesn't retroactively
            // change how an already-assigned item gets recomputed.
            $table->string('auto_metric', 50)->nullable()->after('weightage');
        });
    }

    public function down(): void
    {
        Schema::table('kpis', function (Blueprint $table) {
            $table->dropColumn('auto_metric');
        });

        Schema::table('kpi_assignment_items', function (Blueprint $table) {
            $table->dropColumn('auto_metric');
        });
    }
};
