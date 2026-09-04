<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('employees', 'department')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropColumn('department');
            });
        }

        if (Schema::hasColumn('employees', 'additional_led_departments')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropColumn('additional_led_departments');
            });
        }

        if (Schema::hasColumn('projects', 'department')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->dropColumn('department');
            });
        }

        if (Schema::hasColumn('broadcasts', 'department')) {
            Schema::table('broadcasts', function (Blueprint $table) {
                $table->dropColumn('department');
            });
        }

        if (Schema::hasColumn('technical_review_evaluations', 'department')) {
            Schema::table('technical_review_evaluations', function (Blueprint $table) {
                $table->dropColumn('department');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('employees', 'department')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->string('department')->nullable()->after('rs9n_device_id');
            });
        }

        if (!Schema::hasColumn('employees', 'additional_led_departments')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->text('additional_led_departments')->nullable()->after('department');
            });
        }

        if (!Schema::hasColumn('projects', 'department')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->text('department')->nullable();
            });
        }

        if (!Schema::hasColumn('broadcasts', 'department')) {
            Schema::table('broadcasts', function (Blueprint $table) {
                $table->string('department')->nullable();
            });
        }

        if (!Schema::hasColumn('technical_review_evaluations', 'department')) {
            Schema::table('technical_review_evaluations', function (Blueprint $table) {
                $table->string('department')->nullable();
            });
        }
    }
};
