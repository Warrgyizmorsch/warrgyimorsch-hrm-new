<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('asset_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('asset_type');
            $table->text('reason')->nullable();
            $table->enum('status', ['Pending', 'Approved', 'Rejected', 'Allocated', 'Returned'])->default('Pending');
            $table->foreignId('asset_id')->nullable()->constrained('assets')->nullOnDelete();
            $table->timestamp('allocated_at')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_requests');
    }
};
