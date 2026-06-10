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
                    <div id="bulk-action-wrapper" style="display: none;">
                        <a href="javascript:void(0);" id="btn-bulk-delete" class="btn btn-icon btn-soft-danger"
                            style="width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; 
                            justify-content: center;">
                            <i class="feather-trash-2 fs-18"></i>
                        </a>
                    </div>
                    <div class="filter-toggle-wrapper">
                        <!-- <a href="javascript:void(0);" class="btn btn-icon btn-light-brand" id="toggleFilter"
                            style="cursor: pointer;">
                            <i class="feather-filter"></i>
                        </a> -->
                    </div>
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
        <div class="container mt-4 d-none" id="requirementFormContainer">
            <div class="card shadow border-0">
                <div class="card-header bg-primary text-white">
                    <h4 class="text-white">Job Requirement</h4>
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
                                <label class="form-label">Fresher / Experience</label>
                                <div class="dropdown wghrm-modular-dropdown">
                                    <button class="btn btn-outline-secondary w-100 dropdown-toggle text-start d-flex align-items-center justify-content-between wghrm-toggle-btn" 
                                            type="button" data-bs-toggle="dropdown" style="height: 44px; border-radius: 12px; border: 1px solid #dcdcdc; background: #fff; color: #4b5563;">
                                        <span>Select Type</span>
                                    </button>
                                    <!-- Retained candidateType ID dynamically inside hidden input field wrapper -->
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
                <h5>Requirement List</h5>

                <!-- SHOW ENTRIES -->
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

            <div class="card-body">
                <table class="table table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Role Details</th>
                            <th>Skills</th>
                            <th>Status</th>
                            <th>Action</th>
                            <th>Interview Count</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($requirements as $item)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                
                                <td>
                                    <strong>{{ $item->role_name }}</strong><br>
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

                                <td>
                                    @php
                                        $skills = is_array($item->skills)
                                            ? $item->skills
                                            : json_decode($item->skills, true);

                                        if (!is_array($skills)) {
                                            $skills = explode(',', $item->skills);
                                        }
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
                                     <a
                                        href="javascript:void(0)"
                                        class="btn btn-sm btn-primary"
                                        data-bs-toggle="offcanvas"
                                        data-bs-target="#projectOffcanvas"
                                        data-designation="{{ $item->role_name }}"
                                    >
                                        Schedule Interview
                                    </a>
                                </td>
                                <td>
                                    @if(($item->applications_count ?? 0) > 0)
                                        <a href="{{ action([App\Http\Controllers\VacancyController::class, 'show'], ['role' => $item->role_name]) }}" 
                                        class="badge bg-info text-white text-decoration-none">
                                            <i class="feather-users me-1"></i> {{ $item->applications_count }}
                                        </a>
                                    @else
                                        <span class="text-muted">0</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
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

    <div class="offcanvas offcanvas-end" tabindex="-1" id="projectOffcanvas" style="width:600px;">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title d-flex align-items-center gap-2">
                <i class="feather-user-plus text-primary"></i>
                Candidate Information
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>

        <div class="offcanvas-body">
            <form action="{{ url('/job-vacancy/store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <!-- Basic Information -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3 text-primary">
                            Basic Information
                        </h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Full Name </label>
                                <input type="text" class="form-control" name="name">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Mobile</label>
                                <input type="text" class="form-control" name="phone">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Qualification</label>
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary w-100 dropdown-toggle text-start d-flex align-items-center justify-content-between" 
                                            type="button" id="qualOffcanvasBtn" data-bs-toggle="dropdown" style="height: 44px; border-radius: 12px; border: 1px solid #dcdcdc; background: #fff; color: #4b5563;">
                                        <span>Select Qualification</span>
                                    </button>
                                    <input type="hidden" name="qualification" id="offcanvasQualification" value="" required>
                                    
                                    <div class="dropdown-menu wghrm-custom-dropdown-menu w-100">
                                        <div class="wghrm-custom-search-box">
                                            <input type="text" class="wghrm-custom-search-input" placeholder="Search qualification..." onkeyup="wghrmFilterItems(this)">
                                        </div>
                                        <div class="wghrm-items-container">
                                            <a class="dropdown-item wghrm-custom-dropdown-item active" href="javascript:void(0);" onclick="updateOffcanvasDropdown('offcanvasQualification', '', 'qualOffcanvasBtn', 'Select Qualification', this)">Select Qualification</a>
                                            <a class="dropdown-item wghrm-custom-dropdown-item" href="javascript:void(0);" onclick="updateOffcanvasDropdown('offcanvasQualification', 'B.Tech', 'qualOffcanvasBtn', 'B.Tech', this)">B.Tech</a>
                                            <a class="dropdown-item wghrm-custom-dropdown-item" href="javascript:void(0);" onclick="updateOffcanvasDropdown('offcanvasQualification', 'BCA', 'qualOffcanvasBtn', 'BCA', this)">BCA</a>
                                            <a class="dropdown-item wghrm-custom-dropdown-item" href="javascript:void(0);" onclick="updateOffcanvasDropdown('offcanvasQualification', 'MCA', 'qualOffcanvasBtn', 'MCA', this)">MCA</a>
                                            <a class="dropdown-item wghrm-custom-dropdown-item" href="javascript:void(0);" onclick="updateOffcanvasDropdown('offcanvasQualification', 'M.Tech', 'qualOffcanvasBtn', 'M.Tech', this)">M.Tech</a>
                                            <a class="dropdown-item wghrm-custom-dropdown-item" href="javascript:void(0);" onclick="updateOffcanvasDropdown('offcanvasQualification', 'B.Sc IT', 'qualOffcanvasBtn', 'B.Sc IT', this)">B.Sc IT</a>
                                            <a class="dropdown-item wghrm-custom-dropdown-item" href="javascript:void(0);" onclick="updateOffcanvasDropdown('offcanvasQualification', 'M.Sc IT', 'qualOffcanvasBtn', 'M.Sc IT', this)">M.Sc IT</a>
                                            <a class="dropdown-item wghrm-custom-dropdown-item" href="javascript:void(0);" onclick="updateOffcanvasDropdown('offcanvasQualification', 'B.Sc Computer Science', 'qualOffcanvasBtn', 'B.Sc Computer Science', this)">B.Sc Computer Science</a>
                                            <a class="dropdown-item wghrm-custom-dropdown-item" href="javascript:void(0);" onclick="updateOffcanvasDropdown('offcanvasQualification', 'M.Sc Computer Science', 'qualOffcanvasBtn', 'M.Sc Computer Science', this)">M.Sc Computer Science</a>
                                            <a class="dropdown-item wghrm-custom-dropdown-item" href="javascript:void(0);" onclick="updateOffcanvasDropdown('offcanvasQualification', 'BE Computer Engineering', 'qualOffcanvasBtn', 'BE Computer Engineering', this)">BE Computer Engineering</a>
                                            <a class="dropdown-item wghrm-custom-dropdown-item" href="javascript:void(0);" onclick="updateOffcanvasDropdown('offcanvasQualification', 'Diploma in Computer Engineering', 'qualOffcanvasBtn', 'Diploma in Computer Engineering', this)">Diploma in Computer Engineering</a>
                                            <a class="dropdown-item wghrm-custom-dropdown-item" href="javascript:void(0);" onclick="updateOffcanvasDropdown('offcanvasQualification', 'PGDCA', 'qualOffcanvasBtn', 'PGDCA', this)">PGDCA</a>
                                            <a class="dropdown-item wghrm-custom-dropdown-item" href="javascript:void(0);" onclick="updateOffcanvasDropdown('offcanvasQualification', 'MBA IT', 'qualOffcanvasBtn', 'MBA IT', this)">MBA IT</a>
                                            <a class="dropdown-item wghrm-custom-dropdown-item" href="javascript:void(0);" onclick="updateOffcanvasDropdown('offcanvasQualification', 'Full Stack Development', 'qualOffcanvasBtn', 'Full Stack Development', this)">Full Stack Development</a>
                                            <a class="dropdown-item wghrm-custom-dropdown-item" href="javascript:void(0);" onclick="updateOffcanvasDropdown('offcanvasQualification', 'Cyber Security', 'qualOffcanvasBtn', 'Cyber Security', this)">Cyber Security</a>
                                            <a class="dropdown-item wghrm-custom-dropdown-item" href="javascript:void(0);" onclick="updateOffcanvasDropdown('offcanvasQualification', 'Data Science', 'qualOffcanvasBtn', 'Data Science', this)">Data Science</a>
                                            <a class="dropdown-item wghrm-custom-dropdown-item" href="javascript:void(0);" onclick="updateOffcanvasDropdown('offcanvasQualification', 'Artificial Intelligence', 'qualOffcanvasBtn', 'Artificial Intelligence', this)">Artificial Intelligence</a>
                                            <a class="dropdown-item wghrm-custom-dropdown-item" href="javascript:void(0);" onclick="updateOffcanvasDropdown('offcanvasQualification', 'Machine Learning', 'qualOffcanvasBtn', 'Machine Learning', this)">Machine Learning</a>
                                            <a class="dropdown-item wghrm-custom-dropdown-item" href="javascript:void(0);" onclick="updateOffcanvasDropdown('offcanvasQualification', 'Other', 'qualOffcanvasBtn', 'Other', this)">Other</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Department</label>
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary w-100 dropdown-toggle text-start d-flex align-items-center justify-content-between" 
                                            type="button" id="deptOffcanvasBtn" data-bs-toggle="dropdown" style="height: 44px; border-radius: 12px; border: 1px solid #dcdcdc; background: #fff; color: #4b5563;">
                                        <span>Select Department</span>
                                    </button>
                                    <input type="hidden" name="department_id" id="offcanvasDepartment" value="" required>
                                    
                                    <div class="dropdown-menu wghrm-custom-dropdown-menu w-100">
                                        <div class="wghrm-custom-search-box">
                                            <input type="text" class="wghrm-custom-search-input" placeholder="Search department..." onkeyup="wghrmFilterItems(this)">
                                        </div>
                                        <div class="wghrm-items-container">
                                            <a class="dropdown-item wghrm-custom-dropdown-item active" href="javascript:void(0);" onclick="updateOffcanvasDropdown('offcanvasDepartment', '', 'deptOffcanvasBtn', 'Select Department', this)">Select Department</a>
                                            @foreach($departments as $department)
                                                <a class="dropdown-item wghrm-custom-dropdown-item" href="javascript:void(0);" onclick="updateOffcanvasDropdown('offcanvasDepartment', '{{ $department->id }}', 'deptOffcanvasBtn', '{{ $department->name }}', this)">{{ $department->name }}</a>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3 d-none">
                                <label class="form-label">Designation</label>
                                <input type="text" class="form-control" name="designation" id="requirementDesignation" readonly>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Experience</label>
                                <input type="text" class="form-control" placeholder="1 years" name="experience">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Interview -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3 text-primary">Interview Information</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Interview Date</label>
                                <input type="date" value="{{ date('Y-m-d') }}" class="form-control" name="interview_date">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Interview Time</label>
                                <input type="time" class="form-control" name="interview_time">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status</label>
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary w-100 dropdown-toggle text-start d-flex align-items-center justify-content-between" 
                                            type="button" id="statusOffcanvasBtn" data-bs-toggle="dropdown" style="height: 44px; border-radius: 12px; border: 1px solid #dcdcdc; background: #fff; color: #4b5563;">
                                        <span>Pending</span>
                                    </button>
                                    <input type="hidden" name="status" id="offcanvasStatus" value="Pending">
                                    
                                    <div class="dropdown-menu wghrm-custom-dropdown-menu w-100">
                                        <div class="wghrm-custom-search-box">
                                            <input type="text" class="wghrm-custom-search-input" placeholder="Search status..." onkeyup="wghrmFilterItems(this)">
                                        </div>
                                        <div class="wghrm-items-container">
                                            <a class="dropdown-item wghrm-custom-dropdown-item active" href="javascript:void(0);" onclick="updateOffcanvasDropdown('offcanvasStatus', 'Pending', 'statusOffcanvasBtn', 'Pending', this)">Pending</a>
                                            <a class="dropdown-item wghrm-custom-dropdown-item" href="javascript:void(0);" onclick="updateOffcanvasDropdown('offcanvasStatus', 'Selected', 'statusOffcanvasBtn', 'Selected', this)">Selected</a>
                                            <a class="dropdown-item wghrm-custom-dropdown-item" href="javascript:void(0);" onclick="updateOffcanvasDropdown('offcanvasStatus', 'Awaited', 'statusOffcanvasBtn', 'Awaited', this)">Awaited</a>
                                            <a class="dropdown-item wghrm-custom-dropdown-item" href="javascript:void(0);" onclick="updateOffcanvasDropdown('offcanvasStatus', 'Rejected', 'statusOffcanvasBtn', 'Rejected', this)">Rejected</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Interviewer</label>
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary w-100 dropdown-toggle text-start d-flex align-items-center justify-content-between" 
                                            type="button" id="interviewerOffcanvasBtn" data-bs-toggle="dropdown" style="height: 44px; border-radius: 12px; border: 1px solid #dcdcdc; background: #fff; color: #4b5563;">
                                        <span>Select Interviewer</span>
                                    </button>
                                    <input type="hidden" name="interviewer_id" id="offcanvasInterviewer" value="">
                                    
                                    <div class="dropdown-menu wghrm-custom-dropdown-menu w-100">
                                        <div class="wghrm-custom-search-box">
                                            <input type="text" class="wghrm-custom-search-input" placeholder="Search interviewer..." onkeyup="wghrmFilterItems(this)">
                                        </div>
                                        <div class="wghrm-items-container">
                                            <a class="dropdown-item wghrm-custom-dropdown-item active" href="javascript:void(0);" onclick="updateOffcanvasDropdown('offcanvasInterviewer', '', 'interviewerOffcanvasBtn', 'Select Interviewer', this)">Select Interviewer</a>
                                            @foreach($employees as $employee)
                                                <a class="dropdown-item wghrm-custom-dropdown-item" href="javascript:void(0);" onclick="updateOffcanvasDropdown('offcanvasInterviewer', '{{ $employee->id }}', 'interviewerOffcanvasBtn', '{{ $employee->name }}', this)">{{ $employee->name }}</a>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md">
                                <label class="form-label">Interview Description / Link</label>
                                <textarea class="form-control" name="interview_details" rows="3"
                                    placeholder="Enter interview description or meeting link"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Profile -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3 text-primary">Profile Upload</h6>
                        <div class="mb-3">
                            <label class="form-label">Upload Resume/Profile</label>
                            <input type="file" class="form-control" name="resume" id="resume" accept=".pdf,.doc,.docx">
                            <div id="resumeError" class="text-danger small mt-1 d-none">
                                Resume size should not exceed 2 MB.
                            </div>
                            <small class="text-muted"> Accepted formats: PDF, DOC, DOCX | Maximum file size: 2 MB</small>
                        </div>
                    </div>
                </div>
                <button class="btn btn-primary w-100">
                    <i class="feather-save me-2"></i>
                    Save Candidate
                </button>
            </form>
        </div>
    </div>

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
        /* Custom Dropdown Container Menu */
        .wghrm-custom-dropdown-menu {
            border-radius: 12px !important;
            padding: 8px !important;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08) !important;
            border: 1px solid #e4e6ef !important;
            margin-top: 4px !important;
        }

        /* Search Box Area inside Dropdown */
        .wghrm-custom-search-box {
            padding: 2px 4px 8px 4px;
        }

        .wghrm-custom-search-input {
            width: 100%;
            border: 1px solid #ebedf3;
            background-color: #f5f8fa;
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 13px;
            outline: none;
        }

        .wghrm-custom-search-input:focus {
            border-color: #4e73df;
            background-color: #fff;
        }

        /* Scrollbar Viewport Limiter for Too Many Options */
        .wghrm-items-container {
            max-height: 200px; /* Limits dropdown height */
            overflow-y: auto;  /* Adds the scrollbar automatically */
        }

        /* List Items Option Styling */
        .wghrm-custom-dropdown-item {
            border-radius: 8px !important;
            padding: 8px 12px !important;
            font-size: 14px;
            color: #4b5563 !important;
            margin-bottom: 2px;
        }

        .wghrm-custom-dropdown-item:hover {
            background-color: #f1f3f9 !important;
            color: #4e73df !important;
        }

        .wghrm-custom-dropdown-item.active {
            background-color: #f1f3f9 !important;
            color: #4e73df !important;
            font-weight: 500;
        }
    </style>

@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const candidateType = document.getElementById('candidateType');
            const experienceDiv = document.getElementById('experienceDiv');
            const projectOffcanvas = document.getElementById('projectOffcanvas');
            const requirementDesignation = document.getElementById('requirementDesignation');
            
            // Handle change event
            candidateType.addEventListener('change', function () {
                if (this.value === 'Experience') {
                    experienceDiv.classList.remove('d-none');
                } else {
                    experienceDiv.classList.add('d-none');
                }
            });

            if (projectOffcanvas && requirementDesignation) {
                projectOffcanvas.addEventListener('show.bs.offcanvas', function (event) {
                    const trigger = event.relatedTarget;
                    requirementDesignation.value = trigger?.getAttribute('data-designation') || '';
                });
            }
        });

        $(document).ready(function() {
            $('.status-updater').on('change', function() {
                let status = $(this).val();
                let requirementId = $(this).data('id');

                $.ajax({
                    url: "{{ route('requirements.update-status') }}",
                    type: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        id: requirementId,
                        status: status
                    },
                    success: function(response) {
                        if(response.success) {
                            alert('Status updated successfully!');
                        } else {
                            alert('Something went wrong.');
                        }
                    },
                    error: function() {
                        alert('Server error. Failed to update status.');
                    }
                });
            });
        });

        document.addEventListener('DOMContentLoaded', function () {
            const showBtn = document.getElementById('showAddRequirement');
            const formContainer = document.getElementById('requirementFormContainer');

            if (showBtn && formContainer) {
                showBtn.addEventListener('click', function () {
                    // Check if the container is hidden, then show it; otherwise hide it
                    if (formContainer.classList.contains('d-none')) {
                        formContainer.classList.remove('d-none');
                    } else {
                        formContainer.classList.add('d-none');
                    }
                });
            }
        });

        function updateOffcanvasDropdown(inputId, val, btnId, text, element) {
            document.getElementById(inputId).value = val;
            document.getElementById(btnId).querySelector('span').innerText = text;
            
            let container = element.closest('.wghrm-items-container');
            container.querySelectorAll('.wghrm-custom-dropdown-item').forEach(el => el.classList.remove('active'));
            element.classList.add('active');
        }

        function checkExperienceVisibility(value) {
            let expDiv = document.getElementById('experienceDiv');
            if (value === 'Experience') {
                expDiv.classList.remove('d-none');
            } else {
                expDiv.classList.add('d-none');
                // Clear input value if switched back to Fresher
                expDiv.querySelector('input').value = ''; 
            }
        }

        // Function 1: Handles option selection dynamically by finding components relatively 
        function wghrmSelectOption(element, value) {
            // Find the closest custom modular wrapper container context
            let dropdownWrapper = element.closest('.wghrm-modular-dropdown');
            
            // Get the clean option label text and set it onto our main display toggle button
            let displayLabel = element.innerText || element.textContent;
            dropdownWrapper.querySelector('.wghrm-toggle-btn span').innerText = displayLabel;
            
            // Find our hidden form input field and assign the true selection value to it
            dropdownWrapper.querySelector('.wghrm-hidden-input').value = value;
            
            // Reset active highlight states on list neighbors and set onto selected element
            let itemsContainer = element.closest('.wghrm-items-container');
            itemsContainer.querySelectorAll('.wghrm-custom-dropdown-item').forEach(el => el.classList.remove('active'));
            element.classList.add('active');
        }

        // Function 2: Pure Vanilla live text filter lookup search engine
        function wghrmFilterItems(input) {
            let filter = input.value.toLowerCase();
            let container = input.closest('.dropdown-menu').querySelector('.wghrm-items-container');
            let items = container.getElementsByClassName('wghrm-custom-dropdown-item');
            
            for (let i = 0; i < items.length; i++) {
                let text = items[i].textContent || items[i].innerText;
                if (text.toLowerCase().indexOf(filter) > -1) {
                    items[i].style.setProperty('display', '', 'important');
                } else {
                    items[i].style.setProperty('display', 'none', 'important');
                }
            }
        }
    </script>
@endpush
