<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->unsignedInteger('rs9n_device_id')->nullable()->unique()->after('employee_code');
        });

        // Carry over the mapping that used to live only in config/biometric.php,
        // so existing rs9n enrollees keep working once the code switches to the DB.
        $legacyMap = [
            1 => 34, 2 => 46, 3 => 11, 4 => 55, 5 => 5, 6 => 37, 7 => 6, 8 => 57,
            9 => 49, 10 => 9, 11 => 40, 12 => 45, 13 => 13, 14 => 54, 15 => 32,
            16 => 8, 17 => 2, 18 => 39, 19 => 15, 20 => 36, 21 => 17, 22 => 50,
            23 => 59, 24 => 56, 25 => 25, 26 => 21, 27 => 3, 28 => 16,
            29 => 112,
        ];

        foreach ($legacyMap as $deviceId => $employeeCode) {
            DB::table('employees')
                ->where('employee_code', (string) $employeeCode)
                ->update(['rs9n_device_id' => $deviceId]);
        }
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('rs9n_device_id');
        });
    }
};
