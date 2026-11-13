<?php

use App\Services\SalaryStructureService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Re-split every employee's current gross into the new Basic 50% / HRA 40% of Basic / Medical /
 * Travel / Other split
 * (config/salary_structure.php). Each employee's gross stays exactly the same.
 *
 * The old components are copied to employee_salary_backups first; `migrate:rollback` puts them back.
 */
return new class extends Migration
{
    private const FIELDS = ['basic_salary', 'dearness_allowance', 'hra', 'conveyance_allowance', 'medical_allowance', 'other_allowance'];

    public function up(): void
    {
        if (!Schema::hasTable('employee_salary_backups')) {
            Schema::create('employee_salary_backups', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('employee_id')->index();
                $table->string('reason', 100);
                foreach (self::FIELDS as $field) {
                    $table->decimal($field, 12, 2)->default(0);
                }
                $table->timestamp('backed_up_at')->useCurrent();
            });
        }

        DB::transaction(function () {
            $now = now();
            foreach (DB::table('employees')->select(array_merge(['id'], self::FIELDS))->orderBy('id')->get() as $row) {
                $old = [];
                foreach (self::FIELDS as $field) {
                    $old[$field] = round((float) ($row->{$field} ?? 0), 2);
                }
                $gross = round(array_sum($old), 2);
                if ($gross <= 0) {
                    continue;
                }

                DB::table('employee_salary_backups')->insert(
                    ['employee_id' => $row->id, 'reason' => 'resplit_2026_11', 'backed_up_at' => $now] + $old
                );
                DB::table('employees')->where('id', $row->id)->update(
                    SalaryStructureService::breakdown($gross) + ['updated_at' => $now]
                );
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('employee_salary_backups')) {
            return;
        }

        DB::transaction(function () {
            foreach (DB::table('employee_salary_backups')->where('reason', 'resplit_2026_11')->get() as $b) {
                $restore = [];
                foreach (self::FIELDS as $field) {
                    $restore[$field] = $b->{$field};
                }
                DB::table('employees')->where('id', $b->employee_id)->update($restore);
            }
            DB::table('employee_salary_backups')->where('reason', 'resplit_2026_11')->delete();
        });
        // The backup table itself is kept (it may hold other backups later).
    }
};
