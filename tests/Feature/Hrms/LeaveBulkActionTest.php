<?php

namespace Tests\Feature\Hrms;

use App\Models\Attendance;
use App\Models\LeaveApplication;
use App\Models\User;
use App\Services\LeaveNotificationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * Bulk actions on the leave applications list.
 *
 * The project's migrations don't run on SQLite (they use MODIFY), so this builds just the
 * tables it needs in the in-memory test database. Notifications are mocked: nothing is sent.
 */
class LeaveBulkActionTest extends TestCase
{
    private MockInterface $notifications;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('departments', function (Blueprint $t) {
            $t->id();
            $t->string('name')->nullable();
            $t->timestamps();
        });
        Schema::create('department_employee_led', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('department_id');
            $t->unsignedBigInteger('employee_id');
        });
        Schema::create('employees', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email')->nullable();
            $t->string('role')->nullable();
            $t->unsignedBigInteger('department_id')->nullable();
            $t->softDeletes();
            $t->timestamps();
        });
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email')->nullable();
            $t->string('password')->nullable();
            $t->string('role')->nullable();
            $t->string('account_status')->default('active');
            $t->unsignedBigInteger('employee_id')->nullable();
            $t->rememberToken();
            $t->timestamps();
        });
        Schema::create('holidays', function (Blueprint $t) {
            $t->id();
            $t->date('date');
            $t->timestamps();
        });
        Schema::create('leave_applications', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('employee_id');
            $t->string('leave_type');
            $t->string('leave_category');
            $t->date('start_date');
            $t->date('end_date')->nullable();
            $t->time('start_time')->nullable();
            $t->time('end_time')->nullable();
            $t->text('reason')->nullable();
            $t->text('message')->nullable();
            $t->string('status');
            $t->string('approval_stage')->nullable();
            $t->string('tl_status')->nullable();
            $t->unsignedBigInteger('tl_action_by')->nullable();
            $t->timestamp('tl_action_at')->nullable();
            $t->text('tl_remarks')->nullable();
            $t->unsignedBigInteger('manager_action_by')->nullable();
            $t->timestamp('manager_action_at')->nullable();
            $t->text('manager_remarks')->nullable();
            $t->double('total_days')->default(1);
            $t->timestamps();
        });
        Schema::create('leave_application_logs', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('leave_application_id');
            $t->string('action');
            $t->string('level')->nullable();
            $t->string('from_status')->nullable();
            $t->string('to_status')->nullable();
            $t->text('remarks')->nullable();
            $t->unsignedBigInteger('actor_user_id')->nullable();
            $t->string('actor_name')->nullable();
            $t->timestamp('created_at')->nullable();
        });
        Schema::create('attendances', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('employee_id');
            $t->date('attendance_date');
            $t->time('check_in')->nullable();
            $t->time('check_out')->nullable();
            $t->string('status');
            $t->boolean('is_manual')->default(false);
            $t->decimal('total_hours', 5, 2)->nullable();
            $t->timestamps();
        });

        DB::table('departments')->insert([['id' => 1, 'name' => 'Dev'], ['id' => 2, 'name' => 'Sales']]);

        $this->notifications = $this->mock(LeaveNotificationService::class);
    }

    protected function person(string $name, string $role, int $department = 1): User
    {
        $employeeId = DB::table('employees')->insertGetId([
            'name' => $name,
            'email' => strtolower($name) . '@example.test',
            'role' => $role,
            'department_id' => $department,
        ]);
        $userId = DB::table('users')->insertGetId([
            'name' => $name,
            'email' => strtolower($name) . '@example.test',
            'password' => 'x',
            'role' => $role,
            'employee_id' => $employeeId,
        ]);

        return User::find($userId);
    }

    protected function leave(User $applicant, array $overrides = []): LeaveApplication
    {
        return LeaveApplication::create(array_merge([
            'employee_id' => $applicant->employee_id,
            'leave_type' => 'Full Day',
            'leave_category' => 'Paid Leave',
            'start_date' => '2026-11-10', // a Tuesday
            'end_date' => '2026-11-10',
            'reason' => 'test',
            'status' => 'pending',
            'approval_stage' => 'manager',
            'tl_status' => 'skipped',
            'total_days' => 1,
        ], $overrides));
    }

    public function test_manager_decides_open_leaves_and_reports_the_rest(): void
    {
        $manager = $this->person('Manager', 'manager');
        $ravi = $this->person('Ravi', 'employee');
        $sita = $this->person('Sita', 'employee');

        $open = $this->leave($ravi);
        $onHold = $this->leave($sita, ['status' => 'on_hold']);
        $tlPending = $this->leave($sita, ['approval_stage' => 'tl', 'tl_status' => 'pending', 'start_date' => '2026-11-12', 'end_date' => '2026-11-12']);
        $alreadyRejected = $this->leave($ravi, ['status' => 'rejected', 'approval_stage' => 'done', 'manager_remarks' => 'original']);
        $own = $this->leave($manager);

        $this->notifications->shouldReceive('managerDecided')->times(3);

        $response = $this->actingAs($manager)->postJson(route('leave.bulkAction'), [
            'ids' => [$open->id, $onHold->id, $tlPending->id, $alreadyRejected->id, $own->id, 999999],
            'status' => 'approved',
            'remarks' => 'Bulk approved',
        ]);

        $response->assertOk()->assertJson(['success' => true, 'updated' => 3]);
        $this->assertCount(3, $response->json('skipped'));

        foreach ([$open, $onHold, $tlPending] as $leave) {
            $leave->refresh();
            $this->assertSame('approved', $leave->status);
            $this->assertSame('done', $leave->approval_stage);
            $this->assertSame('Bulk approved', $leave->manager_remarks);
            $this->assertSame($manager->id, (int) $leave->manager_action_by);
        }
        $this->assertSame('bypassed', $tlPending->tl_status);

        // A decided leave is not overturned in bulk, and nobody decides their own.
        $this->assertSame('rejected', $alreadyRejected->refresh()->status);
        $this->assertSame('original', $alreadyRejected->manager_remarks);
        $this->assertSame('pending', $own->refresh()->status);

        // Attendance and the timeline are written just as for a single decision.
        $this->assertSame('leave', Attendance::where('employee_id', $ravi->employee_id)->whereDate('attendance_date', '2026-11-10')->value('status'));
        $this->assertSame(3, DB::table('leave_application_logs')->where('action', 'manager_update')->where('to_status', 'approved')->count());
    }

    public function test_rejecting_or_holding_writes_no_attendance(): void
    {
        $manager = $this->person('Manager', 'manager');
        $a = $this->leave($this->person('Ravi', 'employee'));
        $b = $this->leave($this->person('Sita', 'employee'));

        $this->notifications->shouldReceive('managerDecided')->times(2);

        $this->actingAs($manager)->postJson(route('leave.bulkAction'), ['ids' => [$a->id], 'status' => 'rejected', 'remarks' => 'No cover'])
            ->assertJson(['updated' => 1]);
        $this->actingAs($manager)->postJson(route('leave.bulkAction'), ['ids' => [$b->id], 'status' => 'on_hold', 'remarks' => 'Wait'])
            ->assertJson(['updated' => 1]);

        $this->assertSame('rejected', $a->refresh()->status);
        $this->assertSame('on_hold', $b->refresh()->status);
        $this->assertSame(0, Attendance::count());
    }

    public function test_a_manager_cannot_bulk_decide_another_managers_leave_but_hr_can(): void
    {
        $manager = $this->person('Manager', 'manager');
        $hr = $this->person('Hr', 'hr_executive');
        $otherManagerLeave = $this->leave($this->person('Boss', 'manager'));

        $this->actingAs($manager)->postJson(route('leave.bulkAction'), ['ids' => [$otherManagerLeave->id], 'status' => 'approved', 'remarks' => 'Fine'])
            ->assertOk()->assertJson(['success' => false, 'updated' => 0]);
        $this->assertSame('pending', $otherManagerLeave->refresh()->status);

        $this->notifications->shouldReceive('managerDecided')->once();
        $this->actingAs($hr)->postJson(route('leave.bulkAction'), ['ids' => [$otherManagerLeave->id], 'status' => 'approved', 'remarks' => 'Fine'])
            ->assertJson(['updated' => 1]);
        $this->assertSame('approved', $otherManagerLeave->refresh()->status);
    }

    public function test_team_leader_decides_level_1_for_own_team_only(): void
    {
        $tl = $this->person('Leader', 'team_leader', 1);
        $mine = $this->leave($this->person('Ravi', 'employee', 1), ['approval_stage' => 'tl', 'tl_status' => 'pending']);
        $alsoMine = $this->leave($this->person('Sita', 'employee', 1), ['approval_stage' => 'tl', 'tl_status' => 'pending']);
        $otherTeam = $this->leave($this->person('Amit', 'employee', 2), ['approval_stage' => 'tl', 'tl_status' => 'pending']);

        $this->notifications->shouldReceive('teamLeaderDecided')->once();
        $this->actingAs($tl)->postJson(route('leave.bulkAction'), ['ids' => [$mine->id, $otherTeam->id], 'status' => 'approved', 'remarks' => 'OK by me'])
            ->assertOk()->assertJson(['updated' => 1]);

        $mine->refresh();
        $this->assertSame('pending', $mine->status);
        $this->assertSame('manager', $mine->approval_stage);
        $this->assertSame('approved', $mine->tl_status);
        $this->assertSame('pending', $otherTeam->refresh()->tl_status);

        // Only Level 1 approve / reject is open to a TL.
        $this->actingAs($tl)->postJson(route('leave.bulkAction'), ['ids' => [$alsoMine->id], 'status' => 'on_hold', 'remarks' => 'Later'])
            ->assertForbidden();
        $this->assertSame('pending', $alsoMine->refresh()->status);
    }

    public function test_access_and_validation(): void
    {
        $employee = $this->person('Ravi', 'employee');
        $tl = $this->person('Leader', 'team_leader');
        $manager = $this->person('Manager', 'manager');
        $leave = $this->leave($employee);

        $this->postJson(route('leave.bulkAction'), ['ids' => [$leave->id], 'status' => 'approved', 'remarks' => 'abc'])->assertUnauthorized();
        $this->actingAs($employee)->postJson(route('leave.bulkAction'), ['ids' => [$leave->id], 'status' => 'approved', 'remarks' => 'abc'])->assertForbidden();
        $this->actingAs($employee)->postJson(route('leave.bulkDestroy'), ['ids' => [$leave->id]])->assertForbidden();
        $this->actingAs($tl)->postJson(route('leave.bulkDestroy'), ['ids' => [$leave->id]])->assertForbidden();

        $this->actingAs($manager)->postJson(route('leave.bulkAction'), ['ids' => [$leave->id], 'status' => 'approved'])->assertUnprocessable();
        $this->actingAs($manager)->postJson(route('leave.bulkAction'), ['ids' => [$leave->id], 'status' => 'pending', 'remarks' => 'abc'])->assertUnprocessable();
        $this->actingAs($manager)->postJson(route('leave.bulkAction'), ['ids' => [], 'status' => 'approved', 'remarks' => 'abc'])->assertUnprocessable();
        $this->actingAs($manager)->postJson(route('leave.bulkAction'), ['ids' => range(1, 101), 'status' => 'approved', 'remarks' => 'abc'])->assertUnprocessable();

        $this->assertSame('pending', $leave->refresh()->status);
        $this->assertDatabaseHas('leave_applications', ['id' => $leave->id]);
    }

    public function test_bulk_delete_leaves_decisions_with_attendance_alone(): void
    {
        $manager = $this->person('Manager', 'manager');
        $ravi = $this->person('Ravi', 'employee');
        $pending = $this->leave($ravi);
        $rejected = $this->leave($ravi, ['status' => 'rejected']);
        $approved = $this->leave($ravi, ['status' => 'approved']);

        $response = $this->actingAs($manager)->postJson(route('leave.bulkDestroy'), ['ids' => [$pending->id, $rejected->id, $approved->id]]);

        $response->assertOk()->assertJson(['success' => true, 'updated' => 2]);
        $this->assertCount(1, $response->json('skipped'));
        $this->assertDatabaseMissing('leave_applications', ['id' => $pending->id]);
        $this->assertDatabaseMissing('leave_applications', ['id' => $rejected->id]);
        $this->assertDatabaseHas('leave_applications', ['id' => $approved->id]);
    }

    /** The real page through a bare layout (the app layout needs tables this test doesn't build). */
    protected function leaveListAs(User $user): string
    {
        $dir = sys_get_temp_dir() . '/leave-bulk-layout-' . uniqid();
        mkdir($dir . '/layouts', 0777, true);
        file_put_contents($dir . '/layouts/app.blade.php', "<html><head>@stack('styles')</head><body>@yield('content')@stack('scripts')</body></html>");
        view()->getFinder()->prependLocation($dir);

        try {
            return $this->actingAs($user)->get(route('leave.history'))->assertOk()->getContent();
        } finally {
            view()->getFinder()->flush();
            unlink($dir . '/layouts/app.blade.php');
            rmdir($dir . '/layouts');
            rmdir($dir);
        }
    }

    public function test_list_offers_tick_boxes_only_where_a_bulk_action_is_possible(): void
    {
        $manager = $this->person('Manager', 'manager');
        $ravi = $this->person('Ravi', 'employee');
        $open = $this->leave($ravi);
        $rejected = $this->leave($ravi, ['status' => 'rejected', 'approval_stage' => 'done']);
        $approved = $this->leave($ravi, ['status' => 'approved', 'approval_stage' => 'done']);

        $html = $this->leaveListAs($manager);

        $this->assertStringContainsString('id="leaveBulkBar"', $html);
        $this->assertMatchesRegularExpression('/lh-bulk-check" value="' . $open->id . '".*?data-can-decide="1"\s+data-can-delete="1"/s', $html);
        $this->assertMatchesRegularExpression('/lh-bulk-check" value="' . $rejected->id . '".*?data-can-decide="0"\s+data-can-delete="1"/s', $html);
        $this->assertStringNotContainsString('lh-bulk-check" value="' . $approved->id . '"', $html);
    }

    public function test_team_leader_gets_only_approve_and_reject_and_employee_no_page(): void
    {
        $tl = $this->person('Leader', 'team_leader');
        $ravi = $this->person('Ravi', 'employee');
        $mine = $this->leave($ravi, ['approval_stage' => 'tl', 'tl_status' => 'pending']);

        $this->actingAs($ravi)->get(route('leave.history'))->assertForbidden();

        $tlHtml = $this->leaveListAs($tl);
        $this->assertStringContainsString('lh-bulk-check" value="' . $mine->id . '"', $tlHtml);
        $this->assertStringContainsString('data-bulk-status="approved"', $tlHtml);
        $this->assertStringNotContainsString('data-bulk-status="on_hold"', $tlHtml);
        $this->assertStringNotContainsString('id="leaveBulkDelete"', $tlHtml);
    }

    /** updateAction now shares its code with the bulk path: it must behave as before. */
    public function test_single_update_still_writes_and_clears_attendance(): void
    {
        $manager = $this->person('Manager', 'manager');
        $ravi = $this->person('Ravi', 'employee');
        $leave = $this->leave($ravi);

        $this->notifications->shouldReceive('managerDecided')->times(2);

        $this->actingAs($manager)->postJson(route('leave.updateAction'), ['leave_id' => $leave->id, 'status' => 'approved', 'remarks' => 'Fine'])
            ->assertOk()->assertJson(['success' => true]);
        $this->assertSame(1, Attendance::where('employee_id', $ravi->employee_id)->count());

        // SQLite keeps the time part the date cast adds; a MySQL DATE column drops it.
        DB::table('attendances')->update(['attendance_date' => '2026-11-10']);

        $this->actingAs($manager)->postJson(route('leave.updateAction'), ['leave_id' => $leave->id, 'status' => 'rejected', 'remarks' => 'Changed'])
            ->assertOk();
        $this->assertSame('rejected', $leave->refresh()->status);
        $this->assertSame(0, Attendance::where('employee_id', $ravi->employee_id)->count());
    }
}
