@extends('layouts.app')

@section('content')

    <div class="page-header d-flex align-items-center">
        <div class="page-header-left">
            <div class="page-header-title">
                <h5 class="m-b-10">Payroll Module</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/dashboard">Home</a></li>
                <li class="breadcrumb-item">Edit Attendance By Employee</li>
            </ul>
        </div>
    </div>

    <div class="px-2 px-md-4 pt-3 pt-md-4">
        <div class="card border-0 shadow-sm" style="border-radius: 12px; background: white;">
            <div class="card-body p-3 p-md-4">

            <form action="{{ route('payroll.attendance.employee.updateByName', $employee->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row g-3 mb-4">
                    <!-- Employee Name -->
                    <div class="col-12 col-md-8 col-lg-6">
                        <label class="form-label fw-bold text-dark mb-2">Employee Name:</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="feather-user"></i></span>
                            <input type="text" value="{{ $employee->name }}" readonly
                                class="form-control bg-light border-start-0 fw-bold" style="height: 44px;">
                        </div>
                        <small class="text-danger mt-1 d-block"><i class="feather-info me-1"></i>Employee name cannot be
                            changed.</small>
                    </div>

                    <!-- Right: Bulk Status Dropdown -->
                    <div class="col-12 col-md-4 col-lg-3 ms-md-auto">
                        <label class="form-label fw-bold text-dark mb-2">Bulk Apply Status:</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="feather-check-circle"></i></span>
                            <select id="bulk_status" onchange="applyBulkStatus()" class="form-select border-start-0"
                                style="height: 44px; cursor: pointer;">
                                <option value="">Select Status</option>
                                <option value="present">Present</option>
                                <option value="absent">Absent</option>
                                <option value="half_day">Half Day</option>
                                <option value="wfh">WFH</option>
                                <option value="leave">Leave</option>
                                <option value="late">Late</option>
                                <option value="activity">Activity</option>
                                <option value="early_leave">Early Leave</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Table -->
                <!-- Desktop Table -->
                <div class="d-none d-lg-block">
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                            <thead>
                                <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                                    <th style="padding: 15px; text-align: center; border-right: 1px solid #e2e8f0;">
                                        <input type="checkbox" id="select_all" onclick="toggleAllCheckboxes(this)"
                                            class="form-check-input">
                                    </th>
                                    <th
                                        style="padding: 15px; text-align: left; border-right: 1px solid #e2e8f0; font-weight: bold; color: #475569;">
                                        Attendance Date
                                    </th>
                                    <th
                                        style="padding: 15px; text-align: center; border-right: 1px solid #e2e8f0; font-weight: bold; width: 140px; color: #475569;">
                                        Check In
                                    </th>
                                    <th
                                        style="padding: 15px; text-align: center; border-right: 1px solid #e2e8f0; font-weight: bold; width: 140px; color: #475569;">
                                        Check Out
                                    </th>
                                    <th
                                        style="padding: 15px; text-align: center; border-right: 1px solid #e2e8f0; font-weight: bold; width: 110px; color: #475569;">
                                        Total Time
                                    </th>
                                    <th
                                        style="padding: 15px; text-align: left; font-weight: bold; width: 130px; color: #475569;">
                                        Status
                                    </th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach($attendance as $index => $record)
                                    <tr
                                        style="border-bottom: 1px solid #e2e8f0; background: {{ $loop->even ? '#fbfcfe' : 'white' }};">
                                        <!-- Checkbox -->
                                        <td style="padding: 15px; text-align: center; border-right: 1px solid #e2e8f0;">
                                            <input type="checkbox" class="row_checkbox form-check-input"
                                                data-index="{{ $index }}">
                                        </td>
                                        <!-- Date -->
                                        <td
                                            style="padding: 15px; border-right: 1px solid #e2e8f0; font-weight: 600; color: #334155;">
                                            {{ \Carbon\Carbon::parse($record->attendance_date)->format('d-m-Y') }}
                                            <input type="hidden" name="attendance_ids[]" value="{{ $record->id }}">
                                        </td>

                                        <!-- Check In -->
                                        <td style="padding: 15px; border-right: 1px solid #e2e8f0; text-align: center;">
                                            <div style="margin-bottom: 8px;">
                                                <input type="time" id="check_in_{{ $index }}" name="check_in[{{ $record->id }}]"
                                                    value="{{ $record->check_in ? \Carbon\Carbon::parse($record->check_in)->format('H:i') : '' }}"
                                                    class="form-control form-control-sm text-center mx-auto"
                                                    style="max-width: 100px;" onchange="syncTime({{ $index }}, 'in', 'desktop')">
                                            </div>

                                            <label class="small text-primary cursor-pointer d-block">
                                                <input type="checkbox"
                                                    onchange="setNowTime('check_in_{{ $index }}', {{ $index }})"
                                                    class="form-check-input me-1">
                                                <span>Set Now</span>
                                            </label>
                                        </td>

                                        <!-- Check Out -->
                                        <td style="padding: 15px; border-right: 1px solid #e2e8f0; text-align: center;">
                                            <div style="margin-bottom: 8px;">
                                                <input type="time" id="check_out_{{ $index }}"
                                                    name="check_out[{{ $record->id }}]"
                                                    value="{{ $record->check_out ? \Carbon\Carbon::parse($record->check_out)->format('H:i') : '' }}"
                                                    class="form-control form-control-sm text-center mx-auto"
                                                    style="max-width: 100px;" onchange="syncTime({{ $index }}, 'out', 'desktop')">
                                            </div>

                                            <label class="small text-primary cursor-pointer d-block">
                                                <input type="checkbox"
                                                    onchange="setNowTime('check_out_{{ $index }}', {{ $index }})"
                                                    class="form-check-input me-1">
                                                <span>Set Now</span>
                                            </label>
                                        </td>

                                        <!-- Duration -->
                                        <td style="padding: 15px; border-right: 1px solid #e2e8f0; text-align: center;">
                                            <span id="duration_{{ $index }}" class="fw-bold" style="color: #3858f9;">--</span>
                                        </td>

                                        <!-- Status -->
                                        <td style="padding: 15px;">
                                            <select name="status[{{ $record->id }}]" id="status_{{ $index }}"
                                                class="form-select form-select-sm" style="cursor: pointer;"
                                                onchange="syncStatus({{ $index }}, 'desktop')">
                                                <option value="" {{ empty($record->status) ? 'selected' : '' }}>Select</option>
                                                <option value="present" {{ $record->status == 'present' ? 'selected' : '' }}>
                                                    Present</option>
                                                <option value="absent" {{ $record->status == 'absent' ? 'selected' : '' }}>Absent
                                                </option>
                                                <option value="half_day" {{ $record->status == 'half_day' ? 'selected' : '' }}>
                                                    Half Day</option>
                                                <option value="wfh" {{ $record->status == 'wfh' ? 'selected' : '' }}>WFH</option>
                                                <option value="leave" {{ $record->status == 'leave' ? 'selected' : '' }}>Leave
                                                </option>
                                                <option value="late" {{ $record->status == 'late' ? 'selected' : '' }}>Late
                                                </option>
                                                <option value="activity" {{ $record->status == 'activity' ? 'selected' : '' }}>
                                                    Activity</option>
                                                <option value="early_leave" {{ $record->status == 'early_leave' ? 'selected' : '' }}>Early Leave</option>
                                            </select>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

            <!-- Mobile & Tablet Card View -->
            <div class="d-lg-none">
                <div class="row g-3">
                    @foreach($attendance as $index => $record)
                    <div class="col-12">
                        <div class="card border-0 shadow-sm p-3 mb-3" style="border-radius: 15px; background: #fff; border: 1px solid #f1f5f9 !important;">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="d-flex align-items-center gap-2">
                                    <input type="checkbox" class="row_checkbox form-check-input" data-index="{{ $index }}">
                                    <div>
                                        <span class="d-block small text-muted text-uppercase fw-bold" style="letter-spacing: 0.5px;">Date</span>
                                        <span class="fw-bold text-dark">{{ \Carbon\Carbon::parse($record->attendance_date)->format('d-m-Y') }}</span>
                                    </div>
                                </div>
                                <div style="width: 120px;">
                                    <select name="status[{{ $record->id }}]" id="status_mob_{{ $index }}" 
                                            class="form-select form-select-sm border-0 bg-light fw-bold" 
                                            onchange="syncStatus({{ $index }}, 'mob')">
                                        <option value="" {{ empty($record->status) ? 'selected' : '' }}>Status</option>
                                        <option value="present" {{ $record->status == 'present' ? 'selected' : '' }}>Present</option>
                                        <option value="absent" {{ $record->status == 'absent' ? 'selected' : '' }}>Absent</option>
                                        <option value="half_day" {{ $record->status == 'half_day' ? 'selected' : '' }}>Half Day</option>
                                        <option value="wfh" {{ $record->status == 'wfh' ? 'selected' : '' }}>WFH</option>
                                        <option value="leave" {{ $record->status == 'leave' ? 'selected' : '' }}>Leave</option>
                                        <option value="late" {{ $record->status == 'late' ? 'selected' : '' }}>Late</option>
                                        <option value="activity" {{ $record->status == 'activity' ? 'selected' : '' }}>Activity</option>
                                        <option value="early_leave" {{ $record->status == 'early_leave' ? 'selected' : '' }}>Early Leave</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row g-2">
                                <!-- Check In -->
                                <div class="col-6">
                                    <div class="bg-light p-2 rounded text-center">
                                        <label class="small text-muted fw-bold d-block">In</label>
                                        <input type="time" id="check_in_mob_{{ $index }}" name="check_in[{{ $record->id }}]"
                                            value="{{ $record->check_in ? \Carbon\Carbon::parse($record->check_in)->format('H:i') : '' }}"
                                            class="form-control form-control-sm border-0 bg-white" 
                                            onchange="syncTime({{ $index }}, 'in', 'mob')">
                                        <label class="small text-primary mt-1 cursor-pointer" onclick="setNowTime('check_in_mob_{{ $index }}', {{ $index }}, 'mob')">
                                            <span>Set Now</span>
                                        </label>
                                    </div>
                                </div>
                                <!-- Check Out -->
                                <div class="col-6">
                                    <div class="bg-light p-2 rounded text-center">
                                        <label class="small text-muted fw-bold d-block">Out</label>
                                        <input type="time" id="check_out_mob_{{ $index }}" name="check_out[{{ $record->id }}]"
                                            value="{{ $record->check_out ? \Carbon\Carbon::parse($record->check_out)->format('H:i') : '' }}"
                                            class="form-control form-control-sm border-0 bg-white"
                                            onchange="syncTime({{ $index }}, 'out', 'mob')">
                                        <label class="small text-primary mt-1 cursor-pointer" onclick="setNowTime('check_out_mob_{{ $index }}', {{ $index }}, 'mob')">
                                            <span>Set Now</span>
                                        </label>
                                    </div>
                                </div>
                                <!-- Duration -->
                                <div class="col-12">
                                    <div class="d-flex justify-content-between align-items-center bg-soft-primary p-2 rounded">
                                        <span class="small fw-bold">Duration:</span>
                                        <span id="duration_mob_{{ $index }}" class="fw-bold">--</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

                <!-- Buttons -->
                <div class="d-flex flex-column flex-sm-row justify-content-end gap-2 mt-4">
                    <a href="{{ route('payroll.attendace.employee') }}" class="btn btn-outline-secondary w-100 w-sm-auto">
                        Cancel
                    </a>

                    <button type="submit" class="btn btn-primary fw-bold w-100 w-sm-auto px-4">
                        UPDATE ATTENDANCE
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

    <script>
        window.onload = function () {
            document.querySelectorAll('[id^="check_in_"]').forEach((el, index) => {
                calculateDuration(index);
            });
        };

        function setNowTime(fieldId, index, source = 'desktop') {
            const field = document.getElementById(fieldId);
            const now = new Date();

            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');

            field.value = hours + ':' + minutes;

            // Sync and Calculate
            if (source === 'desktop') {
                const type = fieldId.includes('check_in') ? 'in' : 'out';
                syncTime(index, type, 'desktop');
            } else {
                const type = fieldId.includes('check_in') ? 'in' : 'out';
                syncTime(index, type, 'mob');
            }
        }

        function syncTime(index, type, source) {
            const desktopId = `check_${type}_${index}`;
            const mobId = `check_${type}_mob_${index}`;

            if (source === 'desktop') {
                document.getElementById(mobId).value = document.getElementById(desktopId).value;
            } else {
                document.getElementById(desktopId).value = document.getElementById(mobId).value;
            }
            calculateDuration(index);
        }

        function syncStatus(index, source) {
            const desktopId = `status_${index}`;
            const mobId = `status_mob_${index}`;

            if (source === 'desktop') {
                document.getElementById(mobId).value = document.getElementById(desktopId).value;
            } else {
                document.getElementById(desktopId).value = document.getElementById(mobId).value;
            }
        }

        function calculateDuration(index) {
            const checkIn = document.getElementById('check_in_' + index).value;
            const checkOut = document.getElementById('check_out_' + index).value;
            const durationDisplay = document.getElementById('duration_' + index);
            const durationMobDisplay = document.getElementById('duration_mob_' + index);

            if (checkIn && checkOut) {
                const [inHours, inMinutes] = checkIn.split(':').map(Number);
                const [outHours, outMinutes] = checkOut.split(':').map(Number);

                let inTotal = inHours * 60 + inMinutes;
                let outTotal = outHours * 60 + outMinutes;

                if (outTotal < inTotal) outTotal += 24 * 60;

                const diffMinutes = outTotal - inTotal;
                const hours = Math.floor(diffMinutes / 60);
                const minutes = diffMinutes % 60;

                const text = hours + 'h ' + minutes + 'm';
                durationDisplay.textContent = text;
                durationDisplay.style.color = '#3858f9';
                if (durationMobDisplay) durationMobDisplay.textContent = text;
            } else {
                durationDisplay.textContent = '--';
                durationDisplay.style.color = '#999';
                if (durationMobDisplay) durationMobDisplay.textContent = '--';
            }
        }

        function toggleAllCheckboxes(source) {
            let checkboxes = document.querySelectorAll('.row_checkbox');
            checkboxes.forEach(cb => cb.checked = source.checked);
        }

        function applyBulkStatus() {
            let selectedStatus = document.getElementById('bulk_status').value;

            if (!selectedStatus) return;

            let checkboxes = document.querySelectorAll('.row_checkbox:checked');

            if (checkboxes.length === 0) {
                alert("Please select at least one employee.");
                return;
            }

            checkboxes.forEach(cb => {
                let index = cb.getAttribute('data-index');
                let statusDropdown = document.getElementById('status_' + index);

                if (statusDropdown) {
                    statusDropdown.value = selectedStatus;
                }
            });

            // Optional: reset dropdown after applying
            document.getElementById('bulk_status').value = "";
        }
    </script>

@endsection