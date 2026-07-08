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
        Schema::table('job_applications', function (Blueprint $table) {
            $table->foreignId('candidate_id')->nullable()->after('id')
                ->constrained('candidates')->nullOnDelete();
            $table->foreignId('job_requirement_id')->nullable()->after('candidate_id')
                ->constrained('job_requirements')->nullOnDelete();
            $table->foreignId('hired_employee_id')->nullable()->after('job_requirement_id')
                ->constrained('employees')->nullOnDelete();
        });

        $this->backfillCandidates();
        $this->backfillStatuses();
    }

    /**
     * Deliberately uses the DB facade only (no Eloquent) so no model events/observers
     * fire during backfill — this must never trigger the hire->employee bridge.
     */
    private function backfillCandidates(): void
    {
        $rows = DB::table('job_applications')->select('id', 'name', 'email', 'phone', 'qualification', 'experience', 'resume')->get();

        $candidateIdsByEmail = [];

        foreach ($rows as $row) {
            $email = strtolower(trim((string) $row->email));

            if ($email === '') {
                continue;
            }

            if (!isset($candidateIdsByEmail[$email])) {
                $existing = DB::table('candidates')->where('email', $email)->first();

                $candidateIdsByEmail[$email] = $existing
                    ? $existing->id
                    : DB::table('candidates')->insertGetId([
                        'name' => $row->name,
                        'email' => $email,
                        'phone' => $row->phone,
                        'qualification' => $row->qualification,
                        'experience' => $row->experience,
                        'resume' => $row->resume,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
            }

            DB::table('job_applications')->where('id', $row->id)
                ->update(['candidate_id' => $candidateIdsByEmail[$email]]);
        }
    }

    /**
     * Maps the old 4-state status to the new pipeline keys. "Selected" intentionally
     * maps to "offered", not "hired" — so this backfill never triggers the new
     * hire->employee flow. HR can promote individual records to "Hired" by hand.
     */
    private function backfillStatuses(): void
    {
        $map = [
            'Pending' => 'applied',
            'Awaited' => 'interview_scheduled',
            'Selected' => 'offered',
            'Rejected' => 'rejected',
        ];

        foreach ($map as $old => $new) {
            DB::table('job_applications')->where('status', $old)->update(['status' => $new]);
        }

        // Anything left over (blank/unrecognised) defaults to the first pipeline stage.
        DB::table('job_applications')
            ->whereNotIn('status', array_values($map))
            ->update(['status' => 'applied']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->dropForeign(['candidate_id']);
            $table->dropForeign(['job_requirement_id']);
            $table->dropForeign(['hired_employee_id']);
            $table->dropColumn(['candidate_id', 'job_requirement_id', 'hired_employee_id']);
        });
    }
};
