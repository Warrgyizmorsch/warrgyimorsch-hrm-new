<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Payroll;
use App\Models\User;
use App\Services\PayrollImportService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Tests\TestCase;

class PayrollImportTest extends TestCase
{
    private const MONTH = '2026-10';

    private PayrollImportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createSchema();
        $this->service = app(PayrollImportService::class);
        $this->employee('29', 'Kapil Paliwal', 36500);
        $this->employee('57', 'Kapil menaria', 6000);
    }

    /** The project's migrations use MySQL-only SQL, so the in-memory SQLite database gets just these tables. */
    private function createSchema(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('role')->nullable();
            $table->string('account_status')->nullable();
            $table->foreignId('employee_id')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_code');
            $table->string('name');
            $table->string('email')->nullable();
            foreach (['basic_salary', 'dearness_allowance', 'hra', 'conveyance_allowance', 'medical_allowance', 'other_allowance'] as $column) {
                $table->decimal($column, 12, 2)->default(0);
            }
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id');
            $table->string('month');
            foreach ((new Payroll())->getFillable() as $column) {
                if (!in_array($column, ['employee_id', 'month', 'status', 'payment_date', 'remarks'], true)) {
                    $table->decimal($column, 12, 2)->default(0);
                }
            }
            $table->string('status')->default('pending');
            $table->date('payment_date')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    private function employee(string $code, string $name, float $basic): Employee
    {
        $id = DB::table('employees')->insertGetId([
            'employee_code' => $code,
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)) . '@example.test',
            'basic_salary' => $basic,
        ]);

        User::factory()->create(['account_status' => 'active', 'employee_id' => $id]);

        return Employee::findOrFail($id);
    }

    /** One template row; a clean ₹36,500 month unless overridden. */
    private function row(array $overrides = []): array
    {
        return array_merge([
            'employee_code' => 29, 'employee_name' => 'Kapil', 'working_days' => 29, 'payable_days' => 31,
            'leave_balance' => 3, 'monthly_salary' => 36500, 'gross_paid' => 36500, 'additional' => null,
            'pf' => 1000, 'esic' => null, 'late_deduction' => 100, 'net_salary' => 35400, 'remarks' => null,
        ], $overrides);
    }

    private function sheet(array ...$rows): array
    {
        return array_merge(
            [PayrollImportService::HEADERS],
            array_map(fn ($row) => array_map(fn ($header) => $row[$header] ?? null, PayrollImportService::HEADERS), $rows)
        );
    }

    private function review(array ...$rows): array
    {
        return $this->service->review($this->service->parse($this->sheet(...$rows)), self::MONTH);
    }

    public function test_old_salary_sheet_layout_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->parse([
            [null, 'Working', 'Old', 'Month', 'New Balance', 'After Adjustment', 'Pay', 'Salary'],
            ['Name', null, null, null, null, 'TILL Oct balance', null, null],
            ['Kapil', 35, 8.5, 3, 11.5, 9.5, 37, 36500],
        ]);
    }

    public function test_clean_row_saves_all_amounts_so_payslip_adds_up(): void
    {
        $review = $this->review($this->row());
        $this->assertSame(0, $review['error_count']);

        $this->assertSame(1, $this->service->commit($review, self::MONTH, 'oct.xlsx'));

        $payroll = Payroll::sole();
        $this->assertSame(self::MONTH, $payroll->month);
        $this->assertEquals(1000, $payroll->pf_deduction);
        $this->assertEquals(100, $payroll->other_deduction);
        $this->assertEquals(1100, $payroll->deductions);
        $this->assertEquals(35400, $payroll->net_salary);
        $this->assertEquals(36500, $payroll->monthly_salary);
        $this->assertEquals(2, $payroll->paid_leave_days);
        $this->assertSame('pending', $payroll->status);
    }

    public function test_additional_counts_towards_gross_and_net(): void
    {
        $review = $this->review($this->row(['additional' => 500, 'net_salary' => 35900]));
        $this->assertSame(0, $review['error_count']);

        $this->service->commit($review, self::MONTH, 'oct.xlsx');

        $this->assertEquals(37000, Payroll::sole()->gross_salary);
        $this->assertEquals(500, Payroll::sole()->variable_earning);
    }

    public function test_net_that_does_not_add_up_warns_and_saves_the_sheet_amount(): void
    {
        // The late deduction taken a second time, and a zero net, as in the October sheet.
        foreach ([35300, 0] as $net) {
            $review = $this->review($this->row(['net_salary' => $net]));
            $this->assertSame(0, $review['error_count']);
            $this->assertSame(1, $review['warning_count']);

            $this->service->commit($review, self::MONTH, 'oct.xlsx');
            $this->assertEquals($net, Payroll::sole()->net_salary);
        }

        // Small rounding differences don't warn.
        $this->assertSame(0, $this->review($this->row(['net_salary' => 35400.75]))['warning_count']);
    }

    public function test_unknown_duplicate_and_mismatched_codes_are_errors(): void
    {
        $this->assertSame(1, $this->review($this->row(['employee_code' => 9999]))['error_count']);
        $this->assertSame(1, $this->review($this->row(['employee_code' => '29abc']))['error_count']);
        $this->assertSame(1, $this->review($this->row(['employee_code' => null]))['error_count']);
        $this->assertSame(1, $this->review($this->row(['employee_name' => 'Prakash Sharma']))['error_count']);
        $this->assertSame(1, $this->review($this->row(), $this->row())['error_count']);
    }

    public function test_name_check_allows_short_forms_and_misspellings(): void
    {
        $this->employee('37', 'Mohammad Kaif', 8000);
        $this->employee('302', 'ujjwal singh mehra', 5000);

        $this->assertSame(0, $this->review($this->row(['employee_code' => 37, 'employee_name' => 'Moh. Kaif']))['error_count']);
        $this->assertSame(0, $this->review($this->row(['employee_code' => 302, 'employee_name' => 'Ujjawal']))['error_count']);
    }

    public function test_bad_numbers_are_errors(): void
    {
        $this->assertSame(1, $this->review($this->row(['gross_paid' => '36,500']))['error_count']);
        $this->assertSame(1, $this->review($this->row(['pf' => -5]))['error_count']);
        $this->assertSame(1, $this->review($this->row(['net_salary' => null]))['error_count']);
    }

    public function test_spacer_rows_are_ignored_and_unfilled_template_rows_skipped(): void
    {
        $blank = array_fill_keys(PayrollImportService::HEADERS, null);

        $review = $this->review(
            $this->row(),
            array_merge($blank, ['employee_code' => 57, 'employee_name' => 'Kapil menaria']),
            array_merge($blank, ['gross_paid' => 0, 'net_salary' => 0]),
        );

        $this->assertSame(0, $review['error_count']);
        $this->assertSame(1, $review['save_count']);
        $this->assertCount(2, $review['rows']);
    }

    public function test_row_with_money_but_no_employee_is_an_error_not_silently_dropped(): void
    {
        $this->assertSame(1, $this->review($this->row(['employee_code' => null, 'employee_name' => 'Total']))['error_count']);
    }

    public function test_pending_payroll_is_replaced_but_approved_and_paid_are_protected(): void
    {
        $employee = Employee::where('employee_code', '29')->first();
        $existing = Payroll::create(['employee_id' => $employee->id, 'month' => self::MONTH, 'net_salary' => 37, 'status' => 'pending']);

        $review = $this->review($this->row());
        $this->assertSame(1, $review['replace_count']);
        $this->service->commit($review, self::MONTH, 'oct.xlsx');
        $this->assertSame(1, Payroll::count());
        $this->assertEquals(35400, $existing->fresh()->net_salary);

        foreach (['approved', 'paid'] as $status) {
            $existing->update(['status' => $status, 'net_salary' => 1]);

            $review = $this->review($this->row());
            $this->assertSame(1, $review['error_count']);
            $this->assertEquals(1, $existing->fresh()->net_salary);
        }
    }

    public function test_commit_refuses_a_review_with_errors(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->commit($this->review($this->row(['employee_code' => 9999])), self::MONTH, 'oct.xlsx');
    }

    public function test_confirm_needs_the_right_token_and_saves_once(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'account_status' => 'active']);
        $rows = $this->service->parse($this->sheet($this->row()));

        $this->actingAs($admin)->withSession(['payroll_import' => [
            'token' => 'test-token', 'month' => self::MONTH, 'file_name' => 'oct.xlsx', 'rows' => $rows,
        ]]);

        $this->post(route('payroll.import.confirm'), ['token' => 'wrong'])->assertRedirect(route('payroll.index'));
        $this->assertSame(0, Payroll::count());

        $this->post(route('payroll.import.confirm'), ['token' => 'test-token'])->assertRedirect(route('payroll.index'));
        $this->assertSame(1, Payroll::count());
        $this->assertNull(session('payroll_import'));
    }

    public function test_employees_cannot_use_the_import(): void
    {
        $employee = User::factory()->create(['role' => 'employee', 'account_status' => 'active']);

        $this->actingAs($employee)->get(route('payroll.import.template'))->assertStatus(403);
    }
}
