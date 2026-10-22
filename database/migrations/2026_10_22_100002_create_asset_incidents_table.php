<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Damage and loss cases. One row per incident, with the cost to the company and how much
 * is being recovered from the employee (recorded only - nothing is pushed into payroll).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->foreignId('asset_request_id')->nullable()->constrained('asset_requests')->nullOnDelete();
            // Holder at the time of the incident. Name kept as text so it survives an employee purge.
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_name')->nullable();
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('type', 20);                    // damage | lost
            $table->date('incident_date');
            $table->text('description');
            $table->string('attachment_path')->nullable(); // photo / police report

            $table->string('status', 20)->default('open'); // open | in_repair | closed
            $table->string('resolution', 30)->nullable();  // repaired | no_action | found | written_off

            $table->decimal('cost', 12, 2)->nullable();               // repair or replacement cost
            $table->decimal('recovery_amount', 12, 2)->default(0);    // to be recovered from employee
            $table->string('recovery_status', 20)->default('none');   // none | pending | recovered | waived
            $table->decimal('recovered_amount', 12, 2)->default(0);
            $table->date('recovered_at')->nullable();

            $table->text('resolution_notes')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'type']);
            $table->index('recovery_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_incidents');
    }
};
