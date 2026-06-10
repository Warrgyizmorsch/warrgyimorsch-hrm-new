<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('technical_reviews', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('employee_id');

            $table->string('month');
            $table->string('period');

            $table->decimal('self_total', 8, 2)->default(0);
            $table->decimal('author_total', 8, 2)->default(0);
            $table->decimal('admin_total', 8, 2)->default(0);

            $table->timestamps();

            $table->foreign('employee_id')
                ->references('id')
                ->on('employees')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('technical_reviews');
    }
};