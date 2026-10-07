<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Self-service onboarding: HR creates the account with job details, the employee fills
        // personal/ID/bank details, HR approves. NULL = fully active (every existing employee).
        Schema::table('employees', function (Blueprint $table) {
            $table->string('profile_status', 20)->nullable()->after('employment_status');
        });

        // Details an employee submitted for HR approval — the first-time profile, or a later
        // change (e.g. new bank account). Nothing is applied to the employee until approved.
        Schema::create('employee_profile_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('type', 20);            // onboarding | change
            $table->json('data');
            $table->string('status', 20)->default('pending'); // pending | approved | returned
            $table->text('review_note')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_profile_requests');
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('profile_status');
        });
    }
};
