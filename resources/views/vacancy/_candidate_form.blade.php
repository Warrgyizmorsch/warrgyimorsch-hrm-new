{{-- Shared "Add / Edit Candidate" offcanvas, used by vacancy/index.blade.php and vacancy/job_requirement.blade.php --}}
@php
    $qualificationOptions = [
        'B.Tech', 'BCA', 'MCA', 'M.Tech', 'B.Sc IT', 'M.Sc IT', 'B.Sc Computer Science',
        'M.Sc Computer Science', 'BE Computer Engineering', 'Diploma in Computer Engineering',
        'PGDCA', 'MBA IT', 'Full Stack Development', 'Cyber Security', 'Data Science',
        'Artificial Intelligence', 'Machine Learning', 'Other',
    ];
@endphp

<div class="offcanvas offcanvas-end" tabindex="-1" id="candidateOffcanvas" style="width:600px;">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title d-flex align-items-center gap-2">
            <i class="feather-user-plus text-primary"></i>
            <span id="candidateOffcanvasTitle">Candidate Information</span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>

    <div class="offcanvas-body">
        <form id="candidateForm" action="{{ route('job.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="_method" id="candidateFormMethod" value="">
            <input type="hidden" name="job_requirement_id" id="candidateJobRequirementId" value="">

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h6 class="fw-bold mb-3 text-primary">Basic Information</h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" name="name" id="candidateName" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="candidateEmail" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Mobile</label>
                            <input type="text" class="form-control" name="phone" id="candidatePhone" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Qualification</label>
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary w-100 dropdown-toggle text-start d-flex align-items-center justify-content-between"
                                        type="button" id="qualOffcanvasBtn" data-bs-toggle="dropdown" style="height: 44px; border-radius: 12px; border: 1px solid #dcdcdc; background: #fff; color: #4b5563;">
                                    <span>Select Qualification</span>
                                </button>
                                <input type="hidden" name="qualification" id="offcanvasQualification" value="">

                                <div class="dropdown-menu wghrm-custom-dropdown-menu w-100">
                                    <div class="wghrm-custom-search-box">
                                        <input type="text" class="wghrm-custom-search-input" placeholder="Search qualification..." onkeyup="wghrmFilterItems(this)">
                                    </div>
                                    <div class="wghrm-items-container">
                                        <a class="dropdown-item wghrm-custom-dropdown-item active" href="javascript:void(0);" onclick="updateOffcanvasDropdown('offcanvasQualification', '', 'qualOffcanvasBtn', 'Select Qualification', this)">Select Qualification</a>
                                        @foreach($qualificationOptions as $qual)
                                            <a class="dropdown-item wghrm-custom-dropdown-item" href="javascript:void(0);" onclick="updateOffcanvasDropdown('offcanvasQualification', '{{ $qual }}', 'qualOffcanvasBtn', '{{ $qual }}', this)">{{ $qual }}</a>
                                        @endforeach
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
                                            <a class="dropdown-item wghrm-custom-dropdown-item" href="javascript:void(0);" onclick="updateOffcanvasDropdown('offcanvasDepartment', '{{ $department->id }}', 'deptOffcanvasBtn', '{{ addslashes($department->name) }}', this)">{{ $department->name }}</a>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Designation / Role</label>
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
                                            <a class="dropdown-item wghrm-custom-dropdown-item" href="javascript:void(0);" onclick="updateOffcanvasDropdown('offcanvasDesignation', '{{ addslashes($designation->name) }}', 'desigOffcanvasBtn', '{{ addslashes($designation->name) }}', this)">{{ $designation->name }}</a>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Experience</label>
                            <input type="text" class="form-control" placeholder="1 years" name="experience" id="candidateExperience">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h6 class="fw-bold mb-3 text-primary">Interview Information</h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Interview Date</label>
                            <input type="date" class="form-control" name="interview_date" id="candidateInterviewDate">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Interview Time</label>
                            <input type="time" class="form-control" name="interview_time" id="candidateInterviewTime">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Stage</label>
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary w-100 dropdown-toggle text-start d-flex align-items-center justify-content-between"
                                        type="button" id="statusOffcanvasBtn" data-bs-toggle="dropdown" style="height: 44px; border-radius: 12px; border: 1px solid #dcdcdc; background: #fff; color: #4b5563;">
                                    <span>Applied</span>
                                </button>
                                <input type="hidden" name="status" id="offcanvasStatus" value="applied">

                                <div class="dropdown-menu wghrm-custom-dropdown-menu w-100">
                                    <div class="wghrm-items-container">
                                        @foreach(\App\Models\JobApplication::STAGES as $stageKey => $stageLabel)
                                            <a class="dropdown-item wghrm-custom-dropdown-item {{ $stageKey === 'applied' ? 'active' : '' }}" href="javascript:void(0);" onclick="updateOffcanvasDropdown('offcanvasStatus', '{{ $stageKey }}', 'statusOffcanvasBtn', '{{ $stageLabel }}', this)">{{ $stageLabel }}</a>
                                        @endforeach
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
                                            <a class="dropdown-item wghrm-custom-dropdown-item" href="javascript:void(0);" onclick="updateOffcanvasDropdown('offcanvasInterviewer', '{{ $employee->id }}', 'interviewerOffcanvasBtn', '{{ addslashes($employee->name) }}', this)">{{ $employee->name }}</a>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Interview Description / Link</label>
                            <textarea class="form-control" name="interview_details" id="candidateInterviewDetails" rows="3"
                                placeholder="Enter interview description or meeting link"></textarea>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Remarks</label>
                            <textarea class="form-control" name="remarks" id="candidateRemarks" rows="2"
                                placeholder="Internal notes about this candidate"></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h6 class="fw-bold mb-3 text-primary">Profile Upload</h6>
                    <div class="mb-3">
                        <label class="form-label">Upload Resume/Profile</label>
                        <input type="file" class="form-control" name="resume" id="resume" accept=".pdf,.doc,.docx">
                        <div id="resumeError" class="text-danger small mt-1 d-none">
                            Resume size should not exceed 2 MB.
                        </div>
                        <small class="text-muted" id="candidateExistingResume"></small>
                        <small class="text-muted d-block">Accepted formats: PDF, DOC, DOCX | Maximum file size: 2 MB</small>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100">
                <i class="feather-save me-2"></i>
                <span id="candidateSubmitLabel">Save Candidate</span>
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
    .stage-badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .3px;
    }
    .stage-badge--applied { background: #eef2ff; color: #4338ca; }
    .stage-badge--shortlisted { background: #ecfeff; color: #0e7490; }
    .stage-badge--interview_scheduled { background: #fff7ed; color: #c2410c; }
    .stage-badge--interviewed { background: #fefce8; color: #a16207; }
    .stage-badge--offered { background: #f0fdf4; color: #15803d; }
    .stage-badge--hired { background: #dcfce7; color: #166534; }
    .stage-badge--rejected { background: #fef2f2; color: #b91c1c; }
</style>

<script>
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

    const candidateResumeInput = document.getElementById('resume');
    if (candidateResumeInput) {
        candidateResumeInput.addEventListener('change', function () {
            let file = this.files[0];
            if (file) {
                let maxSize = 2 * 1024 * 1024;
                if (file.size > maxSize) {
                    this.classList.add('is-invalid');
                    document.getElementById('resumeError').classList.remove('d-none');
                    this.value = '';
                    return;
                }
                this.classList.remove('is-invalid');
                document.getElementById('resumeError').classList.add('d-none');
            }
        });
    }

    function resetCandidateForm() {
        const form = document.getElementById('candidateForm');
        form.reset();
        document.getElementById('candidateFormMethod').value = '';
        document.getElementById('candidateJobRequirementId').value = '';
        form.action = '{{ route('job.store') }}';
        document.getElementById('candidateOffcanvasTitle').innerText = 'Candidate Information';
        document.getElementById('candidateSubmitLabel').innerText = 'Save Candidate';
        document.getElementById('candidateExistingResume').innerText = '';

        updateOffcanvasDropdown('offcanvasQualification', '', 'qualOffcanvasBtn', 'Select Qualification', document.querySelector('#qualOffcanvasBtn').closest('.dropdown').querySelector('.wghrm-custom-dropdown-item'));
        updateOffcanvasDropdown('offcanvasDepartment', '', 'deptOffcanvasBtn', 'Select Department', document.querySelector('#deptOffcanvasBtn').closest('.dropdown').querySelector('.wghrm-custom-dropdown-item'));
        updateOffcanvasDropdown('offcanvasDesignation', '', 'desigOffcanvasBtn', 'Select Designation', document.querySelector('#desigOffcanvasBtn').closest('.dropdown').querySelector('.wghrm-custom-dropdown-item'));
        updateOffcanvasDropdown('offcanvasInterviewer', '', 'interviewerOffcanvasBtn', 'Select Interviewer', document.querySelector('#interviewerOffcanvasBtn').closest('.dropdown').querySelector('.wghrm-custom-dropdown-item'));
        updateOffcanvasDropdown('offcanvasStatus', 'applied', 'statusOffcanvasBtn', 'Applied', document.querySelector('#statusOffcanvasBtn').closest('.dropdown').querySelector('.wghrm-custom-dropdown-item'));
    }

    function openAddOffcanvas(jobRequirementId, designation) {
        resetCandidateForm();

        if (jobRequirementId) {
            document.getElementById('candidateJobRequirementId').value = jobRequirementId;
        }
        if (designation) {
            updateOffcanvasDropdown('offcanvasDesignation', designation, 'desigOffcanvasBtn', designation, document.querySelector('#desigOffcanvasBtn').closest('.dropdown').querySelector('.wghrm-custom-dropdown-item'));
        }

        new bootstrap.Offcanvas(document.getElementById('candidateOffcanvas')).show();
    }

    function setDropdownFromValue(hiddenId, btnId, value, label) {
        const hidden = document.getElementById(hiddenId);
        const btn = document.getElementById(btnId);
        hidden.value = value ?? '';
        btn.querySelector('span').innerText = label || btn.querySelector('span').innerText;

        const container = hidden.closest('.dropdown').querySelector('.wghrm-items-container');
        if (container) {
            container.querySelectorAll('.wghrm-custom-dropdown-item').forEach(el => {
                el.classList.toggle('active', el.getAttribute('onclick')?.includes(`'${value}'`));
            });
        }
    }

    function openEditOffcanvas(applicationId) {
        fetch(`/job-vacancy/${applicationId}/edit`, { headers: { 'Accept': 'application/json' } })
            .then(res => res.json())
            .then(app => {
                resetCandidateForm();

                document.getElementById('candidateFormMethod').value = 'PUT';
                document.getElementById('candidateForm').action = `/job-vacancy/${applicationId}`;
                document.getElementById('candidateOffcanvasTitle').innerText = 'Edit Candidate';
                document.getElementById('candidateSubmitLabel').innerText = 'Update Candidate';
                document.getElementById('candidateJobRequirementId').value = app.job_requirement_id || '';

                document.getElementById('candidateName').value = app.name || '';
                document.getElementById('candidateEmail').value = app.email || '';
                document.getElementById('candidatePhone').value = app.phone || '';
                document.getElementById('candidateExperience').value = app.experience || '';
                document.getElementById('candidateInterviewDate').value = app.interview_date || '';
                document.getElementById('candidateInterviewTime').value = app.interview_time ? app.interview_time.substring(0, 5) : '';
                document.getElementById('candidateInterviewDetails').value = app.interview_details || '';
                document.getElementById('candidateRemarks').value = app.remarks || '';

                setDropdownFromValue('offcanvasQualification', 'qualOffcanvasBtn', app.qualification, app.qualification || 'Select Qualification');
                setDropdownFromValue('offcanvasDepartment', 'deptOffcanvasBtn', app.department_id, app.department?.name || 'Select Department');
                setDropdownFromValue('offcanvasDesignation', 'desigOffcanvasBtn', app.designation, app.designation || 'Select Designation');
                setDropdownFromValue('offcanvasInterviewer', 'interviewerOffcanvasBtn', app.interviewer_id, app.interviewer?.name || 'Select Interviewer');
                setDropdownFromValue('offcanvasStatus', 'statusOffcanvasBtn', app.status, app.status ? app.status.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) : 'Applied');

                if (app.resume) {
                    document.getElementById('candidateExistingResume').innerHTML = `Current file: <a href="/storage/${app.resume}" target="_blank">view resume</a> (upload a new one to replace it)`;
                }

                new bootstrap.Offcanvas(document.getElementById('candidateOffcanvas')).show();
            });
    }
</script>
