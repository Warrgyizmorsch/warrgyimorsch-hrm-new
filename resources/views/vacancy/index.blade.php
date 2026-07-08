@extends('layouts.app')

@section('content')
    <!-- [ page-header ] start -->
    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Job Vacancy</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                <li class="breadcrumb-item">Job Vacancy</li>
            </ul>
        </div>
        <div class="page-header-right ms-auto">
            <div class="page-header-right-items">
                <div class="d-flex d-md-none">
                    <a href="javascript:void(0)" class="page-header-right-close-toggle">
                        <i class="feather-arrow-left me-2"></i>
                        <span>Back</span>
                    </a>
                </div>
                <div class="d-flex align-items-center gap-2 page-header-right-items-wrapper">
                    <div id="bulk-action-wrapper" style="display: none;">
                        <a href="javascript:void(0);" id="btn-bulk-delete" class="btn btn-icon btn-soft-danger"
                            style="width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center;
                            justify-content: center;" title="Delete selected">
                            <i class="feather-trash-2 fs-18"></i>
                        </a>
                    </div>
                    <a href="{{ route('candidates.index') }}" class="btn btn-icon btn-light-brand" title="Candidates">
                        <i class="feather-users"></i>
                    </a>
                    <div class="filter-toggle-wrapper">
                        <a href="javascript:void(0);" class="btn btn-icon btn-light-brand" id="toggleFilter"
                            style="cursor: pointer;">
                            <i class="feather-filter"></i>
                        </a>
                    </div>
                    <a href="javascript:void(0)" class="btn btn-primary" onclick="openAddOffcanvas()">
                        <i class="feather-plus me-2"></i>
                        <span>Add</span>
                    </a>
                </div>
            </div>
            <div class="d-md-none d-flex align-items-center">
                <a href="javascript:void(0)" class="page-header-right-open-toggle">
                    <i class="feather-align-right fs-20"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="page-header-collapse">
        <div class="accordion-body pb-2">
            <div class="row g-3">
                @php
                    $stageColors = [
                        'applied' => 'secondary', 'shortlisted' => 'info', 'interview_scheduled' => 'warning',
                        'interviewed' => 'warning', 'offered' => 'primary', 'hired' => 'success', 'rejected' => 'danger',
                    ];
                    $stageIcons = [
                        'applied' => 'feather-inbox', 'shortlisted' => 'feather-star', 'interview_scheduled' => 'feather-calendar',
                        'interviewed' => 'feather-message-circle', 'offered' => 'feather-send', 'hired' => 'feather-check-circle', 'rejected' => 'feather-x-circle',
                    ];
                @endphp
                @foreach(\App\Models\JobApplication::STAGES as $stageKey => $stageLabel)
                    <div class="col-xxl col-md-4">
                        <a href="{{ request()->fullUrlWithQuery(['stage' => $stageKey, 'page' => 1]) }}" class="text-decoration-none">
                            <div class="card stretch stretch-full border-start border-4 border-{{ $stageColors[$stageKey] }} {{ request('stage') === $stageKey ? 'shadow' : '' }}">
                                <div class="card-body p-3">
                                    <div class="hstack justify-content-between">
                                        <div>
                                            <span class="fs-10 fw-bold text-uppercase d-block mb-1">{{ $stageLabel }}</span>
                                            <span class="fs-20 fw-bolder d-block text-dark">{{ $stageCounts[$stageKey] }}</span>
                                        </div>
                                        <div class="avatar-text avatar-md bg-soft-{{ $stageColors[$stageKey] }} text-{{ $stageColors[$stageKey] }}">
                                            <i class="{{ $stageIcons[$stageKey] }}"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    <!-- [ page-header ] end -->

    <!-- filter section -->
    <div class="filter-wrapper" id="filterSection" style="display: none;">
        <div class="card stretch stretch-full border-bottom bg-light bg-opacity-10 p-4 mb-4">
            <form method="GET" action="{{ route('vacancy.show') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Search</label>
                        <input type="text" name="search" class="form-control" placeholder="Name, email, designation..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Stage</label>
                        <select name="stage" class="form-select">
                            <option value="">All Stages</option>
                            @foreach(\App\Models\JobApplication::STAGES as $stageKey => $stageLabel)
                                <option value="{{ $stageKey }}" {{ request('stage') === $stageKey ? 'selected' : '' }}>{{ $stageLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Department</label>
                        <select name="department_id" class="form-select">
                            <option value="">All Departments</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" {{ (string) request('department_id') === (string) $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Requirement</label>
                        <select name="job_requirement_id" class="form-select">
                            <option value="">All Requirements</option>
                            @foreach($requirements as $req)
                                <option value="{{ $req->id }}" {{ (string) request('job_requirement_id') === (string) $req->id ? 'selected' : '' }}>{{ $req->designation->name ?? 'Requirement #'.$req->id }} — {{ \Carbon\Carbon::parse($req->date)->format('d M Y') }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-auto d-flex gap-2">
                        <button type="submit" class="btn btn-primary" style="height: 45px;">Apply</button>
                        <a href="{{ route('vacancy.show') }}" class="btn btn-light" style="height: 45px;">Reset</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Main containt  -->
    <div class="main-content">
        <div class="row">
            <div class="col-lg-12">
                <div class="card stretch stretch-full">
                    <div class="card-body p-0">
                        <!-- SHOW ENTRIES -->
                        <div class="px-4 py-3 border-bottom d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-2">
                                <span class="text-muted small fw-bold text-uppercase">Show</span>
                                <div class="dropdown">
                                    <button class="wghrm-custom-select-btn dropdown-toggle" type="button"
                                        data-bs-toggle="dropdown" id="showEntriesBtn"
                                        style="width: 80px; height: 44px; padding: 0 15px;">
                                        {{ $perPage ?? 20 }}
                                    </button>
                                    <div class="dropdown-menu wghrm-custom-dropdown-menu shadow-lg border-0" style="min-width: 80px; border-radius: 12px;">
                                        <a class="dropdown-item wghrm-custom-dropdown-item {{ ($perPage ?? 20) == 20 ? 'active' : '' }}" href="{{ request()->fullUrlWithQuery(['per_page' => 20, 'page' => 1]) }}">20</a>
                                        <a class="dropdown-item wghrm-custom-dropdown-item {{ ($perPage ?? 50) == 50 ? 'active' : '' }}" href="{{ request()->fullUrlWithQuery(['per_page' => 50, 'page' => 1]) }}">50</a>
                                        <a class="dropdown-item wghrm-custom-dropdown-item {{ ($perPage ?? 100) == 100 ? 'active' : '' }}" href="{{ request()->fullUrlWithQuery(['per_page' => 100, 'page' => 1]) }}">100</a>
                                    </div>
                                </div>
                                <span class="text-muted small fw-bold text-uppercase">entries</span>
                            </div>
                            <span class="text-muted small" id="selectedCount">0 selected</span>
                        </div>

                        <div class="table-responsive">
                            @if(!empty($selectedRole))
                                <div class="alert alert-info d-flex justify-content-between align-items-center role-filter-alert m-3" role="alert">
                                    <span>
                                        Showing applications filtered by: <strong>{{ $selectedRole }}</strong>
                                    </span>
                                    <a href="{{ route('vacancy.show') }}" class="btn btn-sm btn-primary">
                                        Show All Data
                                    </a>
                                </div>
                            @endif
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th style="width: 34px;"><input type="checkbox" id="select_all" class="form-check-input" onclick="toggleAllCheckboxes(this)"></th>
                                        <th>Candidate</th>
                                        <th>Requirement</th>
                                        <th>Interview</th>
                                        <th>Stage</th>
                                        <th>Resume</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @forelse($applications as $app)
                                    <tr class="job-row">
                                        <td><input type="checkbox" class="form-check-input row_checkbox" value="{{ $app->id }}" onchange="updateSelectedCount()"></td>

                                        {{-- CANDIDATE --}}
                                        <td>
                                            <div class="fw-semibold job-name">
                                                @if($app->candidate)
                                                    <a href="{{ route('candidates.show', $app->candidate_id) }}" class="text-dark text-decoration-none">{{ $app->name }}</a>
                                                @else
                                                    {{ $app->name }}
                                                @endif
                                            </div>
                                            <div class="small text-muted"><i class="feather-mail me-1"></i>{{ $app->email }}</div>
                                            <div class="small text-muted"><i class="feather-phone me-1"></i>{{ $app->phone }}</div>
                                            <div class="small text-muted"><i class="feather-book me-1"></i>{{ $app->qualification ?: '—' }}</div>
                                            <div class="small text-muted"><i class="feather-grid me-1"></i>{{ $app->department->name ?? 'N/A' }}</div>
                                            <div class="small text-muted"><i class="feather-briefcase me-1"></i>{{ $app->designation }}</div>
                                            <div class="small text-muted"><i class="feather-activity me-1"></i>{{ $app->experience ?: '—' }}</div>
                                        </td>

                                        {{-- REQUIREMENT --}}
                                        <td>
                                            @if($app->requirement)
                                                <span class="badge bg-light text-dark border">{{ $app->requirement->designation->name ?? '—' }}</span><br>
                                                <span class="small text-muted">{{ \Carbon\Carbon::parse($app->requirement->date)->format('d M Y') }}</span>
                                            @else
                                                <span class="small text-muted">Direct application</span>
                                            @endif
                                        </td>

                                        {{-- INTERVIEW --}}
                                        <td>
                                            <div class="small">
                                                <i class="feather-calendar me-1 text-primary"></i>
                                                {{ $app->interview_date ?? '—' }}
                                            </div>
                                            <div class="small text-muted mb-2">
                                                <i class="feather-clock me-1"></i>
                                                {{ $app->interview_time ? \Carbon\Carbon::parse($app->interview_time)->format('h:i A') : '—' }}
                                            </div>
                                            <div class="small text-muted mb-2">
                                                <i class="feather-user me-1"></i>
                                                {{ $app->interviewer->name ?? 'No Interviewer' }}
                                            </div>
                                            @if($app->interview_details)
                                                <a href="{{ $app->interview_details }}" target="_blank"
                                                class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1 px-2 py-1"
                                                style="font-size: 12px; border-radius: 6px;">
                                                    <i class="feather-video"></i> Join Meeting
                                                </a>
                                            @else
                                                <span class="text-muted small">No link</span>
                                            @endif
                                        </td>

                                        {{-- STAGE --}}
                                        <td>
                                            <form method="POST" action="{{ url('/job-applications/update-status/'.$app->id) }}">
                                                @csrf
                                                <select name="status" style="width: 160px;"
                                                    class="form-select form-select-sm job-status-select"
                                                    onchange="this.form.submit()">
                                                    @foreach(\App\Models\JobApplication::STAGES as $stageKey => $stageLabel)
                                                        <option value="{{ $stageKey }}" {{ $app->status === $stageKey ? 'selected' : '' }}>{{ $stageLabel }}</option>
                                                    @endforeach
                                                </select>
                                            </form>
                                            @if($app->hiredEmployee)
                                                <a href="{{ route('employees.index') }}" class="small text-success d-block mt-1">
                                                    <i class="feather-link"></i> Employee record created
                                                </a>
                                            @endif
                                        </td>

                                        {{-- RESUME --}}
                                        <td>
                                            @if($app->resume)
                                                <a href="{{ asset('storage/' . $app->resume) }}" target="_blank"
                                                class="btn btn-sm btn-outline-primary d-inline-flex align-items-center justify-content-center"
                                                style="width:32px; height:32px; border-radius:8px;" title="View Resume">
                                                    <i class="feather-file-text" style="font-size:14px;"></i>
                                                </a>
                                            @else
                                                <span class="text-muted small">—</span>
                                            @endif
                                        </td>

                                        {{-- ACTIONS --}}
                                        <td class="text-end">
                                            <button type="button" class="btn btn-sm btn-icon btn-light-brand" title="Edit" onclick="openEditOffcanvas({{ $app->id }})">
                                                <i class="feather-edit-2"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-icon btn-soft-danger" title="Delete" onclick="deleteApplication({{ $app->id }})">
                                                <i class="feather-trash-2"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-5 text-muted">No candidates found.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <!-- PAGINATION -->
                        @if($applications->hasPages())
                            <div class="card-footer bg-white border-0 py-3 attendance-pagination">
                                {{ $applications->appends(request()->query())->links('pagination::bootstrap-5') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('vacancy._candidate_form')

    <form id="deleteApplicationForm" method="POST" style="display:none;">
        @csrf
        @method('DELETE')
    </form>

    <script>
        // Toggle filter
        document.getElementById("toggleFilter").addEventListener("click", function () {
            let filter = document.getElementById("filterSection");
            filter.style.display = (filter.style.display === "none" || filter.style.display === "") ? "block" : "none";
        });
        @if(request()->hasAny(['search','stage','department_id','job_requirement_id']))
            document.getElementById("filterSection").style.display = "block";
        @endif

        function toggleAllCheckboxes(source) {
            document.querySelectorAll('.row_checkbox').forEach(cb => cb.checked = source.checked);
            updateSelectedCount();
        }

        function updateSelectedCount() {
            const count = document.querySelectorAll('.row_checkbox:checked').length;
            document.getElementById('selectedCount').textContent = count + ' selected';
            document.getElementById('bulk-action-wrapper').style.display = count > 0 ? 'block' : 'none';
        }

        document.getElementById('btn-bulk-delete').addEventListener('click', function () {
            const ids = Array.from(document.querySelectorAll('.row_checkbox:checked')).map(cb => cb.value);
            if (ids.length === 0) return;

            Swal.fire({
                title: 'Delete selected candidates?',
                text: `${ids.length} record(s) will be permanently removed.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, delete',
                reverseButtons: true,
                customClass: { confirmButton: 'btn btn-danger px-4', cancelButton: 'btn btn-light-brand px-4 me-3' },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('{{ route('vacancy.bulk-delete') }}', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
                        body: JSON.stringify({ ids })
                    }).then(() => window.location.reload());
                }
            });
        });

        function deleteApplication(id) {
            Swal.fire({
                title: 'Remove this candidate?',
                text: "This can't be undone.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, delete',
                reverseButtons: true,
                customClass: { confirmButton: 'btn btn-danger px-4', cancelButton: 'btn btn-light-brand px-4 me-3' },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.getElementById('deleteApplicationForm');
                    form.action = `/job-vacancy/${id}`;
                    form.submit();
                }
            });
        }
    </script>
@endsection
