<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('technical_review_details', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('review_id');

            $table->string('criteria_name');

            $table->decimal('criteria_point', 8, 2)->default(0);

            $table->decimal('self_review', 8, 2)->default(0);
            $table->decimal('author_review', 8, 2)->default(0);
            $table->decimal('admin_review', 8, 2)->default(0);

            $table->timestamps();

            $table->foreign('review_id')
                ->references('id')
                ->on('technical_reviews')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('technical_review_details');
    }
};
