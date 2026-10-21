<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Salary increment history. An increment is recorded with an effective date and amount; once that
 * date arrives it is applied to the employee's salary components (see IncrementService), keeping
 * the before/after split so payroll for earlier months still uses the old salary.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_increments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('effective_date');
            $table->decimal('previous_gross', 12, 2);
            $table->decimal('increment_amount', 12, 2);
            $table->decimal('new_gross', 12, 2);
            $table->decimal('increment_percent', 7, 2);
            $table->json('previous_components')->nullable();
            $table->json('new_components')->nullable();
            $table->string('remarks', 500)->nullable();
            $table->timestamp('applied_at')->nullable(); // null = waiting for effective_date
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['employee_id', 'effective_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_increments');
    }
};
