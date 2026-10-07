<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // An SOP can now be an uploaded document (PDF/Word/etc.) instead of, or alongside,
        // written content — so content becomes optional.
        Schema::table('sops', function (Blueprint $table) {
            $table->longText('content')->nullable()->change();
            $table->string('attachment_path')->nullable()->after('content');
            $table->string('attachment_name')->nullable()->after('attachment_path');
        });

        // Each version keeps the file it had, so history can still open superseded documents.
        Schema::table('sop_versions', function (Blueprint $table) {
            $table->longText('content')->nullable()->change();
            $table->string('attachment_path')->nullable()->after('content');
            $table->string('attachment_name')->nullable()->after('attachment_path');
        });
    }

    public function down(): void
    {
        foreach (['sop_versions', 'sops'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn(['attachment_path', 'attachment_name']);
            });
        }
    }
};
