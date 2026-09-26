<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sops', function (Blueprint $table) {
            $table->id();
            // NULL department_id = applies company-wide (same convention as broadcasts.department_id).
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            // NULL role = applies to every role. Free-text to match employees.role (no role FK exists).
            $table->string('role')->nullable();
            $table->string('title');
            $table->longText('content');
            $table->unsignedInteger('version')->default(1);
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        // Acknowledgement pattern copied from broadcast_user/read_at — one row per (sop, user, version)
        // so re-acknowledging a bumped version is tracked separately from the prior one.
        Schema::create('sop_acknowledgements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sop_id')->constrained('sops')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->timestamp('acknowledged_at');
            $table->timestamps();

            $table->unique(['sop_id', 'user_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sop_acknowledgements');
        Schema::dropIfExists('sops');
    }
};
