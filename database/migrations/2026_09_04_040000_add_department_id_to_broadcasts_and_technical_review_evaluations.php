<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('broadcasts', 'department_id')) {
            Schema::table('broadcasts', function (Blueprint $table) {
                $table->foreignId('department_id')->nullable()->after('department')
                    ->constrained('departments')->nullOnDelete();
            });
        }

        if (!Schema::hasColumn('technical_review_evaluations', 'department_id')) {
            Schema::table('technical_review_evaluations', function (Blueprint $table) {
                $table->foreignId('department_id')->nullable()->after('department')
                    ->constrained('departments')->nullOnDelete();
            });
        }

        $lookup = [];
        foreach (DB::table('departments')->get(['id', 'name']) as $dept) {
            $lookup[$this->normalize($dept->name)] = $dept->id;
        }

        // On environments where "Web Development"/"Mobile App Development"/"App Development"
        // were later renamed/merged into "Web/App Development" (as happened on this app's dev
        // database), rows written before that still carry the old strings — redirect them.
        // Conditional on "web/app development" actually existing here: on an environment where
        // that rename never happened (e.g. a fresh deploy), the plain direct match below already
        // resolves "Web Development" etc. against their own still-separate department rows, so
        // forcing this alias unconditionally would incorrectly send them to a department that
        // doesn't exist there.
        $aliases = [];
        if (isset($lookup['web/app development'])) {
            $aliases = [
                'web development' => 'web/app development',
                'mobile app development' => 'web/app development',
                'app development' => 'web/app development',
            ];
        }

        $resolve = function (?string $name) use ($lookup, $aliases) {
            if (!$name) {
                return null;
            }
            $norm = $this->normalize($name);
            $norm = $aliases[$norm] ?? $norm;
            return $lookup[$norm] ?? null;
        };

        $unmatched = [];

        foreach (DB::table('broadcasts')->get(['id', 'department']) as $broadcast) {
            if ($broadcast->department === 'All' || !$broadcast->department) {
                continue; // NULL department_id = "All", already the column default
            }
            $deptId = $resolve($broadcast->department);
            if ($deptId) {
                DB::table('broadcasts')->where('id', $broadcast->id)->update(['department_id' => $deptId]);
            } else {
                $unmatched[] = "broadcast #{$broadcast->id} department='{$broadcast->department}'";
            }
        }

        foreach (DB::table('technical_review_evaluations')->get(['id', 'department']) as $row) {
            $deptId = $resolve($row->department);
            if ($deptId) {
                DB::table('technical_review_evaluations')->where('id', $row->id)->update(['department_id' => $deptId]);
            } else {
                $unmatched[] = "technical_review_evaluation #{$row->id} department='{$row->department}'";
            }
        }

        if (!empty($unmatched)) {
            fwrite(STDOUT, "\n[add_department_id_to_broadcasts_and_technical_review_evaluations] " . count($unmatched) . " unmatched string(s):\n");
            foreach ($unmatched as $line) {
                fwrite(STDOUT, "  - {$line}\n");
            }
        } else {
            fwrite(STDOUT, "\n[add_department_id_to_broadcasts_and_technical_review_evaluations] all department strings matched cleanly.\n");
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('broadcasts', 'department_id')) {
            Schema::table('broadcasts', function (Blueprint $table) {
                $table->dropConstrainedForeignId('department_id');
            });
        }

        if (Schema::hasColumn('technical_review_evaluations', 'department_id')) {
            Schema::table('technical_review_evaluations', function (Blueprint $table) {
                $table->dropConstrainedForeignId('department_id');
            });
        }
    }

    private function normalize(string $value): string
    {
        return strtolower(str_replace('_', ' ', trim($value)));
    }
};
