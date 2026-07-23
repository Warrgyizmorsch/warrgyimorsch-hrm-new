<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('session_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('city')->nullable();
            $table->string('region')->nullable();
            $table->string('country')->nullable();
            // Explicit DEFAULT CURRENT_TIMESTAMP (no ON UPDATE) — without it, MySQL's legacy
            // "first TIMESTAMP column" behavior silently attaches ON UPDATE CURRENT_TIMESTAMP,
            // which would overwrite login_at on every heartbeat save. See the equivalent fix in
            // 2026_07_09_114557_remove_auto_update_from_attendance_logs_timestamp.php.
            $table->timestamp('login_at')->useCurrent();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('logout_at')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['employee_id', 'login_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_activities');
    }
};
