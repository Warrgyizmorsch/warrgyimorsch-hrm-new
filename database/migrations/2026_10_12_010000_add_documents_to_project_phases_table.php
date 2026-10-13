<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_phases', function (Blueprint $table) {
            // Rich-text (HTML) description can be long.
            $table->longText('description')->nullable()->change();
            // [{path, name, size}] — files on the public disk under project-phase-documents/
            $table->json('documents')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('project_phases', function (Blueprint $table) {
            $table->dropColumn('documents');
            $table->text('description')->nullable()->change();
        });
    }
};
