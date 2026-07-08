@extends('layouts.app')

@section('content')
    <!-- [ page-header ] start -->
    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Job Requirement</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                <li class="breadcrumb-item">Job Requirement</li>
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
                    <a href="javascript:void(0)" class="btn btn-light-brand" onclick="openAddOffcanvas()">
                        <i class="feather-user-plus me-2"></i>
                        <span>Recruit Directly</span>
                    </a>
                    <button type="button" class="btn btn-primary" id="showAddRequirement">
                        <i class="feather-plus me-2"></i>
                        <span>Add Requirement</span>
                    </button>
                </div>
            </div>
            <div class="d-md-none d-flex align-items-center">
                <a href="javascript:void(0)" class="page-header-right-open-toggle">
                    <i class="feather-align-right fs-20"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="main-content">
        <div class="container-fluid px-0 mt-2 d-none" id="requirementFormContainer">
            <div class="card shadow border-0">
                <div class="card-header bg-primary text-white">
                    <h4 class="text-white m-0">New Job Requirement</h4>
                </div>

                <div class="card-body">
                    <form action="{{route('requirement.store')}}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Role</label>
                                <div class="dropdown wghrm-modular-dropdown">
                                    <button class="btn btn-outline-secondary w-100 dropdown-toggle text-start d-flex align-items-center justify-content-between wghrm-toggle-btn"
                                            type="button" data-bs-toggle="dropdown" style="height: 44px; border-radius: 12px; border: 1px solid #dcdcdc; background: #fff; color: #4b5563;">
                                        <span>Select Role</span>
                                    </button>
                                    <input type="hidden" name="role_id" class="wghrm-hidden-input" value="" required>

                                    <div class="dropdown-menu wghrm-custom-dropdown-menu w-100">
                                        <div class="wghrm-custom-search-box">
                                            <input type="text" class="wghrm-custom-search-input" placeholder="Search role..." onkeyup="wghrmFilterItems(this)">
                                        </div>
                                        <div class="wghrm-items-container">
                                            <a class="dropdown-item wghrm-custom-dropdown-item active" href="javascript:void(0);" onclick="wghrmSelectOption(this, '')">Select Role</a>
                                            @foreach($roles as $role)
                                                <a class="dropdown-item wghrm-custom-dropdown-item" href="javascript:void(0);" onclick="wghrmSelectOption(this, '{{$role->id}}')">{{$role->name}}</a>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Department</label>
                                <div class="dropdown wghrm-modular-dropdown">
                                    <button class="btn btn-outline-secondary w-100 dropdown-toggle text-start d-flex align-items-center justify-content-between wghrm-toggle-btn"
                                            type="button" data-bs-toggle="dropdown" style="height: 44px; border-radius: 12px; border: 1px solid #dcdcdc; background: #fff; color: #4b5563;">
                                        <span>Select Department</span>
                                    </button>
                                    <input type="hidden" name="department_id" class="wghrm-hidden-input" value="">

                                    <div class="dropdown-menu wghrm-custom-dropdown-menu w-100">
                                        <div class="wghrm-custom-search-box">
                                            <input type="text" class="wghrm-custom-search-input" placeholder="Search department..." onkeyup="wghrmFilterItems(this)">
                                        </div>
                                        <div class="wghrm-items-container">
                                            <a class="dropdown-item wghrm-custom-dropdown-item active" href="javascript:void(0);" onclick="wghrmSelectOption(this, '')">Select Department</a>
                                            @foreach($departments as $dept)
                                                <a class="dropdown-item wghrm-custom-dropdown-item" href="javascript:void(0);" onclick="wghrmSelectOption(this, '{{$dept->id}}')">{{$dept->name}}</a>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Priority</label>
                                <div class="dropdown wghrm-modular-dropdown">
                                    <button class="btn btn-outline-secondary w-100 dropdown-toggle text-start d-flex align-items-center justify-content-between wghrm-toggle-btn"
                                            type="button" data-bs-toggle="dropdown" style="height: 44px; border-radius: 12px; border: 1px solid #dcdcdc; background: #fff; color: #4b5563;">
                                        <span>Select Priority</span>
                                    </button>
                                    <input type="hidden" name="priority" class="wghrm-hidden-input" value="">

                                    <div class="dropdown-menu wghrm-custom-dropdown-menu w-100">
                                        <div class="wghrm-custom-search-box">
                                            <input type="text" class="wghrm-custom-search-input" placeholder="Search priority..." onkeyup="wghrmFilterItems(this)">
                                        </div>
                                        <div class="wghrm-items-container">
                                            <a class="dropdown-item wghrm-custom-dropdown-item active" href="javascript:void(0);" onclick="wghrmSelectOption(this, '')">Select</a>
                                            <a class="dropdown-item wghrm-custom-dropdown-item" href="javascript:void(0);" onclick="wghrmSelectOption(this, 'Urgent')">Urgent</a>
                                            <a class="dropdown-item wghrm-custom-dropdown-item" href="javascript:void(0);" onclick="wghrmSelectOption(this, 'High')">High</a>
                                            <a class="dropdown-item wghrm-custom-dropdown-item" href="javascript:void(0);" onclick="wghrmSelectOption(this, 'Medium')">Medium</a>
                                            <a class="dropdown-item wghrm-custom-dropdown-item" href="javascript:void(0);" onclick="wghrmSelectOption(this, 'Low')">Low</a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label>Required Date</label>
                                <input type="date" name="date" class="form-control">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label>Number of Positions</label>
                                <input type="number" name="positions_count" class="form-control" min="1" value="1">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Fresher / Experience</label>
                                <div class="dropdown wghrm-modular-dropdown">
                                    <button class="btn btn-outline-secondary w-100 dropdown-toggle text-start d-flex align-items-center justify-content-between wghrm-toggle-btn"
                                            type="button" data-bs-toggle="dropdown" style="height: 44px; border-radius: 12px; border: 1px solid #dcdcdc; background: #fff; color: #4b5563;">
                                        <span>Select Type</span>
                                    </button>
                                    <input type="hidden" name="candidate_type" id="candidateType" class="wghrm-hidden-input" value="">

                                    <div class="dropdown-menu wghrm-custom-dropdown-menu w-100">
                                        <div class="wghrm-custom-search-box">
                                            <input type="text" class="wghrm-custom-search-input" placeholder="Search type..." onkeyup="wghrmFilterItems(this)">
                                        </div>
                                        <div class="wghrm-items-container">
                                            <a class="dropdown-item wghrm-custom-dropdown-item active" href="javascript:void(0);" onclick="wghrmSelectOption(this, ''); checkExperienceVisibility('');">Select</a>
                                            <a class="dropdown-item wghrm-custom-dropdown-item" href="javascript:void(0);" onclick="wghrmSelectOption(this, 'Fresher'); checkExperienceVisibility('Fresher');">Fresher</a>
                                            <a class="dropdown-item wghrm-custom-dropdown-item" href="javascript:void(0);" onclick="wghrmSelectOption(this, 'Experience'); checkExperienceVisibility('Experience');">Experience</a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3 d-none" id="experienceDiv">
                                <label>Minimum Experience</label>
                                <input type="number" name="minimum_experience" class="form-control" placeholder="Years">
                            </div>

                            <div class="col-md-12 mb-3">
                                <label>Skills</label>
                                <input type="text" name="skills" class="form-control" placeholder="HTML,CSS,Javascript,React">
                                <small>Use comma separated skills</small>
                            </div>

                            <div class="text-end">
                                <button class="btn btn-primary">Save</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="card mt-4 shadow">
            <div class="card-header d-flex justify-content-between">
                <h5 class="m-0">Requirement List</h5>

                <div class="d-flex align-items-center gap-3">
                    <select class="form-select form-select-sm" style="width: auto;" onchange="window.location = this.value">
                        <option value="{{ request()->fullUrlWithQuery(['department_id' => null]) }}" {{ !request('department_id') ? 'selected' : '' }}>All Departments</option>
                        @foreach($departments as $dept)
                            <option value="{{ request()->fullUrlWithQuery(['department_id' => $dept->id]) }}" {{ (string) request('department_id') === (string) $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                        @endforeach
                    </select>
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
                </div>
            </div>

            <div class="card-body">
                <table class="table table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Role Details</th>
                            <th>Department</th>
                            <th>Positions</th>
                            <th>Skills</th>
                            <th>Status</th>
                            <th>Action</th>
                            <th>Applications</th>
                            <th></th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($requirements as $item)
                            <tr>
                                <td>{{ $loop->iteration }}</td>

                                <td>
                                    <strong>{{ $item->designation->name ?? '—' }}</strong><br>
                                    <small>
                                        Priority: {{ $item->priority }} |
                                        Required Date: {{ $item->date }} |
                                        @if(strtolower($item->candidate_type) == 'experience')
                                            Experience: {{ $item->minimum_experience ?? '0' }} Yrs
                                        @else
                                            Fresher
                                        @endif
                                    </small>
                                </td>

                                <td>{{ $item->department->name ?? '—' }}</td>

                                <td>
                                    <span class="fw-semibold">{{ $item->hired_count }}</span> / {{ $item->positions_count }}
                                    <div class="progress mt-1" style="height: 6px; width: 70px;">
                                        <div class="progress-bar bg-success" style="width: {{ $item->positions_count > 0 ? min(100, round($item->hired_count / $item->positions_count * 100)) : 0 }}%"></div>
                                    </div>
                                </td>

                                <td>
                                    @php
                                        $skills = is_array($item->skills) ? $item->skills : json_decode($item->skills, true);
                                        if (!is_array($skills)) { $skills = explode(',', $item->skills); }
                                    @endphp
                                    @foreach($skills as $skill)
                                        <span class="badge bg-primary text-white">{{ trim($skill) }}</span>
                                    @endforeach
                                </td>

                                <td>
                                    <select class="form-select form-select-sm status-updater" name="status" data-id="{{ $item->id }}">
                                        <option value="hold" {{ ($item->status ?? '') == 'hold' ? 'selected' : '' }}>Hold</option>
                                        <option value="hiring" {{ ($item->status ?? '') == 'hiring' ? 'selected' : '' }}>Hiring</option>
                                        <option value="hired" {{ ($item->status ?? '') == 'hired' ? 'selected' : '' }}>Hired</option>
                                    </select>
                                </td>

                                <td>
                                    <a href="javascript:void(0)" class="btn btn-sm btn-primary"
                                        onclick="openAddOffcanvas({{ $item->id }}, '{{ addslashes($item->designation->name ?? '') }}')">
                                        Schedule Interview
                                    </a>
                                </td>
                                <td>
                                    @if(($item->applications_count ?? 0) > 0)
                                        <a href="{{ route('vacancy.show', ['job_requirement_id' => $item->id]) }}"
                                        class="badge bg-info text-white text-decoration-none">
                                            <i class="feather-users me-1"></i> {{ $item->applications_count }}
                                        </a>
                                    @else
                                        <span class="text-muted">0</span>
                                    @endif
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-icon btn-soft-danger" title="Delete" onclick="deleteRequirement({{ $item->id }})">
                                        <i class="feather-trash-2"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">No requirements yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <!-- PAGINATION -->
                @if($requirements->hasPages())
                    <div class="card-footer bg-white border-0 py-3 attendance-pagination">
                        {{ $requirements->appends(request()->query())->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    @include('vacancy._candidate_form')

    <form id="deleteRequirementForm" method="POST" style="display:none;">
        @csrf
        @method('DELETE')
    </form>

    <style>
        .wghrm-custom-select-btn {
            background-color: #fff !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 12px !important;
            color: #1e293b !important;
            padding: 10px 16px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            width: 100% !important;
            height: 48px !important;
            font-size: 14px !important;
            font-weight: 600 !important;
            transition: all 0.2s !important;
            text-align: left !important;
        }
        .wghrm-custom-select-btn:focus {
            border-color: #3858f9 !important;
            box-shadow: 0 0 0 4px rgba(56, 88, 249, 0.1) !important;
            outline: none !important;
        }
    </style>

@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const candidateType = document.getElementById('candidateType');
            const experienceDiv = document.getElementById('experienceDiv');

            candidateType.addEventListener('change', function () {
                if (this.value === 'Experience') {
                    experienceDiv.classList.remove('d-none');
                } else {
                    experienceDiv.classList.add('d-none');
                }
            });
        });

        $(document).ready(function() {
            $('.status-updater').on('change', function() {
                let status = $(this).val();
                let requirementId = $(this).data('id');

                $.ajax({
                    url: "{{ route('requirements.update-status') }}",
                    type: "POST",
                    data: { _token: "{{ csrf_token() }}", id: requirementId, status: status },
                    success: function(response) {
                        if (typeof toastr !== 'undefined') {
                            toastr.success('Status updated successfully!');
                        }
                    },
                    error: function() {
                        if (typeof toastr !== 'undefined') {
                            toastr.error('Failed to update status.');
                        }
                    }
                });
            });
        });

        document.addEventListener('DOMContentLoaded', function () {
            const showBtn = document.getElementById('showAddRequirement');
            const formContainer = document.getElementById('requirementFormContainer');

            if (showBtn && formContainer) {
                showBtn.addEventListener('click', function () {
                    formContainer.classList.toggle('d-none');
                });
            }
        });

        function checkExperienceVisibility(value) {
            let expDiv = document.getElementById('experienceDiv');
            if (value === 'Experience') {
                expDiv.classList.remove('d-none');
            } else {
                expDiv.classList.add('d-none');
                expDiv.querySelector('input').value = '';
            }
        }

        function wghrmSelectOption(element, value) {
            let dropdownWrapper = element.closest('.wghrm-modular-dropdown');
            let displayLabel = element.innerText || element.textContent;
            dropdownWrapper.querySelector('.wghrm-toggle-btn span').innerText = displayLabel;
            dropdownWrapper.querySelector('.wghrm-hidden-input').value = value;

            let itemsContainer = element.closest('.wghrm-items-container');
            itemsContainer.querySelectorAll('.wghrm-custom-dropdown-item').forEach(el => el.classList.remove('active'));
            element.classList.add('active');
        }

        function deleteRequirement(id) {
            Swal.fire({
                title: 'Delete this requirement?',
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
                    const form = document.getElementById('deleteRequirementForm');
                    form.action = `/job-requirement/${id}`;
                    form.submit();
                }
            });
        }
    </script>
@endpush
