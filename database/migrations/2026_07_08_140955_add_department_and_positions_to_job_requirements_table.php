<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('job_requirements', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('role_id')
                ->constrained('departments')->nullOnDelete();
            $table->unsignedInteger('positions_count')->default(1)->after('minimum_experience');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_requirements', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropColumn(['department_id', 'positions_count']);
        });
    }
};
