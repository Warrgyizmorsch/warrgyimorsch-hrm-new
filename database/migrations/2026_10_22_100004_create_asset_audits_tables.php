<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Physical audits: a snapshot of every asset (and who should have it) at the start,
 * then each one is marked found / missing / damaged / wrong holder.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_audits', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('status', 20)->default('in_progress'); // in_progress | completed | cancelled
            $table->text('notes')->nullable();
            $table->foreignId('started_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('asset_audit_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_audit_id')->constrained('asset_audits')->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->foreignId('expected_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('expected_user_name')->nullable();
            $table->string('expected_status', 20)->nullable();
            $table->string('result', 20)->default('pending'); // pending | found | missing | damaged | wrong_holder
            $table->string('notes', 500)->nullable();
            $table->foreignId('checked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('checked_at')->nullable();
            $table->foreignId('asset_incident_id')->nullable()->constrained('asset_incidents')->nullOnDelete();
            $table->timestamps();

            $table->unique(['asset_audit_id', 'asset_id']);
            $table->index(['asset_audit_id', 'result']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_audit_items');
        Schema::dropIfExists('asset_audits');
    }
};
