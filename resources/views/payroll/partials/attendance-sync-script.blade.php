<script>
        // Sync punches: optional employee + date range. Nothing selected = all employees for
        // BIOMETRIC_SYNC_DAYS (same as the automatic sync).
        const SYNC_DEFAULT_DAYS = {{ (int) config('biometric.sync_days', 2) }};
        const SYNC_MAX_DAYS = {{ (int) config('biometric.max_sync_days', 90) }};
        @php
            $syncEmployeeList = collect($employees ?? [])
                ->map(fn ($e) => ['id' => $e->id, 'name' => $e->name, 'code' => $e->employee_code ?? null])
                ->sortBy('name')
                ->values();
        @endphp
        const SYNC_EMPLOYEES = @json($syncEmployeeList);

        function syncEscape(v) {
            const d = document.createElement('div');
            d.textContent = v == null ? '' : String(v);
            return d.innerHTML;
        }

        function syncLocalDate(offsetDays) {
            const d = new Date();
            d.setDate(d.getDate() + (offsetDays || 0));
            return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
        }

        function syncBiometricAttendance() {
            const btn = document.getElementById('syncBiometricBtn');
            if (!btn || btn.disabled) {
                return;
            }

            const today = syncLocalDate(0);
            const employeeOptions = SYNC_EMPLOYEES.map(e =>
                `<option value="${e.id}">${syncEscape(e.name)}${e.code ? ' (' + syncEscape(e.code) + ')' : ''}</option>`
            ).join('');

            Swal.fire({
                title: 'Sync biometric punches',
                html: `
                    <div class="text-start" style="font-size:14px;">
                        <label class="form-label fw-semibold mb-1" for="syncEmployee">Employee</label>
                        <select id="syncEmployee" class="form-select mb-3">
                            <option value="">All employees</option>
                            ${employeeOptions}
                        </select>

                        <label class="form-label fw-semibold mb-1" for="syncRange">Punches to fetch</label>
                        <select id="syncRange" class="form-select mb-2">
                            <option value="auto">Automatic — last ${SYNC_DEFAULT_DAYS} day${SYNC_DEFAULT_DAYS === 1 ? '' : 's'} (as configured)</option>
                            <option value="1">Today only</option>
                            <option value="3">Last 3 days</option>
                            <option value="7">Last 7 days</option>
                            <option value="15">Last 15 days</option>
                            <option value="30">Last 30 days</option>
                            <option value="custom">Custom date range…</option>
                        </select>

                        <div id="syncCustomRange" class="row g-2 d-none">
                            <div class="col-6">
                                <label class="form-label small mb-1" for="syncFrom">From</label>
                                <input type="date" id="syncFrom" class="form-control" max="${today}" value="${syncLocalDate(-6)}">
                            </div>
                            <div class="col-6">
                                <label class="form-label small mb-1" for="syncTo">To</label>
                                <input type="date" id="syncTo" class="form-control" max="${today}" value="${today}">
                            </div>
                            <div class="col-12"><small class="text-muted">Up to ${SYNC_MAX_DAYS} days back from today.</small></div>
                        </div>
                        <p class="text-muted small mt-3 mb-0">Leave both as they are to sync everyone automatically, the same as the scheduled sync.</p>
                    </div>`,
                showCancelButton: true,
                confirmButtonText: 'Sync now',
                cancelButtonText: 'Cancel',
                reverseButtons: true,
                focusConfirm: false,
                customClass: {
                    confirmButton: 'btn btn-primary px-4',
                    cancelButton: 'btn btn-light-brand px-4 me-3'
                },
                buttonsStyling: false,
                didOpen: () => {
                    const range = document.getElementById('syncRange');
                    range.addEventListener('change', () => {
                        document.getElementById('syncCustomRange').classList.toggle('d-none', range.value !== 'custom');
                    });
                },
                preConfirm: () => {
                    const payload = {};
                    const employeeId = document.getElementById('syncEmployee').value;
                    const range = document.getElementById('syncRange').value;
                    if (employeeId) payload.employee_id = Number(employeeId);

                    if (range === 'custom') {
                        const from = document.getElementById('syncFrom').value;
                        const to = document.getElementById('syncTo').value || today;
                        if (!from) {
                            Swal.showValidationMessage('Choose a start date.');
                            return false;
                        }
                        if (from > to) {
                            Swal.showValidationMessage('The start date must be on or before the end date.');
                            return false;
                        }
                        if (to > today) {
                            Swal.showValidationMessage('Dates cannot be in the future.');
                            return false;
                        }
                        const daysBack = Math.round((new Date(today) - new Date(from)) / 86400000) + 1;
                        if (daysBack > SYNC_MAX_DAYS) {
                            Swal.showValidationMessage(`Start date is too far back — the limit is ${SYNC_MAX_DAYS} days.`);
                            return false;
                        }
                        payload.from_date = from;
                        payload.to_date = to;
                    } else if (range !== 'auto') {
                        payload.days = Number(range);
                    }
                    return payload;
                }
            }).then((result) => {
                if (!result.isConfirmed) {
                    return;
                }

                const originalHtml = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<i class="feather-loader"></i> Syncing...';

                fetch('{{ route('sync.attendance') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(result.value || {}),
                })
                    .then(async (response) => {
                        const data = await response.json().catch(() => ({}));

                        if (!response.ok || !data.success) {
                            throw new Error(data.message || 'Biometric sync failed');
                        }

                        return data;
                    })
                    .then((data) => {
                        Swal.fire({
                            icon: data.total_records ? 'success' : 'info',
                            title: data.total_records ? 'Punches synced' : 'Nothing new to sync',
                            text: data.message || 'Attendance synced successfully',
                            confirmButtonText: 'OK',
                        }).then(() => window.location.reload());
                    })
                    .catch((error) => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Sync failed',
                            text: error.message || 'Unable to import punches from biometric device.',
                        });
                    })
                    .finally(() => {
                        btn.disabled = false;
                        btn.innerHTML = originalHtml;
                    });
            });
        }

        function rebuildBiometricAttendance() {
            const btn = document.getElementById('rebuildBiometricBtn');
            if (!btn || btn.disabled) {
                return;
            }

            Swal.fire({
                title: 'Rebuild from stored logs?',
                text: 'This recalculates attendance from punches already saved in the database. It does not contact the biometric device.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3858f9',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, rebuild',
                cancelButtonText: 'Cancel',
                reverseButtons: true,
                customClass: {
                    confirmButton: 'btn btn-primary px-4',
                    cancelButton: 'btn btn-light-brand px-4 me-3'
                },
                buttonsStyling: false
            }).then((result) => {
                if (!result.isConfirmed) {
                    return;
                }

                const originalHtml = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<i class="feather-loader"></i> Rebuilding...';

                fetch('{{ route('sync.attendance.rebuild') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                })
                    .then(async (response) => {
                        const data = await response.json().catch(() => ({}));

                        if (!response.ok || !data.success) {
                            throw new Error(data.message || 'Rebuild failed');
                        }

                        return data;
                    })
                    .then((data) => {
                        if (typeof Toast !== 'undefined') {
                            Toast.fire({
                                icon: 'success',
                                title: data.message || 'Attendance rebuilt successfully',
                            });
                        }

                        setTimeout(() => window.location.reload(), 1200);
                    })
                    .catch((error) => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Rebuild failed',
                            text: error.message || 'Unable to rebuild attendance from logs.',
                        });
                    })
                    .finally(() => {
                        btn.disabled = false;
                        btn.innerHTML = originalHtml;
                    });
            });
        }
</script>
