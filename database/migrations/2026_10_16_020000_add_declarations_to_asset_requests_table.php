<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Asset declarations: an employee (or their team leader on their behalf) records a company
 * device they already hold but that was never entered. It stays Pending until a team leader
 * or HR/Admin verifies it, which creates/links the asset and the allocation.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asset_requests', function (Blueprint $table) {
            $table->string('source', 20)->default('request')->after('user_id'); // request | declaration
            $table->string('declared_name')->nullable()->after('reason');
            $table->string('declared_serial_number')->nullable()->after('declared_name');
            $table->text('declared_configuration')->nullable()->after('declared_serial_number');
            $table->string('declared_image_path')->nullable()->after('declared_configuration');
            $table->foreignId('created_by')->nullable()->after('declared_image_path')->constrained('users')->nullOnDelete();
            $table->foreignId('verified_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable()->after('verified_by');
            $table->string('rejection_reason')->nullable()->after('verified_at');

            $table->index(['source', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('asset_requests', function (Blueprint $table) {
            $table->dropIndex(['source', 'status']);
            $table->dropConstrainedForeignId('created_by');
            $table->dropConstrainedForeignId('verified_by');
            $table->dropColumn([
                'source', 'declared_name', 'declared_serial_number', 'declared_configuration',
                'declared_image_path', 'verified_at', 'rejection_reason',
            ]);
        });
    }
};
