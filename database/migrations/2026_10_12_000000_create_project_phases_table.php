<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Safe to re-run after a half-finished attempt (table / column may already exist).
        if (!Schema::hasTable('project_phases')) {
            Schema::create('project_phases', function (Blueprint $table) {
                $table->engine = 'InnoDB';
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
        }

        // Foreign keys need InnoDB on both tables; older servers created daily_tasks as MyISAM.
        if ($this->engine('daily_tasks') !== 'innodb') {
            DB::statement('ALTER TABLE `daily_tasks` ENGINE=InnoDB');
        }

        if (!Schema::hasColumn('daily_tasks', 'phase_id')) {
            Schema::table('daily_tasks', function (Blueprint $table) {
                $table->unsignedBigInteger('phase_id')->nullable()->after('project_id');
            });
        }

        if (!$this->hasForeignKey('daily_tasks', 'daily_tasks_phase_id_foreign')) {
            // Clear any value that does not point at a real phase, then add the key.
            DB::statement('UPDATE `daily_tasks` SET `phase_id` = NULL WHERE `phase_id` IS NOT NULL AND `phase_id` NOT IN (SELECT `id` FROM `project_phases`)');

            Schema::table('daily_tasks', function (Blueprint $table) {
                // Deleting a phase moves its tasks back to the project backlog instead of deleting them.
                $table->foreign('phase_id')->references('id')->on('project_phases')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('daily_tasks', 'phase_id')) {
            Schema::table('daily_tasks', function (Blueprint $table) {
                if ($this->hasForeignKey('daily_tasks', 'daily_tasks_phase_id_foreign')) {
                    $table->dropForeign('daily_tasks_phase_id_foreign');
                }
                $table->dropColumn('phase_id');
            });
        }

        Schema::dropIfExists('project_phases');
    }

    private function engine(string $table): string
    {
        $row = DB::selectOne(
            'SELECT ENGINE AS engine FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
            [$table]
        );

        return strtolower((string) ($row->engine ?? ''));
    }

    private function hasForeignKey(string $table, string $name): bool
    {
        return (bool) DB::selectOne(
            "SELECT 1 FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = 'FOREIGN KEY'",
            [$table, $name]
        );
    }
};
