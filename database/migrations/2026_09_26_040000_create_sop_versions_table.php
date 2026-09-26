<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Snapshot of a SOP's content each time it's superseded by an edit — the full
        // history of what it used to say, not just a version number.
        Schema::create('sop_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sop_id')->constrained('sops')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('title');
            $table->longText('content');
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['sop_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sop_versions');
    }
};
