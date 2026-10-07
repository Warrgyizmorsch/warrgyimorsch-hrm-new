<?php

namespace App\Models;

use App\Services\AttendanceService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * One employee's user ID on one biometric machine. Device IDs are only unique per machine
 * (zk user 17 and rs9n user 17 are different people), so every lookup is by
 * (machine, device_user_id). attendance_logs.user_id still stores the employee_code.
 */
class BiometricEnrollment extends Model
{
    protected $fillable = ['employee_id', 'machine', 'device_user_id'];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /** Machines selectable on the employee form: the synced ones plus any labelled in config. */
    public static function machines(): array
    {
        $labels = config('biometric.machine_labels', []);
        $keys = array_unique(array_merge(config('biometric.machines', []), array_keys($labels)));

        return collect($keys)->mapWithKeys(fn ($key) => [$key => $labels[$key] ?? $key])->all();
    }

    /** "007" and "7" are the same device user; non-numeric IDs are kept as-is. */
    public static function normalizeId($deviceUserId): string
    {
        $id = trim((string) $deviceUserId);

        return ctype_digit($id) ? (string) (int) $id : $id;
    }

    /**
     * Lookup table for a sync/import run.
     *
     * @return array<string, string> "machine|device_user_id" => employee_code
     */
    public static function codeMap(): array
    {
        return self::query()
            ->join('employees', 'employees.id', '=', 'biometric_enrollments.employee_id')
            ->whereNotNull('employees.employee_code')
            ->where('employees.employee_code', '!=', '')
            ->get(['biometric_enrollments.machine', 'biometric_enrollments.device_user_id', 'employees.employee_code'])
            ->mapWithKeys(fn ($row) => [$row->machine . '|' . $row->device_user_id => (string) $row->employee_code])
            ->all();
    }

    public static function resolveCode(array $codeMap, string $machine, $deviceUserId): ?string
    {
        return $codeMap[$machine . '|' . self::normalizeId($deviceUserId)] ?? null;
    }

    /** Keep a punch with no enrollment so it can be replayed once HR maps the ID. */
    public static function recordUnmapped(string $machine, $deviceUserId, string $punchedAt, ?string $direction, string $source): void
    {
        DB::table('biometric_unmapped_punches')->updateOrInsert(
            ['machine' => $machine, 'device_user_id' => self::normalizeId($deviceUserId), 'punched_at' => $punchedAt],
            ['direction' => $direction, 'source' => $source, 'updated_at' => now(), 'created_at' => now()]
        );
    }

    /**
     * Unmapped device IDs seen recently, for the employee form hint.
     *
     * @return \Illuminate\Support\Collection<int, object{machine: string, device_user_id: string, punches: int, last_punch: string}>
     */
    public static function unmappedSummary(int $days = 60)
    {
        return DB::table('biometric_unmapped_punches')
            ->where('punched_at', '>=', now()->subDays($days))
            ->select('machine', 'device_user_id', DB::raw('COUNT(*) as punches'), DB::raw('MAX(punched_at) as last_punch'))
            ->groupBy('machine', 'device_user_id')
            ->orderBy('machine')
            ->orderByRaw('CAST(device_user_id AS UNSIGNED)')
            ->get();
    }

    /**
     * Move any stored unmapped punches for this employee's enrollments into attendance_logs
     * and rebuild their attendance, so mapping an ID also recovers punches made before it.
     *
     * @return int punches recovered
     */
    public static function replayUnmappedFor(Employee $employee): int
    {
        $code = trim((string) $employee->employee_code);
        if ($code === '') {
            return 0;
        }

        $recovered = 0;

        foreach ($employee->biometricEnrollments as $enrollment) {
            $rows = DB::table('biometric_unmapped_punches')
                ->where('machine', $enrollment->machine)
                ->where('device_user_id', $enrollment->device_user_id)
                ->get();

            foreach ($rows as $row) {
                DB::table('attendance_logs')->updateOrInsert(
                    ['user_id' => $code, 'timestamp' => $row->punched_at],
                    [
                        'device_uid' => $enrollment->machine,
                        'punch' => $row->direction === 'OUT' ? 1 : 0,
                        'status' => $row->direction,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }

            $recovered += DB::table('biometric_unmapped_punches')->whereIn('id', $rows->pluck('id'))->delete();
        }

        if ($recovered > 0) {
            app(AttendanceService::class)->rebuildFromDeviceLogs($code);
        }

        return $recovered;
    }
}
