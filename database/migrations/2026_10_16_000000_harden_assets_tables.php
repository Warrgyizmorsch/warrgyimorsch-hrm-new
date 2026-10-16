<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Asset module hardening:
 *  - assets: soft deletes, so deleting a device keeps its allocation history.
 *  - asset_requests.user_id: RESTRICT instead of CASCADE, so deleting a login can no longer
 *    silently erase asset history. (EmployeePurgeService removes these rows itself first.)
 *  - index on (asset_id, status) for the "current holder" lookups.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('asset_requests', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
            $table->index(['asset_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('asset_requests', function (Blueprint $table) {
            $table->dropIndex(['asset_id', 'status']);
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('assets', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
