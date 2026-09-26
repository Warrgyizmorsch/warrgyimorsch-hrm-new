<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kra_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('month', 7); // Y-m — the target period
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'month']);
        });

        Schema::create('kra_assignment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kra_assignment_id')->constrained('kra_assignments')->cascadeOnDelete();
            $table->foreignId('technical_review_evaluation_id')->nullable()->constrained('technical_review_evaluations')->nullOnDelete();
            $table->string('criteria_name');
            $table->decimal('max_point', 8, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kra_assignment_items');
        Schema::dropIfExists('kra_assignments');
    }
};
