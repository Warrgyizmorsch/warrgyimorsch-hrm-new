<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('employees', 'department_id')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->foreignId('department_id')->nullable()->after('department')
                    ->constrained('departments')->nullOnDelete();
            });
        }

        if (!Schema::hasTable('department_employee_led')) {
            Schema::create('department_employee_led', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
                $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['employee_id', 'department_id']);
            });
        }

        if (!Schema::hasTable('department_project')) {
            Schema::create('department_project', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
                $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['project_id', 'department_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('department_project');
        Schema::dropIfExists('department_employee_led');

        if (Schema::hasColumn('employees', 'department_id')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropConstrainedForeignId('department_id');
            });
        }
    }
};
