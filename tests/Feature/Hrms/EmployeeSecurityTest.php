<?php

namespace Tests\Feature\Hrms;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Audit-driven tests for the Employee management (HRMS) module.
 *
 * Several of these encode the CORRECT/expected behavior and currently FAIL against
 * app\Http\Controllers\EmployeeController.php — that failure is the finding, not a
 * test bug. See the audit report for severity and suggested fixes. Do not weaken
 * these assertions to make them pass; fix the controller instead.
 */
class EmployeeSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function actingAdmin(string $role): User
    {
        $employee = Employee::create([
            'name' => 'Actor ' . $role,
            'employee_code' => 'ACT-' . strtoupper($role),
            'role' => $role,
            'department' => 'Operations',
            'designation' => 'Staff',
            'password' => Hash::make('password'),
            'working_mode' => 'Office',
        ]);

        return User::create([
            'name' => $employee->name,
            'email' => strtolower($role) . '@example.test',
            'password' => 'password',
            'role' => $role,
            'employee_id' => $employee->id,
        ]);
    }

    private function targetEmployee(): Employee
    {
        return Employee::create([
            'name' => 'Target Employee',
            'employee_code' => 'EMP-TARGET',
            'role' => 'employee',
            'department' => 'Operations',
            'designation' => 'Staff',
            'password' => Hash::make('OriginalPass123'),
            'working_mode' => 'Office',
        ]);
    }

    private function validUpdatePayload(Employee $employee, array $overrides = []): array
    {
        return array_merge([
            'name' => $employee->name,
            'mobile_number' => '9999999999',
            'department' => $employee->department,
            'designation' => $employee->designation,
            'role' => $employee->role,
            'working_mode' => 'Office',
        ], $overrides);
    }

    /**
     * FINDING (CRITICAL — privilege escalation): any of the five "admin-tier" roles
     * (super_admin, manager, hr_executive, hr_intern, business_operation_head) can hit
     * PUT /employees/{id} and set role=super_admin with no restriction — the validation
     * only requires `role` to be a string, and RoleAccess authorizes purely by matching
     * that free-text column against a hardcoded per-route allow-list. hr_intern is the
     * lowest-privileged role in that group and should not be able to mint a super_admin.
     */
    public function test_hr_intern_cannot_escalate_an_employee_to_super_admin(): void
    {
        $actor = $this->actingAdmin('hr_intern');
        $employee = $this->targetEmployee();

        $this->actingAs($actor)->put(
            "/employees/{$employee->id}",
            $this->validUpdatePayload($employee, ['role' => 'super_admin'])
        );

        $employee->refresh();

        $this->assertNotEquals(
            'super_admin',
            strtolower((string) $employee->role),
            'hr_intern was able to set an employee\'s role to super_admin — unrestricted role assignment allows privilege escalation.'
        );
    }

    /**
     * FINDING (CRITICAL — plaintext password persisted): EmployeeController::update()
     * assigns $request->password directly to the Employee model with no hashing
     * (unlike store(), and unlike the linked User row in the same method), so editing
     * an employee's password leaves employees.password holding the raw plaintext value.
     */
    public function test_updating_employee_password_stores_a_hash_not_plaintext(): void
    {
        $actor = $this->actingAdmin('manager');
        $employee = $this->targetEmployee();

        $this->actingAs($actor)->put(
            "/employees/{$employee->id}",
            $this->validUpdatePayload($employee, ['password' => 'BrandNewPassword1'])
        );

        $employee->refresh();
        $stored = (string) $employee->password;
        $looksHashed = (bool) preg_match('/^\$2[aby]\$/', $stored);

        $this->assertTrue(
            $looksHashed,
            "employees.password holds the raw plaintext value ('{$stored}') after update() instead of a bcrypt hash — plaintext passwords are being persisted to the database."
        );

        if ($looksHashed) {
            $this->assertTrue(Hash::check('BrandNewPassword1', $stored));
        }
    }

    /**
     * FINDING (HIGH — information disclosure): Employee has no $hidden array, so the
     * password column (hash today, plaintext after an edit — see above) is serialized
     * straight into the admin-facing JSON payload used by the employee-details modal.
     */
    public function test_employee_json_endpoint_does_not_expose_the_password_field(): void
    {
        $actor = $this->actingAdmin('manager');
        $employee = $this->targetEmployee();

        $response = $this->actingAs($actor)->get("/api/employees/{$employee->id}");

        $response->assertStatus(200);
        $this->assertArrayNotHasKey(
            'password',
            $response->json(),
            'GET /api/employees/{id} exposes the password column (hash or plaintext) to any admin-tier caller.'
        );
    }

    /**
     * FINDING (HIGH — stale privileged access): EmployeeController::destroy() only
     * deletes the Employee row. It never touches the linked User account, so a "removed"
     * employee's login stays fully active (account_status untouched) and can keep
     * authenticating and using the app after being deleted from the roster.
     */
    public function test_deleting_an_employee_revokes_the_linked_login(): void
    {
        $actor = $this->actingAdmin('super_admin');
        $employee = $this->targetEmployee();
        $linkedUser = User::create([
            'name' => $employee->name,
            'email' => 'target@example.test',
            'password' => 'password',
            'role' => 'employee',
            'employee_id' => $employee->id,
        ]);

        $this->actingAs($actor)->delete("/employees/{$employee->id}");

        $freshUser = User::find($linkedUser->id);

        $this->assertTrue(
            $freshUser === null || $freshUser->account_status === 'inactive',
            'Deleting an employee left their linked User account active — the login was not revoked.'
        );
    }

    /**
     * Locks in correct existing behavior: a non-admin role is blocked from the
     * employee roster entirely (RoleAccess middleware), not just IDOR-blocked per record.
     */
    public function test_non_admin_role_cannot_list_employees(): void
    {
        $actor = $this->actingAdmin('team_leader');

        $response = $this->actingAs($actor)->get('/employees');

        $response->assertStatus(403);
    }

    /**
     * Locks in correct existing behavior: EmployeeController::getAttendance() correctly
     * scopes a non-admin caller to their own employee_id even when no {id} route
     * parameter is supplied (the self-service /attendance-history route).
     */
    public function test_employee_self_service_attendance_defaults_to_own_record_only(): void
    {
        $employee = $this->targetEmployee();
        $otherEmployee = Employee::create([
            'name' => 'Other Employee',
            'employee_code' => 'EMP-OTHER',
            'role' => 'employee',
            'department' => 'Operations',
            'designation' => 'Staff',
            'working_mode' => 'Office',
        ]);

        $user = User::create([
            'name' => $employee->name,
            'email' => 'self@example.test',
            'password' => 'password',
            'role' => 'employee',
            'employee_id' => $employee->id,
        ]);

        // Self-service route has no {id} param, so it must resolve to the caller's own record.
        $response = $this->actingAs($user)->get('/attendance-history');

        $response->assertStatus(200);
        $response->assertViewHas('employee', function ($viewEmployee) use ($employee) {
            return $viewEmployee->id === $employee->id;
        });

        // And the admin-only-by-route id-based endpoint must reject a non-admin caller
        // outright, even for a plausible-looking other employee id.
        $this->actingAs($user)
            ->get("/api/employees/{$otherEmployee->id}/attendance")
            ->assertStatus(403);
    }
}
