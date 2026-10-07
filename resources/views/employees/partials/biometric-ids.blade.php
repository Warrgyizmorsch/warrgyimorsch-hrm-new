{{--
    "Biometric IDs" — this employee's user ID on each attendance machine. Each machine numbers
    its own users, so the same number can belong to different people on different machines.
    Expects optional $employee (edit form).
--}}
@php
    $bioMachines = \App\Models\BiometricEnrollment::machines();
    $bioRows = old('biometric') ?? (isset($employee)
        ? $employee->biometricEnrollments->map(fn ($e) => ['machine' => $e->machine, 'device_user_id' => $e->device_user_id])->all()
        : []);
    $bioRows = array_values(array_filter((array) $bioRows, fn ($r) => ($r['device_user_id'] ?? '') !== '' || ($r['machine'] ?? '') !== ''));
    if ($bioRows === []) {
        $bioRows[] = ['machine' => array_key_first($bioMachines), 'device_user_id' => ''];
    }
    $bioUnmapped = \App\Models\BiometricEnrollment::unmappedSummary();
@endphp

<div class="hrm-field" style="grid-column: 1 / -1;">
    <label>Biometric IDs</label>
    <div id="biometricRows" class="d-flex flex-column gap-2">
        @foreach($bioRows as $i => $row)
            <div class="d-flex gap-2 align-items-center biometric-row">
                <select name="biometric[{{ $i }}][machine]" class="form-select" style="max-width: 200px;">
                    @foreach($bioMachines as $key => $label)
                        <option value="{{ $key }}" @selected(($row['machine'] ?? '') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                <div class="hrm-input-wrap flex-grow-1"><i class="bi bi-fingerprint"></i>
                    <input type="text" name="biometric[{{ $i }}][device_user_id]" class="form-control"
                        placeholder="User ID shown on that machine" value="{{ $row['device_user_id'] ?? '' }}" maxlength="50">
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="biometricRemoveRow(this)" title="Remove">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        @endforeach
    </div>
    <button type="button" class="btn btn-sm btn-link px-0 mt-1" onclick="biometricAddRow()">
        <i class="bi bi-plus-lg"></i> Add another machine
    </button>

    @error('biometric')
        @foreach($errors->get('biometric') as $message)
            <div class="text-danger small fw-semibold">{{ $message }}</div>
        @endforeach
    @enderror

    <div class="form-text">
        Enter the ID the person has <strong>on each machine they punch on</strong>. The same number can be used on
        different machines — it only has to be unique within one machine. Employee Code is the HR/payroll number
        and is not used for matching punches.
    </div>

    @if($bioUnmapped->isNotEmpty())
        <div class="mt-2 small">
            <span class="text-muted">Punching but not linked to anyone yet (click to use):</span>
            <div class="d-flex flex-wrap gap-1 mt-1">
                @foreach($bioUnmapped as $u)
                    <button type="button" class="badge rounded-pill border-0 bg-warning-subtle text-warning-emphasis"
                        onclick="biometricUse('{{ e($u->machine) }}', '{{ e($u->device_user_id) }}')"
                        title="{{ $u->punches }} punch(es), last {{ \Carbon\Carbon::parse($u->last_punch)->format('d M, h:i A') }}">
                        {{ $bioMachines[$u->machine] ?? $u->machine }} · ID {{ $u->device_user_id }} ({{ $u->punches }})
                    </button>
                @endforeach
            </div>
        </div>
    @endif
</div>

<template id="biometricRowTemplate">
    <div class="d-flex gap-2 align-items-center biometric-row">
        <select data-name="machine" class="form-select" style="max-width: 200px;">
            @foreach($bioMachines as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
            @endforeach
        </select>
        <div class="hrm-input-wrap flex-grow-1"><i class="bi bi-fingerprint"></i>
            <input type="text" data-name="device_user_id" class="form-control" placeholder="User ID shown on that machine" maxlength="50">
        </div>
        <button type="button" class="btn btn-sm btn-outline-danger" onclick="biometricRemoveRow(this)" title="Remove">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
</template>

<script>
    function biometricAddRow(machine = null, id = '') {
        const container = document.getElementById('biometricRows');
        const index = Date.now() + container.children.length;
        const row = document.getElementById('biometricRowTemplate').content.firstElementChild.cloneNode(true);
        row.querySelectorAll('[data-name]').forEach(el => el.name = `biometric[${index}][${el.dataset.name}]`);
        if (machine) row.querySelector('select').value = machine;
        row.querySelector('input').value = id;
        container.appendChild(row);
        return row;
    }

    function biometricRemoveRow(button) {
        const container = document.getElementById('biometricRows');
        const row = button.closest('.biometric-row');
        if (container.children.length > 1) {
            row.remove();
        } else {
            row.querySelector('input').value = '';
        }
    }

    // Fill the unmapped ID into this machine's row (or an empty row, or a new one).
    function biometricUse(machine, id) {
        const rows = [...document.querySelectorAll('#biometricRows .biometric-row')];
        const target = rows.find(r => r.querySelector('select').value === machine)
            || rows.find(r => !r.querySelector('input').value);
        if (target) {
            target.querySelector('select').value = machine;
            target.querySelector('input').value = id;
        } else {
            biometricAddRow(machine, id);
        }
    }
</script>
