<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Handover details on each allocation row:
 *  - who handed it over / took it back, and the condition each way
 *  - employee acknowledgement (pending -> accepted / disputed). NULL = older rows, treated as accepted.
 * status becomes a plain string so an allocation can also end as 'Lost'.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asset_requests', function (Blueprint $table) {
            $table->string('status', 20)->default('Pending')->change();

            $table->foreignId('allocated_by')->nullable()->after('allocated_at')->constrained('users')->nullOnDelete();
            $table->string('condition_on_allocation', 20)->nullable()->after('allocated_by');
            $table->string('acknowledgement_status', 20)->nullable()->after('condition_on_allocation');
            $table->timestamp('acknowledged_at')->nullable()->after('acknowledgement_status');
            $table->string('acknowledgement_note', 500)->nullable()->after('acknowledged_at');
            $table->foreignId('returned_by')->nullable()->after('returned_at')->constrained('users')->nullOnDelete();
            $table->string('condition_on_return', 20)->nullable()->after('returned_by');
            $table->string('return_note', 500)->nullable()->after('condition_on_return');

            $table->index('acknowledgement_status');
        });
    }

    public function down(): void
    {
        Schema::table('asset_requests', function (Blueprint $table) {
            $table->dropIndex(['acknowledgement_status']);
            $table->dropConstrainedForeignId('allocated_by');
            $table->dropConstrainedForeignId('returned_by');
            $table->dropColumn([
                'condition_on_allocation', 'acknowledgement_status', 'acknowledged_at',
                'acknowledgement_note', 'condition_on_return', 'return_note',
            ]);
        });

        DB::table('asset_requests')->where('status', 'Lost')->update(['status' => 'Returned']);

        Schema::table('asset_requests', function (Blueprint $table) {
            $table->enum('status', ['Pending', 'Approved', 'Rejected', 'Allocated', 'Returned'])->default('Pending')->change();
        });
    }
};
