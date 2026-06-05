@extends('layouts.app')

@section('content')
    <div class="container-fluid px-0" style="background: #f8fafc; min-height: 100vh; font-family: 'Inter', sans-serif;">
        <!-- Main Content Card -->
        <div class="px-4">
            <div class="card border-0 shadow-sm" style="border-radius: 12px; background: white;">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center"
                    style="border-radius: 12px 12px 0 0;">
                    <div>
                        <h5 class="fw-bold mb-0" style="color: #334155;">Payroll History</h5>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="#"
                                        class="text-decoration-none text-muted small">Home</a></li>
                                <li class="breadcrumb-item active small fw-bold" style="color: #3858f9;"
                                    aria-current="page">History</li>
                            </ol>
                        </nav>
                    </div>
                    
                </div>

                <!-- salary report -->
                <div id="salaryFormSection" class="card mt-4 p-4" style="display: none;">
                    <h4>Salary Slip</h4>

                    <form action="{{ route('payroll.sendDateRange') }}" method="POST">
                        @csrf
                        <!-- Employee Dropdown -->
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted mb-2">Select Employee</label>
                            <div class="dropdown">
                                <button class="wghrm-custom-select-btn fw-bold dropdown-toggle" type="button"
                                    data-bs-toggle="dropdown" data-bs-auto-close="outside" id="salaryEmployeeBtn"
                                    style="border-radius: 8px; height: 45px !important; font-size: 14px; background: #fff; border: 1px solid #e2e8f0;">
                                    Select
                                </button>
                                <div class="dropdown-menu wghrm-custom-dropdown-menu" style="width: 100%;">
                                    <div class="wghrm-custom-search-box">
                                        <input type="text" class="wghrm-custom-search-input"
                                            placeholder="Search employee..." onkeyup="wghrmFilterItems(this)"
                                            onclick="event.stopPropagation();" onkeydown="event.stopPropagation();">
                                    </div>
                                    @foreach(\App\Models\Employee::all() as $emp)
                                        <a class="dropdown-item wghrm-custom-dropdown-item"
                                            href="javascript:void(0);"
                                            onclick="document.getElementById('salaryEmployeeId').value='{{ $emp->id }}'; document.getElementById('salaryEmployeeBtn').innerText='{{ addslashes($emp->name) }}'; bootstrap.Dropdown.getInstance(this.closest('.dropdown').querySelector('.dropdown-toggle')).hide();">
                                            {{ $emp->name }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                            <input type="hidden" id="salaryEmployeeId" name="employee_id" value="">
                        </div>

                        <!-- Date From -->
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted mb-2">From Date</label>
                            <input type="date" name="from_date" class="form-control" style="height: 45px; border-radius: 8px;">
                        </div>

                        <!-- Date To -->
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted mb-2">To Date</label>
                            <input type="date" name="to_date" class="form-control" style="height: 45px; border-radius: 8px;">
                        </div>

                        <button type="submit" class="btn btn-primary w-100 fw-bold" style="height: 45px; border-radius: 8px; background: #3858f9; border: none;">GENERATE SLIP</button>
                    </form>
                </div>

                <!-- Collapsible Filter Section -->
                <div class="collapse" id="filterSection">
                    <div class="card-body border-bottom bg-light bg-opacity-10 p-4">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-muted mb-2">Employee</label>
                                <div class="dropdown">
                                    <button class="wghrm-custom-select-btn fw-bold dropdown-toggle" type="button"
                                        data-bs-toggle="dropdown" data-bs-auto-close="outside" id="employeeFilterBtn"
                                        style="border-radius: 8px; height: 47px !important;">
                                        @php
                                            $selectedEmp = \App\Models\Employee::find(request('employee_id'));
                                        @endphp
                                        {{ $selectedEmp ? $selectedEmp->name : 'All Employees' }}
                                    </button>
                                    <div class="dropdown-menu wghrm-custom-dropdown-menu">
                                        <div class="wghrm-custom-search-box">
                                            <input type="text" class="wghrm-custom-search-input"
                                                placeholder="Search employee..." onkeyup="wghrmFilterItems(this)"
                                                onclick="event.stopPropagation();" onkeydown="event.stopPropagation();">
                                        </div>
                                        <a class="dropdown-item wghrm-custom-dropdown-item {{ !request('employee_id') ? 'active' : '' }}"
                                            href="javascript:void(0);"
                                            onclick="document.getElementById('employeeFilter').value=''; document.getElementById('employeeFilterBtn').innerText='All Employees';">All
                                            Employees</a>
                                        @foreach(\App\Models\Employee::all() as $emp)
                                            <a class="dropdown-item wghrm-custom-dropdown-item {{ request('employee_id') == $emp->id ? 'active' : '' }}"
                                                href="javascript:void(0);"
                                                onclick="document.getElementById('employeeFilter').value='{{ $emp->id }}'; document.getElementById('employeeFilterBtn').innerText='{{ addslashes($emp->name) }}';">
                                                {{ $emp->name }}
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                                <input type="hidden" id="employeeFilter" value="{{ request('employee_id') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-muted mb-2">Month</label>
                                <input type="month" id="monthFilter"
                                    class="form-control border-0 bg-white py-0 px-3 shadow-sm fw-bold"
                                    value="{{ request('month') }}" style="border-radius: 8px; height: 47px !important;">
                            </div>
                            <div class="col-md-4 d-flex gap-2">
                                <button
                                    class="btn btn-primary flex-grow-1 fw-bold d-flex align-items-center justify-content-center gap-2 shadow-sm"
                                    onclick="applyFilters()"
                                    style="background: #3858f9; border: none; height: 47px !important; border-radius: 8px;">
                                    <i class="feather-search"></i> APPLY
                                </button>
                                <button class="btn btn-light border px-3 shadow-none" onclick="resetFilters()"
                                    style="height: 47px !important; border-radius: 8px;">
                                    <i class="feather-refresh-cw" style="font-size: 14px;"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead style="background: #ffffff; border-bottom: 1px solid #f1f5f9;">
                            <tr>
                                <th class="ps-4 py-3 text-muted small fw-bold text-uppercase" style="width: 80px;">SR. NO.
                                </th>
                                <th class="py-3 text-muted small fw-bold text-uppercase">EMPLOYEE NAME</th>
                                <th class="py-3 text-muted small fw-bold text-uppercase text-center">MONTH</th>
                                <th class="py-3 text-muted small fw-bold text-uppercase text-center">PAYABLE DAYS</th>
                                <th class="py-3 text-muted small fw-bold text-uppercase text-end">GROSS SALARY</th>
                                <th class="py-3 text-muted small fw-bold text-uppercase text-end">DEDUCTIONS</th>
                                <th class="py-3 text-muted small fw-bold text-uppercase text-end">NET SALARY</th>
                                <th class="py-3 text-muted small fw-bold text-uppercase text-center" style="width: 140px;">
                                    STATUS</th>
                                <th class="pe-4 py-3 text-muted small fw-bold text-uppercase text-center"
                                    style="width: 150px;">ACTION</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($payrolls as $index => $payroll)
                                <tr class="hover-row border-bottom">
                                    <td class="ps-4 py-4 fw-bold text-muted">{{ $index + 1 }}</td>
                                    <td>
                                        <div class="fw-bold text-dark fs-6">{{ $payroll->employee->name }}</div>
                                    </td>
                                    <td class="text-center">
                                        <span class="text-muted small fw-bold">{{ $payroll->month }}</span>
                                    </td>
                                    <td class="text-center fw-bold">{{ $payroll->payable_days }}</td>
                                    <td class="text-end fw-bold text-dark">₹{{ number_format($payroll->gross_salary, 2) }}</td>
                                    <td class="text-end fw-bold text-danger">₹{{ number_format($payroll->deductions, 2) }}</td>
                                    <td class="text-end fw-bold text-primary" style="font-size: 15px;">
                                        ₹{{ number_format($payroll->net_salary, 2) }}</td>
                                    <td class="text-center">
                                        @php
                                            $statusClass = 'bg-soft-warning text-warning';
                                            if ($payroll->status == 'paid')
                                                $statusClass = 'bg-soft-success text-success';
                                            elseif ($payroll->status == 'rejected')
                                                $statusClass = 'bg-soft-danger text-danger';
                                        @endphp
                                        <div class="dropdown">
                                            <span class="badge {{ $statusClass }} dropdown-toggle cursor-pointer"
                                                data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false"
                                                style="padding: 7px 14px; border-radius: 9px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; cursor: pointer; min-width: 105px; display: inline-block; text-align: center;">
                                                {{ $payroll->status }}
                                            </span>
                                            <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg p-2"
                                                style="border-radius: 16px; min-width: 160px; z-index: 9999999 !important; border: 1px solid rgba(0,0,0,0.05) !important; position: absolute !important;">
                                                <li><a class="dropdown-item fw-bold text-warning rounded-3 py-2 px-3 mb-1"
                                                        href="javascript:void(0);"
                                                        onclick="updateStatus({{ $payroll->id }}, 'pending')"
                                                        style="font-size: 13px;">Pending</a></li>
                                                <li><a class="dropdown-item fw-bold text-success rounded-3 py-2 px-3 mb-1"
                                                        href="javascript:void(0);"
                                                        onclick="updateStatus({{ $payroll->id }}, 'paid')"
                                                        style="font-size: 13px;">Paid</a></li>
                                                <li><a class="dropdown-item fw-bold text-danger rounded-3 py-2 px-3"
                                                        href="javascript:void(0);"
                                                        onclick="updateStatus({{ $payroll->id }}, 'rejected')"
                                                        style="font-size: 13px;">Rejected</a></li>
                                            </ul>
                                        </div>
                                    </td>
                                    <td class="pe-4 text-center">
                                        <div class="d-flex justify-content-center gap-2">
                                            <a href="javascript:void(0);"
                                                class="avatar-text avatar-md bg-soft-primary text-primary"
                                                onclick="viewPayroll({{ $payroll->id }})" title="View">
                                                <i class="feather-eye"></i>
                                            </a>
                                            <a href="javascript:void(0);" class="avatar-text avatar-md bg-soft-info text-info"
                                                onclick="downloadSlip({{ $payroll->id }}, 'pdf')" title="Download PDF">
                                                <i class="feather-download"></i>
                                            </a>
                                            <!-- <a href="javascript:void(0);"
                                                class="avatar-text avatar-md bg-soft-danger text-danger"
                                                onclick="deletePayroll({{ $payroll->id }})" title="Delete">
                                                <i class="feather-trash-2"></i>
                                            </a> -->
                                            @php
                                                $roleSlug = auth()->user()->role;
                                                $roleId = DB::table('roles_master')
                                                    ->where('slug', $roleSlug)
                                                    ->value('id');

                                                $isAdmin = in_array($roleId, [1, 2, 3, 4]);
                                            @endphp
                                            <a href="javascript:void(0);"
                                                class="avatar-text avatar-md bg-soft-secondary text-secondary comment-btn {{ (!$payroll->is_read && $isAdmin) ? 'blink' : '' }}"
                                                data-id="{{ $payroll->id }}" data-remark="{{ $payroll->remarks ?? '' }}"
                                                data-role="{{ auth()->user()->role }}" title="Comment">
                                                <i class="feather-message-square"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-5">
                                        <div class="py-5">
                                            <i class="bi bi-calculator text-muted" style="font-size: 3rem; opacity: 0.2;"></i>
                                            <p class="text-muted mt-3 fw-bold">No payroll records found.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    <div class="modal fade" id="commentModal" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content" style="border-radius: 12px;">

                                <div class="modal-header">
                                    <h5 class="modal-title">Add Comment</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>

                                <div class="modal-body">
                                    <textarea id="remarksField" class="form-control" rows="4"
                                        placeholder="Write comment..."></textarea>
                                    <input type="hidden" id="userRole">
                                    <input type="hidden" id="payrollId">
                                </div>

                                <div class="modal-footer">
                                    <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                    <button id="saveBtn" class="btn btn-primary" onclick="saveComment()">Save</button>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
                @if($payrolls->hasPages())
                    <div class="card-footer bg-white border-0 py-3">
                        {{ $payrolls->appends(request()->query())->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('modals')
    <!-- Payroll Calculation Offcanvas -->
    <div class="offcanvas offcanvas-end border-0 shadow-lg" tabindex="-1" id="payrollCalculationOffcanvas"
        style="width: 650px !important; background: #f8fafc;">
        <div class="offcanvas-header bg-white border-bottom px-4 py-3">
            <div class="d-flex align-items-center gap-3">
                <div class="avatar-text avatar-md bg-soft-primary text-primary rounded-3">
                    <i class="feather-calculator"></i>
                </div>
                <div>
                    <h5 class="offcanvas-title fw-bold text-dark">Enterprise Payroll Engine</h5>
                    <p class="text-muted small mb-0">Generate and validate monthly employee payslips</p>
                </div>
            </div>
            <button type="button" class="btn-close shadow-none" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body p-0">
            @include('payroll._calculation_form')
        </div>
    </div>

    <!-- Statement Modal -->
    <div class="modal fade" id="payrollDetailModal" tabindex="-1" aria-labelledby="payrollModalLabel" aria-hidden="true"
        data-bs-backdrop="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; background: #fff; overflow: hidden;">
                <div class="modal-header border-0 px-4 py-3" style="background: #3858f9; color: #ffffff;">
                    <h5 class="modal-title fw-bold" id="payrollModalLabel"><i class="bi bi-file-earmark-text-fill me-2"></i>
                        Payroll Statement</h5>
                    <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-0 bg-white" id="payrollModalBody"
                    style="min-height: 400px; max-height: 80vh; overflow-y: auto;">
                    <div class="d-flex justify-content-center align-items-center" style="height: 400px;">
                        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status"></div>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 py-3 bg-light">
                    <button type="button" class="btn btn-secondary fw-bold px-4 shadow-none" data-bs-dismiss="modal"
                        style="border-radius: 8px;">CLOSE</button>
                </div>
            </div>
        </div>
    </div>
@endpush

@push('scripts')
    <script>

        document.querySelectorAll('.comment-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const id = this.dataset.id;
                const remark = this.dataset.remark; // already JSON-safe string
                const role = this.dataset.role;

                openCommentModal(id, remark, role);
            });
        });

        function openCommentModal(id, remarks, role) {
            role = (role || '').toLowerCase();
            let modalEl = document.getElementById('commentModal');
            let modal = new bootstrap.Modal(modalEl);
            modal.show();

            // Set hidden fields
            document.getElementById('payrollId').value = id;
            document.getElementById('userRole').value = role;

            let field = document.getElementById('remarksField');
            let title = modalEl.querySelector('.modal-title');
            let saveBtn = document.getElementById('saveBtn');

            field.value = remarks || '';

            if (role === 'employee') {
                // ✅ Employee: can edit
                field.removeAttribute('readonly');
                field.style.display = 'block';

                title.innerText = 'Add Comment';
            } else {
                // ✅ Admin/HR: view only
                field.setAttribute('readonly', true);
                field.style.display = 'block'; // keep visible
                title.innerText = 'Remarks';
                saveBtn.style.display = 'none';

                document.querySelector(`[data-id="${id}"]`)?.classList.remove('blink');

                markAsRead(id);
            }
        }

        function markAsRead(id) {
            fetch(`/payroll/${id}/mark-read`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            }).catch(err => console.error(err));
        }

        function saveComment() {
            let role = document.getElementById('userRole').value;
            if (role.toLowerCase() !== 'employee') return;

            let id = document.getElementById('payrollId').value;
            let remarks = document.getElementById('remarksField').value;

            let formData = new FormData();
            formData.append('remarks', remarks);

            fetch(`/payroll/${id}/remarks`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: formData
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        bootstrap.Modal.getInstance(document.getElementById('commentModal')).hide();
                        location.reload();
                    } else {
                        alert("Error: " + (data.error || "Unknown error"));
                    }
                })
                .catch(err => console.error("Fetch Error:", err));
        }

        document.addEventListener('DOMContentLoaded', function () {

            const btn = document.getElementById('exportBtn');
            const menu = document.getElementById('exportMenu');

            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                menu.classList.toggle('d-none');
            });

            menu.addEventListener('click', function (e) {
                e.stopPropagation();
            });

            document.addEventListener('click', function () {
                menu.classList.add('d-none');
            });

        });

        document.getElementById("shareReportBtn").addEventListener("click", function () {
            let section = document.getElementById("salaryFormSection");

            if (section.style.display === "none") {
                section.style.display = "block";
            } else {
                section.style.display = "none";
            }
        });

        function applyFilters() {
            const month = document.getElementById('monthFilter').value;
            const empId = document.getElementById('employeeFilter').value;
            window.location.href = `{{ route("payroll.index") }}?month=${month}&employee_id=${empId}`;
        }

        function searchTable() {
            const filter = document.getElementById('tableSearch').value.toLowerCase();
            const rows = document.querySelectorAll('table tbody tr.hover-row');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(filter)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Custom Dropdown Search Logic
        function wghrmFilterItems(input) {
            const filter = input.value.toLowerCase();
            const items = input.closest('.wghrm-custom-dropdown-menu').querySelectorAll('.wghrm-custom-dropdown-item');
            items.forEach(item => {
                const text = item.textContent.toLowerCase();
                if (text.includes(filter)) {
                    item.style.setProperty('display', 'block', 'important');
                } else {
                    item.style.setProperty('display', 'none', 'important');
                }
            });
        }

        function resetFilters() {
            window.location.href = '{{ route("payroll.index") }}';
        }

        function viewPayroll(id) {
            const modalEl = document.getElementById('payrollDetailModal');
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);

            document.getElementById('payrollModalBody').innerHTML = `
                                                        <div class="d-flex justify-content-center align-items-center" style="height: 400px;">
                                                            <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status"></div>
                                                        </div>`;

            modal.show();

            fetch(`/payroll/${id}`)
                .then(res => res.text())
                .then(html => {
                    document.getElementById('payrollModalBody').innerHTML = html;
                })
                .catch(err => {
                    document.getElementById('payrollModalBody').innerHTML = '<div class="p-5 text-center text-danger">Error loading payroll data.</div>';
                });
        }

        function downloadSlip(id, format = 'csv') {
            window.location.href = `{{ route('payroll.export') }}?id=${id}&format=${format}`;
        }

        function deletePayroll(id) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this payroll record!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3858f9',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'No, cancel',
                reverseButtons: true,
                customClass: {
                    confirmButton: 'btn btn-primary px-4',
                    cancelButton: 'btn btn-light-brand px-4 me-3'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`/payroll/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    }).then(res => res.json()).then(data => {
                        if (data.success) {
                            if (typeof Toast !== 'undefined') {
                                Toast.fire({
                                    icon: 'success',
                                    title: 'Payroll record deleted'
                                });
                            }
                            setTimeout(() => location.reload(), 1000);
                        }
                    });
                }
            });
        }

        function updateStatus(id, newStatus) {
            fetch(`/payroll/${id}/status`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ status: newStatus })
            }).then(res => res.json()).then(data => {
                if (data.success) {
                    // Instantly update color
                    location.reload();
                }
            });
        }

        function exportPayroll(format = 'csv') {
            const month = document.getElementById('monthFilter').value;
            const empId = document.getElementById('employeeFilter').value;
            window.location.href = `{{ route('payroll.export') }}?month=${month}&employee_id=${empId}&format=${format}`;
        }
    </script>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');

        .wghrm-custom-select-btn {
            background-color: #fff;
            border: 0;
            box-shadow: 0 .125rem .25rem rgba(0, 0, 0, .075);
            border-radius: 8px;
            color: #475569;
            padding: 0 15px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            height: 40px !important;
            font-size: 14px;
            text-align: left;
        }

        .wghrm-custom-select-btn::after {
            border-top: .3em solid;
            border-right: .3em solid transparent;
            border-bottom: 0;
            border-left: .3em solid transparent;
            margin-left: .255em;
            content: "";
        }

        .wghrm-custom-dropdown-menu {
            border-radius: 16px !important;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.12) !important;
            padding: 10px !important;
            margin-top: 10px !important;
            z-index: 1060 !important;
            background: #fff !important;
            max-height: 350px !important;
            overflow-y: auto !important;
            border: 1px solid rgba(0,0,0,0.05) !important;
            min-width: 250px !important;
            width: 100%;
        }

        /* Custom Scrollbar (Slider) */
        .wghrm-custom-dropdown-menu::-webkit-scrollbar {
            width: 6px;
        }

        .wghrm-custom-dropdown-menu::-webkit-scrollbar-track {
            background: #f8fafc;
            border-radius: 10px;
        }

        .wghrm-custom-dropdown-menu::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        .wghrm-custom-dropdown-menu::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        .wghrm-custom-dropdown-item {
            border-radius: 10px !important;
            padding: 10px 15px !important;
            font-weight: 500 !important;
            font-size: 14px !important;
            color: #475569 !important;
            margin-bottom: 3px !important;
            transition: all 0.2s ease !important;
            cursor: pointer !important;
            white-space: nowrap !important;
        }

        .wghrm-custom-dropdown-item:hover,
        .wghrm-custom-dropdown-item.active {
            background: #f1f5f9 !important;
            color: #3858f9 !important;
        }

        .wghrm-custom-search-box {
            position: sticky;
            top: 0;
            background: white;
            z-index: 10;
            padding-bottom: 8px;
            margin-bottom: 8px;
            border-bottom: 1px solid #f1f5f9;
        }

        .wghrm-custom-search-input {
            width: 100%;
            padding: 8px 12px;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            font-size: 13px;
            outline: none;
        }

        .wghrm-custom-search-input:focus {
            border-color: #3858f9;
            box-shadow: 0 0 0 2px rgba(56, 88, 249, 0.1);
        }

        .breadcrumb-item+.breadcrumb-item::before {
            content: ">";
            color: #94a3b8;
        }

        .hover-row:hover {
            background-color: #fbfcfe;
        }

        .action-btn-outline {
            background: transparent !important;
            border: none !important;
            border-radius: 8px;
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            color: #64748b;
        }

        .table-responsive {
            overflow: visible !important;
        }

        .dropdown-menu {
            z-index: 99999999 !important;
        }

        .action-btn-outline:hover {
            background: #f1f5f9 !important;
            color: #3858f9;
        }

        .form-select-sm {
            font-size: 11px;
            letter-spacing: 0.3px;
            padding-top: 5px;
            padding-bottom: 5px;
        }

        /* Premium Calendar/Date Input Styling */
        input[type="date"],
        input[type="month"] {
            border: 1px solid #e2e8f0 !important;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            color: #334155 !important;
            font-weight: 600 !important;
            cursor: pointer;
        }

        input[type="date"]:hover,
        input[type="month"]:hover {
            border-color: #cbd5e1 !important;
            background-color: #ffffff !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        input[type="date"]:focus,
        input[type="month"]:focus {
            border-color: #3858f9 !important;
            box-shadow: 0 0 0 4px rgba(56, 88, 249, 0.12) !important;
            background-color: #ffffff !important;
            outline: none !important;
        }

        /* Customizing the native calendar picker icon */
        input[type="date"]::-webkit-calendar-picker-indicator,
        input[type="month"]::-webkit-calendar-picker-indicator {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%233858f9' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='3' y='4' width='18' height='18' rx='2' ry='2'%3E%3C/rect%3E%3Cline x1='16' y1='2' x2='16' y2='6'%3E%3C/line%3E%3Cline x1='8' y1='2' x2='8' y2='6'%3E%3C/line%3E%3Cline x1='3' y1='10' x2='21' y2='10'%3E%3C/line%3E%3C/svg%3E");
            cursor: pointer;
            padding: 5px;
            border-radius: 4px;
            transition: all 0.2s;
        }

        input[type="date"]::-webkit-calendar-picker-indicator:hover,
        input[type="month"]::-webkit-calendar-picker-indicator:hover {
            background-color: rgba(56, 88, 249, 0.08);
        }

        .blink {
            animation: blink-animation 1s infinite;
        }

        @keyframes blink-animation {
            50% {
                opacity: 0.3;
            }
        }
    </style>
@endpush