<?php

namespace Tests\Feature\Hrms;

use App\Models\Asset;
use App\Models\AssetAudit;
use App\Models\AssetEvent;
use App\Models\AssetIncident;
use App\Models\AssetRequest;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Asset lifecycle: register -> allocate -> acknowledge -> transfer -> damage / loss -> repair /
 * found / write-off -> retire, plus audits, permissions and the pages themselves.
 *
 * The project's migrations use MySQL-only SQL, so run this against MySQL/MariaDB, e.g.
 *   DB_CONNECTION=mysql DB_DATABASE=hrm_test php artisan test --filter=AssetManagementTest
 */
class AssetManagementTest extends TestCase
{
    use RefreshDatabase;

    private Department $department;
    private User $admin;
    private User $alice;
    private User $bob;

    protected function setUp(): void
    {
        parent::setUp();
        $this->department = Department::create(['name' => 'Engineering']);
        $this->admin = $this->makeUser('hr_executive', 'HR1');
        $this->alice = $this->makeUser('employee', 'E1');
        $this->bob = $this->makeUser('employee', 'E2');
    }

    private function makeUser(string $role, string $code, ?Department $department = null): User
    {
        $employee = Employee::create([
            'name' => 'User ' . $code,
            'employee_code' => $code,
            'role' => $role,
            'department_id' => ($department ?? $this->department)->id,
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
            'account_status' => 'active',
        ]);
    }

    private function laptop(array $extra = []): Asset
    {
        return Asset::create(array_merge(['name' => 'Dell Latitude', 'type' => 'Laptop', 'status' => 'Available'], $extra));
    }

    public function test_assets_get_sequential_tags_per_type(): void
    {
        $a = $this->laptop();
        $b = $this->laptop();
        $pc = Asset::create(['name' => 'Office PC', 'type' => 'PC', 'status' => 'Available']);

        $this->assertSame('WM-LT-0001', $a->asset_tag);
        $this->assertSame('WM-LT-0002', $b->asset_tag);
        $this->assertSame('WM-PC-0001', $pc->asset_tag);
        $this->assertTrue(AssetEvent::where('asset_id', $a->id)->where('event', 'created')->exists());
    }

    public function test_allocation_waits_for_employee_acknowledgement(): void
    {
        $asset = $this->laptop();

        $this->actingAs($this->admin)
            ->post(route('assets.allocateManual', $asset->id), ['user_id' => $this->alice->id, 'condition' => 'New'])
            ->assertSessionHas('success');

        $allocation = $asset->fresh()->activeAllocation;
        $this->assertSame('Allocated', $asset->fresh()->status);
        $this->assertSame(AssetRequest::ACK_PENDING, $allocation->acknowledgement_status);
        $this->assertSame($this->admin->id, $allocation->allocated_by);
        $this->assertSame('New', $allocation->condition_on_allocation);

        // Bob can't confirm Alice's device.
        $this->actingAs($this->bob)
            ->post(route('assets.acknowledge', $allocation->id), ['decision' => 'accept'])
            ->assertForbidden();

        // Disputing needs a note.
        $this->actingAs($this->alice)
            ->post(route('assets.acknowledge', $allocation->id), ['decision' => 'dispute'])
            ->assertSessionHas('error');

        $this->actingAs($this->alice)
            ->post(route('assets.acknowledge', $allocation->id), ['decision' => 'accept'])
            ->assertSessionHas('success');

        $this->assertSame(AssetRequest::ACK_ACCEPTED, $allocation->fresh()->acknowledgement_status);
        $this->assertTrue(AssetEvent::where('asset_id', $asset->id)->where('event', 'acknowledged')->where('performed_by', $this->alice->id)->exists());
    }

    public function test_transfer_closes_old_allocation_and_opens_new_one(): void
    {
        $asset = $this->laptop();
        $this->actingAs($this->admin)->post(route('assets.allocateManual', $asset->id), ['user_id' => $this->alice->id]);
        $first = $asset->fresh()->activeAllocation;

        $this->actingAs($this->admin)
            ->post(route('assets.transfer', $asset->id), ['to_user_id' => $this->bob->id, 'reason' => 'Project change', 'condition' => 'Fair'])
            ->assertSessionHas('success');

        $first->refresh();
        $current = $asset->fresh()->activeAllocation;

        $this->assertSame('Returned', $first->status);
        $this->assertSame($this->admin->id, $first->returned_by);
        $this->assertSame('Fair', $first->condition_on_return);
        $this->assertSame($this->bob->id, $current->user_id);
        $this->assertSame(AssetRequest::ACK_PENDING, $current->acknowledgement_status);
        $this->assertSame('Allocated', $asset->fresh()->status);
        $this->assertSame('Fair', $asset->fresh()->condition);
        $this->assertSame(1, AssetRequest::where('asset_id', $asset->id)->where('status', 'Allocated')->count());

        $event = AssetEvent::where('asset_id', $asset->id)->where('event', 'transferred')->first();
        $this->assertSame($this->alice->id, $event->from_user_id);
        $this->assertSame($this->bob->id, $event->to_user_id);
        $this->assertSame('User E1', $event->meta['from_name']);
    }

    public function test_team_leader_can_transfer_only_within_their_team(): void
    {
        $other = Department::create(['name' => 'Sales']);
        $outsider = $this->makeUser('employee', 'S1', $other);
        $tl = $this->makeUser('team_leader', 'TL1');

        $asset = $this->laptop();
        $this->actingAs($this->admin)->post(route('assets.allocateManual', $asset->id), ['user_id' => $this->alice->id]);

        $this->actingAs($tl)
            ->post(route('assets.transfer', $asset->id), ['to_user_id' => $outsider->id, 'reason' => 'x'])
            ->assertForbidden();

        $this->actingAs($tl)
            ->post(route('assets.transfer', $asset->id), ['to_user_id' => $this->bob->id, 'reason' => 'Swap'])
            ->assertSessionHas('success');

        $this->assertSame($this->bob->id, $asset->fresh()->activeAllocation->user_id);
        $this->actingAs($tl)->get(route('assets.show', $asset->id))->assertOk();
        $this->actingAs($tl)->get(route('assets.dashboard'))->assertForbidden();
    }

    public function test_employee_reports_loss_and_it_is_found_later(): void
    {
        $asset = $this->laptop(['purchase_cost' => 50000]);
        $this->actingAs($this->admin)->post(route('assets.allocateManual', $asset->id), ['user_id' => $this->alice->id]);

        // Only the holder can report.
        $this->actingAs($this->bob)
            ->post(route('assets.incidents.report'), ['asset_id' => $asset->id, 'type' => 'lost', 'incident_date' => now()->toDateString(), 'description' => 'x'])
            ->assertForbidden();

        $this->actingAs($this->alice)
            ->post(route('assets.incidents.report'), [
                'asset_id' => $asset->id, 'type' => 'lost',
                'incident_date' => now()->toDateString(), 'description' => 'Left in a cab',
            ])->assertSessionHas('success');

        $incident = AssetIncident::firstOrFail();
        $this->assertSame('Lost', $asset->fresh()->status);
        $this->assertNull($asset->fresh()->activeAllocation);
        $this->assertSame('Lost', AssetRequest::where('asset_id', $asset->id)->latest('id')->value('status'));
        $this->assertSame($this->alice->id, $incident->user_id);
        $this->assertSame('User E1', $incident->user_name);

        // Can't be allocated or edited back to Available while lost.
        $this->actingAs($this->admin)
            ->post(route('assets.allocateManual', $asset->id), ['user_id' => $this->bob->id])
            ->assertSessionHas('error');
        $this->actingAs($this->admin)
            ->put(route('assets.update', $asset->id), ['name' => 'Dell Latitude', 'type' => 'Laptop', 'status' => 'Available'])
            ->assertSessionHas('error');

        $this->actingAs($this->admin)
            ->post(route('assets.incidents.resolve', $incident), [
                'resolution' => 'found', 'condition' => 'Good', 'recovery_status' => 'none',
            ])->assertSessionHas('success');

        $this->assertSame('Available', $asset->fresh()->status);
        $this->assertSame('closed', $incident->fresh()->status);
        $this->assertTrue(AssetEvent::where('asset_id', $asset->id)->where('event', 'found')->exists());
    }

    public function test_damage_repair_and_recovery(): void
    {
        $asset = $this->laptop();
        $this->actingAs($this->admin)->post(route('assets.allocateManual', $asset->id), ['user_id' => $this->alice->id]);

        $this->actingAs($this->admin)
            ->post(route('assets.incidents.store'), [
                'asset_id' => $asset->id, 'type' => 'damage', 'incident_date' => now()->toDateString(),
                'description' => 'Screen cracked', 'send_to_repair' => 1,
            ])->assertSessionHas('success');

        $incident = AssetIncident::firstOrFail();
        $this->assertSame('in_repair', $incident->status);
        $this->assertSame('Maintenance', $asset->fresh()->status);
        $this->assertSame('Damaged', $asset->fresh()->condition);
        $this->assertNull($asset->fresh()->activeAllocation);

        // Recovered more than owed -> rejected.
        $this->actingAs($this->admin)
            ->post(route('assets.incidents.recovery', $incident), [
                'cost' => 8000, 'recovery_status' => 'pending', 'recovery_amount' => 4000, 'recovered_amount' => 5000,
            ])->assertSessionHas('error');

        $this->actingAs($this->admin)
            ->post(route('assets.incidents.recovery', $incident), [
                'cost' => 8000, 'recovery_status' => 'pending', 'recovery_amount' => 4000, 'recovered_amount' => 1000,
            ])->assertSessionHas('success');
        $this->assertEquals(3000, $incident->fresh()->outstanding_amount);

        $this->actingAs($this->admin)
            ->post(route('assets.incidents.resolve', $incident), [
                'resolution' => 'repaired', 'recovery_status' => 'recovered', 'recovery_amount' => 4000, 'cost' => 8000,
            ])->assertSessionHas('success');

        $incident->refresh();
        $this->assertSame('closed', $incident->status);
        $this->assertEquals(4000, (float) $incident->recovered_amount);
        $this->assertNotNull($incident->recovered_at);
        $this->assertSame('Available', $asset->fresh()->status);
        $this->assertSame('Good', $asset->fresh()->condition);
    }

    public function test_write_off_retires_and_reactivate_brings_back(): void
    {
        $asset = $this->laptop();
        $this->actingAs($this->admin)->post(route('assets.allocateManual', $asset->id), ['user_id' => $this->alice->id]);
        $this->actingAs($this->admin)->post(route('assets.incidents.store'), [
            'asset_id' => $asset->id, 'type' => 'damage', 'incident_date' => now()->toDateString(), 'description' => 'Water damage',
        ]);
        $incident = AssetIncident::firstOrFail();
        $this->assertSame('Allocated', $asset->fresh()->status, 'damage without repair keeps the holder');

        $this->actingAs($this->admin)
            ->post(route('assets.incidents.resolve', $incident), ['resolution' => 'written_off', 'recovery_status' => 'waived', 'recovery_amount' => 2000])
            ->assertSessionHas('success');

        $this->assertSame('Retired', $asset->fresh()->status);
        $this->assertNull($asset->fresh()->activeAllocation);

        // Retire requires no holder; retired asset can't be retired again.
        $this->actingAs($this->admin)->post(route('assets.retire', $asset->id), ['reason' => 'x'])->assertSessionHas('error');

        $this->actingAs($this->admin)->post(route('assets.reactivate', $asset->id), ['note' => 'Fixed by vendor'])->assertSessionHas('success');
        $this->assertSame('Available', $asset->fresh()->status);
    }

    public function test_audit_raises_cases_for_missing_and_damaged(): void
    {
        $missing = $this->laptop();
        $damaged = $this->laptop();
        $fine = $this->laptop();
        $this->actingAs($this->admin)->post(route('assets.allocateManual', $missing->id), ['user_id' => $this->alice->id]);

        $this->actingAs($this->admin)
            ->post(route('assets.audits.store'), ['title' => 'Q4 audit'])
            ->assertRedirect();
        $audit = AssetAudit::firstOrFail();
        $items = $audit->items()->get()->keyBy('asset_id');
        $this->assertCount(3, $items);
        $this->assertSame($this->alice->id, $items[$missing->id]->expected_user_id);

        $this->actingAs($this->admin)->post(route('assets.audits.record', $audit), ['items' => [
            $items[$missing->id]->id => ['result' => 'missing', 'notes' => 'Not at desk'],
            $items[$damaged->id]->id => ['result' => 'damaged'],
            $items[$fine->id]->id => ['result' => 'found'],
        ]])->assertSessionHas('success');

        $this->actingAs($this->admin)->get(route('assets.audits.show', $audit))->assertOk()->assertSee('Q4 audit');

        $this->actingAs($this->admin)
            ->post(route('assets.audits.complete', $audit), ['raise_incidents' => 1])
            ->assertSessionHas('success');

        $this->assertSame('completed', $audit->fresh()->status);
        $this->assertSame('Lost', $missing->fresh()->status);
        $this->assertSame(1, AssetIncident::where('asset_id', $missing->id)->where('type', 'lost')->count());
        $this->assertSame(1, AssetIncident::where('asset_id', $damaged->id)->where('type', 'damage')->count());
        $this->assertSame(0, AssetIncident::where('asset_id', $fine->id)->count());
        $this->assertSame(3, AssetEvent::where('event', 'audited')->count());

        // Closed audits can't be edited.
        $this->actingAs($this->admin)->post(route('assets.audits.record', $audit), ['items' => [
            $items[$fine->id]->id => ['result' => 'missing'],
        ]])->assertSessionHas('error');
    }

    public function test_pages_render_and_are_protected(): void
    {
        $asset = $this->laptop([
            'brand' => 'Dell', 'purchase_cost' => 65000, 'purchase_date' => now()->subYear()->toDateString(),
            'warranty_expiry' => now()->addDays(20)->toDateString(), 'location' => 'Floor 2',
        ]);
        $this->actingAs($this->admin)->post(route('assets.allocateManual', $asset->id), ['user_id' => $this->alice->id]);
        $this->actingAs($this->alice)->post(route('assets.incidents.report'), [
            'asset_id' => $asset->id, 'type' => 'damage', 'incident_date' => now()->toDateString(), 'description' => 'Hinge loose',
        ]);

        $this->actingAs($this->admin)->get(route('assets.dashboard'))->assertOk()->assertSee($asset->asset_tag);
        $this->actingAs($this->admin)->get(route('assets.index'))->assertOk()->assertSee($asset->asset_tag);
        $this->actingAs($this->admin)->get(route('assets.show', $asset->id))->assertOk()->assertSee('Hinge loose');
        $this->actingAs($this->admin)->get(route('assets.incidents.index'))->assertOk()->assertSee(AssetIncident::firstOrFail()->reference);
        $this->actingAs($this->admin)->get(route('assets.audits.index'))->assertOk();

        $csv = $this->actingAs($this->admin)->get(route('assets.export'));
        $csv->assertOk();
        $this->assertStringContainsString($asset->asset_tag, $csv->streamedContent());

        $this->actingAs($this->alice)->get(route('assets.employee'))->assertOk()->assertSee('Confirm receipt');
        $this->actingAs($this->alice)->get(route('assets.guide'))->assertOk()->assertSee('Confirm receipt')->assertDontSee('Getting started');
        $this->actingAs($this->admin)->get(route('assets.guide'))->assertOk()->assertSee('Getting started')->assertSee('Audits');
        $this->actingAs($this->alice)->get(route('assets.dashboard'))->assertForbidden();
        $this->actingAs($this->alice)->get(route('assets.show', $asset->id))->assertForbidden();
        $this->actingAs($this->alice)->get(route('assets.incidents.index'))->assertForbidden();
    }

    public function test_add_form_saves_register_fields_and_details_edit_is_logged(): void
    {
        $this->actingAs($this->admin)->post(route('assets.store'), [
            'assets' => [[
                'name' => 'ThinkPad E14', 'type' => 'Laptop', 'status' => 'Available', 'serial_number' => 'SN-1',
                'brand' => 'Lenovo', 'model' => 'E14 G5', 'purchase_date' => '2026-01-15', 'purchase_cost' => '72000',
                'vendor' => 'Croma', 'invoice_number' => 'INV-77', 'warranty_expiry' => '2029-01-14', 'location' => 'HQ',
            ]],
        ])->assertSessionHas('success');

        $asset = Asset::where('serial_number', 'SN-1')->firstOrFail();
        $this->assertSame('Lenovo', $asset->brand);
        $this->assertEquals(72000, (float) $asset->purchase_cost);
        $this->assertSame('2029-01-14', $asset->warranty_expiry->toDateString());

        $this->actingAs($this->admin)->post(route('assets.details.update', $asset->id), [
            'name' => 'ThinkPad E14', 'type' => 'Laptop', 'serial_number' => 'SN-1', 'location' => 'Branch office',
        ])->assertSessionHas('success');

        $event = AssetEvent::where('asset_id', $asset->id)->where('event', 'details_updated')->latest('id')->first();
        $this->assertSame(['from' => 'HQ', 'to' => 'Branch office'], $event->meta['changes']['location']);
    }
}
