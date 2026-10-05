<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Guarded so a re-run after a partial failure (MySQL DDL isn't transactional) succeeds.
        if (!Schema::hasColumn('employees', 'employment_status')) {
            Schema::table('employees', function (Blueprint $table) {
                // working | probation | notice_period | pip
                $table->string('employment_status', 20)->default('working')->after('working_mode');
            });
        }

        // A previous failed run may have left an empty app_settings without its unique index.
        if (Schema::hasTable('app_settings') && \DB::table('app_settings')->count() === 0) {
            Schema::drop('app_settings');
        }

        if (!Schema::hasTable('app_settings')) {
            Schema::create('app_settings', function (Blueprint $table) {
                $table->id();
                // 191 chars keeps the unique index under the 1000-byte MyISAM/utf8mb4 limit.
                $table->string('key', 191)->unique();
                $table->text('value')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');

        if (Schema::hasColumn('employees', 'employment_status')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropColumn('employment_status');
            });
        }
    }
};
