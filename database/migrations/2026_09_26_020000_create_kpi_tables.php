<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // KPI master/template — same shape as technical_review_evaluations (department-scoped criteria).
        Schema::create('kpis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('title');
            $table->string('unit', 30)->nullable(); // e.g. %, count, ₹
            $table->decimal('target_value', 12, 2);
            $table->decimal('weightage', 5, 2)->default(0);
            $table->integer('sort_order')->default(0);
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('kpi_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('month', 7); // Y-m
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'month']);
        });

        Schema::create('kpi_assignment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kpi_assignment_id')->constrained('kpi_assignments')->cascadeOnDelete();
            $table->foreignId('kpi_id')->nullable()->constrained('kpis')->nullOnDelete();
            $table->string('title'); // snapshot
            $table->string('unit', 30)->nullable(); // snapshot
            $table->decimal('target_value', 12, 2); // snapshot
            $table->decimal('weightage', 5, 2)->default(0); // snapshot
            $table->decimal('actual_value', 12, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_assignment_items');
        Schema::dropIfExists('kpi_assignments');
        Schema::dropIfExists('kpis');
    }
};
