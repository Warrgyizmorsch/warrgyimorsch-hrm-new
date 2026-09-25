<?php

namespace Tests\Feature\Hrms;

use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EmployeeDocumentTest extends TestCase
{
    use RefreshDatabase;

    private Department $department;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake(EmployeeDocument::DISK);
        $this->department = Department::create(['name' => 'Operations']);
    }

    private function makeUser(string $role, string $code): User
    {
        $employee = Employee::create([
            'name' => 'User ' . $code,
            'employee_code' => $code,
            'role' => $role,
            'department_id' => $this->department->id,
            'designation' => 'Staff',
            'password' => Hash::make('password'),
            'working_mode' => 'Office',
        ]);

        return User::create([
            'name' => $employee->name,
            'email' => strtolower($code) . '@example.test',
            'password' => 'password',
            'role' => $role,
            'employee_id' => $employee->id,
        ]);
    }

    private function updatePayload(Employee $employee, array $extra = []): array
    {
        return array_merge([
            'name' => $employee->name,
            'mobile_number' => '9999999999',
            'department_id' => $this->department->id,
            'designation' => 'Staff',
            'role' => $employee->role,
            'working_mode' => 'Office',
            'employee_code' => $employee->employee_code,
        ], $extra);
    }

    public function test_admin_upload_saves_documents_privately(): void
    {
        $admin = $this->makeUser('super_admin', 'ADM-1');
        $target = $this->makeUser('employee', 'EMP-1')->employee;

        $this->actingAs($admin)->put(route('employees.update', $target->id), $this->updatePayload($target, [
            'documents' => [
                'aadhaar' => UploadedFile::fake()->create('aadhaar.pdf', 200, 'application/pdf'),
                'offer_letter' => UploadedFile::fake()->create('offer.docx', 300),
            ],
        ]))->assertRedirect(route('employees.index'));

        $docs = EmployeeDocument::where('employee_id', $target->id)->get()->keyBy('type');
        $this->assertCount(2, $docs);
        $this->assertSame('aadhaar.pdf', $docs['aadhaar']->original_name);
        $this->assertSame($admin->id, $docs['aadhaar']->uploaded_by);
        Storage::disk(EmployeeDocument::DISK)->assertExists($docs['aadhaar']->file_path);
        $this->assertStringStartsWith("employee-documents/{$target->id}/", $docs['aadhaar']->file_path);
    }

    public function test_reupload_replaces_old_file(): void
    {
        $admin = $this->makeUser('hr_executive', 'ADM-2');
        $target = $this->makeUser('employee', 'EMP-2')->employee;

        $first = EmployeeDocument::storeFor($target, 'pan', UploadedFile::fake()->create('pan-old.pdf', 50, 'application/pdf'));
        $oldPath = $first->file_path;

        $this->actingAs($admin)->put(route('employees.update', $target->id), $this->updatePayload($target, [
            'documents' => ['pan' => UploadedFile::fake()->create('pan-new.pdf', 50, 'application/pdf')],
        ]));

        $this->assertSame(1, EmployeeDocument::where('employee_id', $target->id)->count());
        $doc = EmployeeDocument::where('employee_id', $target->id)->first();
        $this->assertSame('pan-new.pdf', $doc->original_name);
        Storage::disk(EmployeeDocument::DISK)->assertMissing($oldPath);
        Storage::disk(EmployeeDocument::DISK)->assertExists($doc->file_path);
    }

    public function test_invalid_file_is_rejected_with_field_error(): void
    {
        $admin = $this->makeUser('super_admin', 'ADM-3');
        $target = $this->makeUser('employee', 'EMP-3')->employee;

        $this->actingAs($admin)->put(route('employees.update', $target->id), $this->updatePayload($target, [
            'documents' => ['aadhaar' => UploadedFile::fake()->create('virus.exe', 10)],
        ]))->assertSessionHasErrors('documents.aadhaar');

        $this->assertSame(0, EmployeeDocument::count());
    }

    public function test_employee_can_open_own_document_but_not_others(): void
    {
        $me = $this->makeUser('employee', 'EMP-4');
        $other = $this->makeUser('employee', 'EMP-5');

        $mine = EmployeeDocument::storeFor($me->employee, 'aadhaar', UploadedFile::fake()->create('a.pdf', 10, 'application/pdf'));
        $theirs = EmployeeDocument::storeFor($other->employee, 'aadhaar', UploadedFile::fake()->create('b.pdf', 10, 'application/pdf'));

        $this->actingAs($me)->get(route('employee-documents.download', $mine->id))->assertOk();
        $this->actingAs($me)->get(route('employee-documents.download', $theirs->id))->assertForbidden();
    }

    public function test_admin_can_open_any_document_and_employee_cannot_delete(): void
    {
        $admin = $this->makeUser('manager', 'ADM-6');
        $emp = $this->makeUser('employee', 'EMP-6');
        $doc = EmployeeDocument::storeFor($emp->employee, 'pan', UploadedFile::fake()->create('p.pdf', 10, 'application/pdf'));

        $this->actingAs($admin)->get(route('employee-documents.download', $doc->id))->assertOk();

        $this->actingAs($emp)->delete(route('employee-documents.destroy', $doc->id));
        $this->assertNotNull($doc->fresh(), 'Employee must not be able to delete documents');

        $this->actingAs($admin)->delete(route('employee-documents.destroy', $doc->id))->assertRedirect();
        $this->assertNull($doc->fresh());
        Storage::disk(EmployeeDocument::DISK)->assertMissing($doc->file_path);
    }

    public function test_list_drawer_json_includes_documents(): void
    {
        $admin = $this->makeUser('manager', 'ADM-8');
        $emp = $this->makeUser('employee', 'EMP-8');
        $doc = EmployeeDocument::storeFor($emp->employee, 'pan', UploadedFile::fake()->create('pan.pdf', 10, 'application/pdf'));

        $list = $this->actingAs($admin)->getJson("/api/employees/{$emp->employee->id}")
            ->assertOk()
            ->json('document_list');

        $this->assertCount(count(EmployeeDocument::TYPES), $list);
        $pan = collect($list)->firstWhere('label', 'PAN Card');
        $this->assertSame('pan.pdf', $pan['name']);
        $this->assertSame(route('employee-documents.download', $doc->id), $pan['url']);
        $this->assertNull(collect($list)->firstWhere('label', 'Aadhaar Card')['url']);
    }

    public function test_guest_cannot_download(): void
    {
        $emp = $this->makeUser('employee', 'EMP-7');
        $doc = EmployeeDocument::storeFor($emp->employee, 'pan', UploadedFile::fake()->create('p.pdf', 10, 'application/pdf'));

        $this->get(route('employee-documents.download', $doc->id))->assertRedirect(route('login'));
    }
}
