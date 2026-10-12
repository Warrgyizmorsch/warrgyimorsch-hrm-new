<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_phases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
            // Planned | In Progress | Completed | On Hold
            $table->string('status', 30)->default('Planned');
            $table->unsignedBigInteger('owner_id')->nullable(); // employees.id
            $table->integer('sort_order')->default(0);
            $table->unsignedBigInteger('created_by')->nullable(); // users.id
            $table->timestamps();

            $table->index(['project_id', 'sort_order']);
        });

        Schema::table('daily_tasks', function (Blueprint $table) {
            // Deleting a phase moves its tasks back to the project backlog instead of deleting them.
            $table->foreignId('phase_id')->nullable()->after('project_id')
                ->constrained('project_phases')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('daily_tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('phase_id');
        });

        Schema::dropIfExists('project_phases');
    }
};
