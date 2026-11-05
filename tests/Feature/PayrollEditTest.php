<?php

namespace Tests\Feature;

use App\Models\Payroll;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Opening a saved payroll for one employee and month, and editing it.
 * The project's migrations use MySQL-only SQL, so the in-memory database gets just these tables.
 */
class PayrollEditTest extends TestCase
{
    protected User $manager;
    protected int $employeeId;
    protected Payroll $payroll;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('roles_master', function (Blueprint $t) {
            $t->id();
            $t->string('slug');
        });
        DB::table('roles_master')->insert([['id' => 1, 'slug' => 'super_admin'], ['id' => 2, 'slug' => 'manager'], ['id' => 3, 'slug' => 'hr_executive'], ['id' => 4, 'slug' => 'employee']]);
        Schema::create('departments', function (Blueprint $t) {
            $t->id();
            $t->string('name')->nullable();
            $t->timestamps();
        });
        Schema::create('employees', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email')->nullable();
            $t->string('role')->nullable();
            $t->unsignedBigInteger('department_id')->nullable();
            $t->boolean('pf')->default(false);
            $t->boolean('esi')->default(false);
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
        Schema::create('payrolls', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('employee_id');
            $t->string('month');
            foreach (['payable_days', 'unpaid_days', 'paid_leave_days', 'unpaid_leave_days', 'leave_balance_before_payroll', 'overtime_hours', 'overtime_days', 'salary_loss', 'gross_salary', 'basic_salary', 'dearness_allowance', 'hra', 'conveyance_allowance', 'medical_allowance', 'other_allowance', 'variable_earning', 'deductions', 'pf_deduction', 'esi_deduction', 'epf_deduction', 'professional_tax_deduction', 'loan_recovery_deduction', 'other_deduction', 'net_salary', 'monthly_salary'] as $column) {
                $t->decimal($column, 12, 2)->default(0);
            }
            $t->string('status')->default('pending');
            $t->date('payment_date')->nullable();
            $t->text('remarks')->nullable();
            $t->boolean('is_read')->default(true);
            $t->timestamps();
        });

        $this->employeeId = DB::table('employees')->insertGetId(['name' => 'Test Person', 'role' => 'employee', 'pf' => true]);
        DB::table('users')->insert(['name' => 'Test Person', 'email' => 'person@example.test', 'password' => 'x', 'role' => 'employee', 'employee_id' => $this->employeeId]);
        $managerEmployeeId = DB::table('employees')->insertGetId(['name' => 'Boss', 'role' => 'manager']);
        $this->manager = User::find(DB::table('users')->insertGetId(['name' => 'Boss', 'email' => 'boss@example.test', 'password' => 'x', 'role' => 'manager', 'employee_id' => $managerEmployeeId]));

        // Extra days beyond the 31-day month and a stored PF the automatic 12% wouldn't produce.
        $this->payroll = Payroll::create([
            'employee_id' => $this->employeeId,
            'month' => '2026-10',
            'payable_days' => 37,
            'basic_salary' => 36500,
            'gross_salary' => 43564.52,
            'pf_deduction' => 1800,
            'deductions' => 1800,
            'net_salary' => 41764,
            'status' => 'pending',
        ]);
    }

    public function test_lookup_returns_everything_saved_for_that_employee_and_month(): void
    {
        $response = $this->actingAs($this->manager)
            ->getJson(route('payroll.lookup', ['employee_id' => $this->employeeId, 'month' => '2026-10']));

        $response->assertOk()->assertJson(['success' => true]);
        $data = $response->json('data');
        $this->assertSame($this->payroll->id, $data['id']);
        $this->assertSame('Test Person', $data['employee_name']);
        $this->assertEquals(37, $data['payable_days']);
        $this->assertEquals(43564.52, $data['gross_salary']);
        $this->assertEquals(1800, $data['pf_deduction']);
        $this->assertEquals(41764, $data['net_salary']);
        // The month's real length, not an assumed 30 days.
        $this->assertSame(31, $data['total_days']);
        $this->assertTrue($data['pf_enabled']);
    }

    public function test_lookup_finds_nothing_for_an_unsaved_month_and_rejects_bad_input(): void
    {
        $this->actingAs($this->manager)
            ->getJson(route('payroll.lookup', ['employee_id' => $this->employeeId, 'month' => '2026-09']))
            ->assertOk()->assertJson(['success' => true, 'data' => null]);

        $this->actingAs($this->manager)
            ->getJson(route('payroll.lookup', ['employee_id' => $this->employeeId, 'month' => 'october']))
            ->assertUnprocessable();
    }

    public function test_employees_cannot_read_payroll_through_lookup_or_edit(): void
    {
        $employee = User::where('employee_id', $this->employeeId)->first();

        $this->actingAs($employee)
            ->getJson(route('payroll.lookup', ['employee_id' => $this->employeeId, 'month' => '2026-10']))
            ->assertForbidden();
        $this->actingAs($employee)->getJson(route('payroll.edit', $this->payroll->id))->assertForbidden();
    }

    public function test_edit_returns_the_same_saved_data_and_lookup_changes_nothing(): void
    {
        $before = $this->payroll->fresh()->getAttributes();

        $this->actingAs($this->manager)->getJson(route('payroll.edit', $this->payroll->id))
            ->assertOk()->assertJson(['success' => true, 'data' => ['id' => $this->payroll->id, 'total_days' => 31]]);

        $this->assertSame($before, $this->payroll->fresh()->getAttributes());
    }

    public function test_update_changes_only_the_opened_payroll(): void
    {
        $other = Payroll::create(['employee_id' => $this->employeeId, 'month' => '2026-09', 'gross_salary' => 100, 'net_salary' => 100]);

        $this->actingAs($this->manager)->putJson(route('payroll.update', $this->payroll->id), [
            'other_deduction' => 500,
            'deductions' => 2300,
            'net_salary' => 41264.52,
            'gross_salary' => 43564.52,
        ])->assertOk()->assertJson(['success' => true]);

        $saved = $this->payroll->fresh();
        $this->assertEquals(500, $saved->other_deduction);
        $this->assertEquals(41264.52, $saved->net_salary);
        $this->assertEquals(37, $saved->payable_days);
        $this->assertEquals(1800, $saved->pf_deduction);
        $this->assertEquals(100, $other->fresh()->net_salary);
    }

    /** The page through a bare layout: each form field must exist once, or scripts fill the wrong copy. */
    protected function payrollPage(): string
    {
        $dir = sys_get_temp_dir() . '/payroll-layout-' . uniqid();
        mkdir($dir . '/layouts', 0777, true);
        file_put_contents($dir . '/layouts/app.blade.php', "<html><head>@stack('styles')</head><body>@yield('content')@stack('modals')@stack('scripts')</body></html>");
        view()->getFinder()->prependLocation($dir);

        try {
            return $this->actingAs($this->manager)->get(route('payroll.index'))->assertOk()->getContent();
        } finally {
            view()->getFinder()->flush();
            unlink($dir . '/layouts/app.blade.php');
            rmdir($dir . '/layouts');
            rmdir($dir);
        }
    }

    public function test_page_has_one_form_with_every_field_the_scripts_need(): void
    {
        $html = $this->payrollPage();

        $this->assertStringNotContainsString('payrollCalculationOffcanvas', $html);
        foreach (['employeeSelect', 'employeeSelectBtn', 'monthSelect', 'payrollIdForUpdate', 'isEditMode', 'inputPayableDays', 'inputBasic', 'inputPF', 'inputDearnessAllowance', 'inputVariableEarning', 'inputEpf', 'inputProfessionalTax', 'inputLoanRecovery', 'rowDearnessAllowance', 'rowVariableEarning', 'rowEpf', 'rowProfessionalTax', 'rowLoanRecovery', 'overrideCheck', 'overrideAmount', 'calculationResult', 'noCalculation', 'tableGrossSalary', 'tableNetSalary', 'tableTotalDeductions', 'payrollSavedNotice'] as $id) {
            $this->assertSame(1, substr_count($html, 'id="' . $id . '"'), "#{$id} should appear exactly once");
        }
    }
}
