<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Timeline of every step on a leave application: applied, TL decision, every manager
 * status change — with who did it, when, and their remarks. Never updated, only appended.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_application_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_application_id')->constrained('leave_applications')->cascadeOnDelete();
            // applied | tl_approved | tl_rejected | manager_update
            $table->string('action', 30);
            $table->string('level', 20)->nullable(); // employee | tl | manager
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30)->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('actor_user_id')->nullable(); // users.id
            $table->string('actor_name')->nullable(); // kept even if the user is removed later
            $table->timestamp('created_at')->useCurrent();

            $table->index(['leave_application_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_application_logs');
    }
};
