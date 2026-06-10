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
                            justify-content: center;">
                            <i class="feather-trash-2 fs-18"></i>
                        </a>
                    </div>
                    <div class="filter-toggle-wrapper">
                        <a href="javascript:void(0);" class="btn btn-icon btn-light-brand" id="toggleFilter"
                            style="cursor: pointer;">
                            <i class="feather-filter"></i>
                        </a>
                    </div>
                    <a href="javascript:void(0)" class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#projectOffcanvas">
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
                <div class="col-xxl col-md-4">
                    <div class="card stretch stretch-full border-start border-4 border-secondary">
                        <div class="card-body p-3">
                            <div class="hstack justify-content-between">
                                <div>
                                    <span class="fs-10 fw-bold text-uppercase d-block mb-1">Pending</span>
                                    <span class="fs-20 fw-bolder d-block">{{ $pendingCount }}</span>
                                </div>
                                <div class="avatar-text avatar-md bg-soft-secondary text-secondary">
                                    <i class="feather-pause-circle"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xxl col-md-4">
                    <div class="card stretch stretch-full border-start border-4 border-warning">
                        <div class="card-body p-3">
                            <div class="hstack justify-content-between">
                                <div>
                                    <span class="fs-10 fw-bold text-uppercase d-block mb-1">Awaited</span>
                                    <span class="fs-20 fw-bolder d-block">{{ $awaitedCount }}</span>
                                </div>
                                <div class="avatar-text avatar-md bg-soft-warning text-warning">
                                    <i class="feather-minus-circle"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xxl col-md-4">
                    <div class="card stretch stretch-full border-start border-4 border-danger">
                        <div class="card-body p-3">
                            <div class="hstack justify-content-between">
                                <div>
                                    <span class="fs-10 fw-bold text-uppercase d-block mb-1">Rejected</span>
                                    <span class="fs-20 fw-bolder d-block">{{ $rejectedCount }}</span>
                                </div>
                                <div class="avatar-text avatar-md bg-soft-danger text-danger">
                                    <i class="feather-refresh-ccw"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xxl col-md-4">
                    <div class="card stretch stretch-full border-start border-4 border-success">
                        <div class="card-body p-3">
                            <div class="hstack justify-content-between">
                                <div>
                                    <span class="fs-10 fw-bold text-uppercase d-block mb-1">Selected</span>
                                    <span class="fs-20 fw-bolder d-block">{{ $selectedCount }}</span>
                                </div>
                                <div class="avatar-text avatar-md bg-soft-success text-success">
                                    <i class="feather-check-circle"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- [ page-header ] end -->

    <!-- filter section -->
     <div class="filter-wrapper" id="filterSection" style="display: none;">
        <div class="card stretch stretch-full border-bottom bg-light bg-opacity-10 p-4 mb-4">
            <div class="row g-3">

                <!-- NAME SEARCH -->
                <div class="col-md-4">
                    <label class="form-label">Applicant Name</label>
                    <input type="text"
                        id="filterName"
                        class="form-control"
                        placeholder="Search by name..."
                        onkeyup="applyFilters()">
                </div>

                <!-- STATUS -->
                <div class="dropdown" style="width: 27% !important;">
                    <label class="form-label">All Status</label>
                    <button class="btn btn-outline-secondary w-100 dropdown-toggle text-start d-flex align-items-center justify-content-between" 
                            type="button" id="statusDropdownBtn" data-bs-toggle="dropdown" style="height: 46px; border-radius: 12px; border: 1px solid #dcdcdc; background: #fff;">
                        <span>All Status</span>
                    </button>
                    <input type="hidden" id="filterStatus" value="">
                    
                    <div class="dropdown-menu wghrm-custom-dropdown-menu w-100">
                        <div class="wghrm-custom-search-box">
                            <input type="text" class="wghrm-custom-search-input" placeholder="Search status..." onkeyup="wghrmFilterItems(this)">
                        </div>
                        <div class="wghrm-items-container">
                            <a class="dropdown-item wghrm-custom-dropdown-item active" href="javascript:void(0);" 
                            onclick="document.getElementById('filterStatus').value=''; document.getElementById('statusDropdownBtn').querySelector('span').innerText='All Status'; applyFilters();">All Status</a>
                            <a class="dropdown-item wghrm-custom-dropdown-item" href="javascript:void(0);" 
                            onclick="document.getElementById('filterStatus').value='pending'; document.getElementById('statusDropdownBtn').querySelector('span').innerText='Pending'; applyFilters();">Pending</a>
                            <a class="dropdown-item wghrm-custom-dropdown-item" href="javascript:void(0);" 
                            onclick="document.getElementById('filterStatus').value='awaited'; document.getElementById('statusDropdownBtn').querySelector('span').innerText='Awaited'; applyFilters();">Awaited</a>
                            <a class="dropdown-item wghrm-custom-dropdown-item" href="javascript:void(0);" 
                            onclick="document.getElementById('filterStatus').value='rejected'; document.getElementById('statusDropdownBtn').querySelector('span').innerText='Rejected'; applyFilters();">Rejected</a>
                            <a class="dropdown-item wghrm-custom-dropdown-item" href="javascript:void(0);" 
                            onclick="document.getElementById('filterStatus').value='selected'; document.getElementById('statusDropdownBtn').querySelector('span').innerText='Selected'; applyFilters();">Selected</a>
                        </div>
                    </div>
                </div>

                <!-- DEPARTMENT -->
                <div class="dropdown" style="width: 27% !important;">
                    <label class="form-label">All Departments</label>
                    <button class="btn btn-outline-secondary w-100 dropdown-toggle text-start d-flex align-items-center justify-content-between" 
                            type="button" id="deptDropdownBtn" data-bs-toggle="dropdown" style="height: 46px; border-radius: 12px; border: 1px solid #dcdcdc; background: #fff;">
                        <span>All Departments</span>
                    </button>
                    <input type="hidden" id="filterDepartment" value="">
                    
                    <div class="dropdown-menu wghrm-custom-dropdown-menu w-100">
                        <div class="wghrm-custom-search-box">
                            <input type="text" class="wghrm-custom-search-input" placeholder="Search department..." onkeyup="wghrmFilterItems(this)">
                        </div>
                        <div class="wghrm-items-container">
                            <a class="dropdown-item wghrm-custom-dropdown-item active" href="javascript:void(0);" 
                            onclick="document.getElementById('filterDepartment').value=''; document.getElementById('deptDropdownBtn').querySelector('span').innerText='All Departments'; applyFilters();">All Departments</a>
                            @foreach($departments as $dept)
                                <a class="dropdown-item wghrm-custom-dropdown-item" href="javascript:void(0);" 
                                onclick="document.getElementById('filterDepartment').value='{{ $dept->name }}'; document.getElementById('deptDropdownBtn').querySelector('span').innerText='{{ $dept->name }}'; applyFilters();">{{ $dept->name }}</a>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- BUTTONS -->
                <div class="col-md-1 d-flex align-items-end justify-content-center">
                    <!-- <button type="button"
                        class="btn btn-primary flex-grow-1 fw-bold"
                        onclick="applyFilters()"
                        style="height: 48px; border-radius: 12px;">
                        APPLY
                    </button> -->

                    <button class="btn btn-light"
                        onclick="resetFilters()"
                        style="height: 48px; width: 60px; border-radius: 12px;">
                        <i class="feather-refresh-cw"></i>
                    </button>
                </div>

            </div>
        </div>
    </div>

    <!-- Main containt  -->
    <div class="main-content">
        <div class="row">
            <div class="col-lg-12">
                <div class="card stretch stretch-full">
                    <div class="card-body p-0">
                        <!-- SHOW ENTRIES -->
                        <div class="px-4 py-3 border-bottom d-flex align-items-center gap-2">
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

                        <div class="table-responsive">
                            @if(!empty($selectedRole))
                                <div class="alert alert-info d-flex justify-content-between align-items-center role-filter-alert" role="alert">
                                    <span>
                                        Showing applications filtered by: <strong>{{ $selectedRole }}</strong>
                                    </span>
                                    <a href="{{ action([App\Http\Controllers\VacancyController::class, 'show']) }}" class="btn btn-sm btn-primary">
                                        Show All Data
                                    </a>
                                </div>
                            @endif
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Basic Information</th>
                                        <th>Interview</th>
                                        <th>Status</th>
                                        <th>Resume</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @foreach($applications as $app)
                                    <tr class="job-row">

                                        {{-- BASIC INFORMATION --}}
                                        <td>
                                            <div class="fw-semibold job-name">{{ $app->name }}</div>

                                            <div class="small text-muted">
                                                <i class="feather-mail me-1"></i> {{ $app->email }}
                                            </div>

                                            <div class="small text-muted">
                                                <i class="feather-phone me-1"></i> {{ $app->phone }}
                                            </div>

                                            <div class="small text-muted">
                                                <i class="feather-book me-1"></i> {{ $app->qualification }}
                                            </div>

                                            <div class="small text-muted">
                                                <i class="feather-grid me-1"></i> <span class="job-department">{{ $app->department->name ?? 'N/A' }}</span>
                                            </div>

                                            <div class="small text-muted">
                                                <i class="feather-briefcase me-1"></i> {{ $app->designation }}
                                            </div>

                                            <div class="small text-muted">
                                                <i class="feather-activity me-1"></i> {{ $app->experience }}
                                            </div>
                                        </td>

                                        {{-- INTERVIEW INFORMATION --}}
                                        <td>
                                            <div class="small">
                                                <i class="feather-calendar me-1 text-primary"></i>
                                                {{ $app->interview_date }}
                                            </div>

                                            <div class="small text-muted mb-2">
                                                <i class="feather-clock me-1"></i>
                                                {{ \Carbon\Carbon::parse($app->interview_time)->format('h:i A') }}
                                            </div>
                                            <div class="small text-muted mb-2">
                                                <i class="feather-user me-1"></i>
                                                {{ $app->interviewer->name ?? 'No Interviewer' }}
                                            </div>

                                            @if($app->interview_details)
                                                <a href="{{ $app->interview_details }}"
                                                target="_blank"
                                                class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1 px-2 py-1"
                                                style="font-size: 12px; border-radius: 6px;">
                                                    <i class="feather-video"></i>
                                                    Join Meeting
                                                </a>
                                            @else
                                                <span class="text-muted small">No link</span>
                                            @endif
                                        </td>

                                        {{-- STATUS --}}
                                        <td>
                                            <form method="POST" action="{{ url('/job-applications/update-status/'.$app->id) }}">
                                                @csrf

                                                <select name="status" style="width: 120px;"
                                                    class="form-select form-select-sm job-status-select"
                                                    onchange="this.form.submit()">

                                                    <option value="Pending" {{ $app->status == 'Pending' ? 'selected' : '' }}>Pending</option>
                                                    <option value="Selected" {{ $app->status == 'Selected' ? 'selected' : '' }}>Selected</option>
                                                    <option value="Awaited" {{ $app->status == 'Awaited' ? 'selected' : '' }}>Awaited</option>
                                                    <option value="Rejected" {{ $app->status == 'Rejected' ? 'selected' : '' }}>Rejected</option>

                                                </select>
                                            </form>
                                        </td>

                                        {{-- RESUME --}}
                                        <td>
                                            <a href="{{ asset('storage/' . $app->resume) }}"
                                            target="_blank"
                                            class="btn btn-sm btn-outline-primary d-inline-flex align-items-center justify-content-center"
                                            style="width:32px; height:32px; border-radius:8px;"
                                            title="View Resume">

                                                <i class="feather-file-text" style="font-size:14px;"></i>
                                            </a>
                                        </td>

                                    </tr>
                                    @endforeach
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

    <!-- Add section  -->
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
                
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3 text-primary">
                            <!-- Basic Information -->
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
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Designation</label>
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary w-100 dropdown-toggle text-start d-flex align-items-center justify-content-between" 
                                            type="button" id="desigOffcanvasBtn" data-bs-toggle="dropdown" style="height: 44px; border-radius: 12px; border: 1px solid #dcdcdc; background: #fff; color: #4b5563;">
                                        <span>Select Designation</span>
                                    </button>
                                    <input type="hidden" name="designation" id="offcanvasDesignation" value="" required>
                                    
                                    <div class="dropdown-menu wghrm-custom-dropdown-menu w-100">
                                        <div class="wghrm-custom-search-box">
                                            <input type="text" class="wghrm-custom-search-input" placeholder="Search designation..." onkeyup="wghrmFilterItems(this)">
                                        </div>
                                        <div class="wghrm-items-container">
                                            <a class="dropdown-item wghrm-custom-dropdown-item active" href="javascript:void(0);" onclick="updateOffcanvasDropdown('offcanvasDesignation', '', 'desigOffcanvasBtn', 'Select Designation', this)">Select Designation</a>
                                            @foreach($designations as $designation)
                                                <a class="dropdown-item wghrm-custom-dropdown-item" href="javascript:void(0);" onclick="updateOffcanvasDropdown('offcanvasDesignation', '{{ $designation->name }}', 'desigOffcanvasBtn', '{{ $designation->name }}', this)">{{ $designation->name }}</a>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Experience</label>
                                <input type="text" class="form-control" placeholder="1 years" name="experience">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Interview Information -->
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

                <!-- Profile Information -->
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
        .wghrm-custom-dropdown-menu {
            border-radius: 12px !important;
            padding: 4px !important;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05) !important;
            border: 1px solid #e4e6ef !important;
            min-width: 220px;
        }
        .wghrm-custom-search-box {
            padding: 4px 4px 8px 4px;
        }
        .wghrm-custom-search-input {
            width: 100%;
            border: 1px solid #ebedf3;
            background-color: #f5f8fa;
            border-radius: 8px;
            padding: 6px 12px;
            font-size: 14px;
            outline: none;
        }
        .wghrm-custom-search-input:focus {
            border-color: #4e73df;
        }
        .wghrm-items-container {
            max-height: 240px;
            overflow-y: auto;
        }
        .wghrm-custom-dropdown-item {
            border-radius: 8px !important;
            padding: 8px 12px !important;
            font-size: 14px;
            color: #4b5563 !important;
            margin-bottom: 2px;
        }
        .wghrm-custom-dropdown-item.active, .wghrm-custom-dropdown-item:hover {
            background-color: #f1f3f9 !important;
            color: #4e73df !important;
        }
    </style>

    <script>
        // Toggle filter
        document.getElementById("toggleFilter").addEventListener("click", function () {
            let filter = document.getElementById("filterSection");

            filter.style.display = (filter.style.display === "none" || filter.style.display === "")
                ? "block"
                : "none";
        });


        // Apply filters
        function applyFilters() {
            let name = document.getElementById("filterName").value.toLowerCase();
            let status = document.getElementById("filterStatus").value.toLowerCase();
            let department = document.getElementById("filterDepartment").value.toLowerCase();

            let rows = document.querySelectorAll(".job-row");

            rows.forEach(row => {

                let rowName = row.querySelector(".job-name")?.innerText.toLowerCase() || "";
                let rowStatus = row.querySelector(".job-status-select")?.value.toLowerCase() || "";
                let rowDept = row.querySelector(".job-department")?.innerText.toLowerCase() || "";

                let matchName = rowName.includes(name);
                let matchStatus = status === "" || rowStatus.includes(status);
                let matchDept = department === "" || rowDept.includes(department);

                row.style.display = (matchName && matchStatus && matchDept) ? "" : "none";
            });
        }


        // Reset filters
        function resetFilters() {
            document.getElementById("filterName").value = "";
            document.getElementById("filterStatus").value = "";
            document.getElementById("filterDepartment").value = "";
            
            // Clear label text configurations manually 
            document.getElementById("statusDropdownBtn").querySelector('span').innerText = 'All Status';
            document.getElementById("deptDropdownBtn").querySelector('span').innerText = 'All Departments';
            
            applyFilters();
        }

        document.getElementById('resume').addEventListener('change', function() {
            let file = this.files[0];
            if(file){
                let maxSize = 2 * 1024 * 1024; //2MB
                if(file.size > maxSize){
                    this.classList.add('is-invalid');
                    document
                        .getElementById('resumeError')
                        .classList.remove('d-none');

                    this.value = '';
                    return;
                }
                this.classList.remove('is-invalid');
                document
                    .getElementById('resumeError')
                    .classList.add('d-none');
            }
        });

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

        function updateOffcanvasDropdown(inputId, val, btnId, text, element) {
            document.getElementById(inputId).value = val;
            document.getElementById(btnId).querySelector('span').innerText = text;
            
            let container = element.closest('.wghrm-items-container');
            container.querySelectorAll('.wghrm-custom-dropdown-item').forEach(el => el.classList.remove('active'));
            element.classList.add('active');
        }
    </script>
@endsection