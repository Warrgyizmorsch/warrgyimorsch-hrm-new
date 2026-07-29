<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suggestions', function (Blueprint $table) {
            $table->string('status')->default('Open')->after('message');
            $table->text('manager_reply')->nullable()->after('status');
            $table->boolean('appreciated')->default(false)->after('manager_reply');
            $table->foreignId('replied_by')->nullable()->after('appreciated')->constrained('users')->nullOnDelete();
            $table->timestamp('replied_at')->nullable()->after('replied_by');
        });
    }

    public function down(): void
    {
        Schema::table('suggestions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('replied_by');
            $table->dropColumn(['status', 'manager_reply', 'appreciated', 'replied_at']);
        });
    }
};
