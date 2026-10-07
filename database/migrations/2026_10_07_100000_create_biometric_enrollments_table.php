<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Which employee each biometric machine's user ID belongs to. Device IDs are only
        // unique *per machine* (zk user 17 and rs9n user 17 are different people), so the
        // machine is part of the key. employee_code stays a pure HR/payroll number.
        Schema::create('biometric_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('machine', 50);
            $table->string('device_user_id', 50);
            $table->timestamps();

            $table->unique(['machine', 'device_user_id']);
            $table->unique(['employee_id', 'machine']);
        });

        // Punches whose device ID has no enrollment yet. Kept (not just logged) so they can
        // be replayed into attendance once HR maps the ID to an employee.
        Schema::create('biometric_unmapped_punches', function (Blueprint $table) {
            $table->id();
            $table->string('machine', 50);
            $table->string('device_user_id', 50);
            $table->dateTime('punched_at');
            $table->string('direction', 10)->nullable();
            $table->string('source', 30)->default('sync');
            $table->timestamps();

            $table->unique(['machine', 'device_user_id', 'punched_at'], 'bio_unmapped_machine_id_time_unique');
        });

        $now = now();

        // rs9n: carry over every existing rs9n_device_id as-is.
        $rs9n = DB::table('employees')->whereNotNull('rs9n_device_id')->get(['id', 'rs9n_device_id']);
        foreach ($rs9n as $row) {
            DB::table('biometric_enrollments')->insert([
                'employee_id' => $row->id, 'machine' => 'rs9n', 'device_user_id' => (string) $row->rs9n_device_id,
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        // zk: the old matching treated every employee_code as a zk user ID. Carry that over
        // for everyone NOT on rs9n (they can only be punching on zk), plus any rs9n person who
        // actually punched on zk in the last 60 days (genuinely on both machines). Other rs9n
        // people are deliberately NOT mapped: their employee_code may equal a different zk
        // user's number, which is exactly the misattribution this table exists to prevent.
        $rs9nIds = $rs9n->pluck('id')->all();
        $recentZkCodes = DB::table('attendance_logs')
            ->where('timestamp', '>=', now()->subDays(60))
            ->whereNotIn('device_uid', ['rs9n', 'excel-import'])
            ->where('device_uid', 'not like', 'RSS%')
            ->distinct()
            ->pluck('user_id')
            ->map(fn ($code) => (string) $code)
            ->all();

        $taken = [];
        DB::table('employees')->whereNotNull('employee_code')->where('employee_code', '!=', '')
            ->orderByDesc('id')
            ->get(['id', 'employee_code'])
            ->each(function ($row) use ($rs9nIds, $recentZkCodes, $now, &$taken) {
                $code = trim((string) $row->employee_code);
                $deviceId = ctype_digit($code) ? (string) (int) $code : $code;

                $onRs9n = in_array($row->id, $rs9nIds, true);
                if ($onRs9n && !in_array($code, $recentZkCodes, true)) {
                    return;
                }
                if (isset($taken[$deviceId])) {
                    return; // duplicate code — newest employee keeps it, same as the old lookup
                }
                $taken[$deviceId] = true;

                DB::table('biometric_enrollments')->insert([
                    'employee_id' => $row->id, 'machine' => 'zk', 'device_user_id' => $deviceId,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropUnique(['rs9n_device_id']);
            $table->dropColumn('rs9n_device_id');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->unsignedInteger('rs9n_device_id')->nullable()->unique()->after('employee_code');
        });

        DB::table('biometric_enrollments')->where('machine', 'rs9n')->get()->each(function ($row) {
            if (ctype_digit($row->device_user_id)) {
                DB::table('employees')->where('id', $row->employee_id)->update(['rs9n_device_id' => (int) $row->device_user_id]);
            }
        });

        Schema::dropIfExists('biometric_unmapped_punches');
        Schema::dropIfExists('biometric_enrollments');
    }
};
