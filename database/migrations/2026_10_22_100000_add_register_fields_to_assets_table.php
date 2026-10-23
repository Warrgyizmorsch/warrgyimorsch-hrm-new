<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Asset register: asset tag, make/model, purchase + warranty, location, condition, notes.
 * status becomes a plain string so the lifecycle can include Lost and Retired.
 * Existing assets get a tag (WM-LT-0001 style) in id order.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->string('status', 20)->default('Available')->change();

            $table->string('asset_tag', 30)->nullable()->unique()->after('id');
            $table->string('brand', 100)->nullable()->after('type');
            $table->string('model', 100)->nullable()->after('brand');
            $table->string('condition', 20)->default('Good')->after('status');
            $table->string('location', 150)->nullable()->after('condition');
            $table->date('purchase_date')->nullable()->after('location');
            $table->decimal('purchase_cost', 12, 2)->nullable()->after('purchase_date');
            $table->string('vendor', 150)->nullable()->after('purchase_cost');
            $table->string('invoice_number', 100)->nullable()->after('vendor');
            $table->date('warranty_expiry')->nullable()->after('invoice_number');
            $table->text('notes')->nullable()->after('warranty_expiry');

            $table->index('status');
            $table->index('type');
        });

        // Backfill tags for assets that already exist (deleted ones too, so numbers never repeat).
        $codes = [
            'pc' => 'PC', 'laptop' => 'LT', 'keyboard' => 'KB', 'mouse' => 'MS', 'monitor' => 'MN',
            'charger' => 'CH', 'headphone' => 'HP', 'earphone' => 'EP', 'pendrive/hard disk' => 'SD', 'other' => 'OT',
        ];
        $counters = [];

        DB::table('assets')->orderBy('id')->select('id', 'type')->get()->each(function ($row) use ($codes, &$counters) {
            $type = strtolower(trim((string) $row->type));
            $code = $codes[$type] ?? (strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $type), 0, 2)) ?: 'OT');
            $counters[$code] = ($counters[$code] ?? 0) + 1;

            DB::table('assets')->where('id', $row->id)->update([
                'asset_tag' => sprintf('WM-%s-%04d', $code, $counters[$code]),
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['type']);
            $table->dropUnique(['asset_tag']);
            $table->dropColumn([
                'asset_tag', 'brand', 'model', 'condition', 'location', 'purchase_date',
                'purchase_cost', 'vendor', 'invoice_number', 'warranty_expiry', 'notes',
            ]);
        });

        DB::table('assets')->whereIn('status', ['Lost', 'Retired'])->update(['status' => 'Faulty']);

        Schema::table('assets', function (Blueprint $table) {
            $table->enum('status', ['Available', 'Allocated', 'Maintenance', 'Faulty'])->default('Available')->change();
        });
    }
};
