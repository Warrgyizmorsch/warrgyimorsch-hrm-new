<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('employee_reviews', function (Blueprint $table) {
            $table->text('author_note')->nullable()->after('admin_total');
            $table->text('admin_note')->nullable()->after('author_note');
        });

        // Carry forward any existing single note as the team leader's note (best-effort, non-blocking).
        DB::table('employee_review_details')
            ->whereNotNull('note')
            ->orderBy('id')
            ->get(['id', 'review_id', 'note'])
            ->groupBy('review_id')
            ->each(function ($rows) {
                $note = $rows->first()->note;
                DB::table('employee_reviews')->where('id', $rows->first()->review_id)->update(['author_note' => $note]);
            });

        Schema::table('employee_review_details', function (Blueprint $table) {
            $table->dropColumn('note');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_review_details', function (Blueprint $table) {
            $table->text('note')->nullable()->after('admin_review');
        });

        Schema::table('employee_reviews', function (Blueprint $table) {
            $table->dropColumn(['author_note', 'admin_note']);
        });
    }
};
