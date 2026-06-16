@extends('layouts.app')

@section('title', 'Edit Project')

@section('content')
    <!-- [ page-header ] start -->
    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Projects</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                <li class="breadcrumb-item">Edit Project</li>
            </ul>
        </div>
    </div>
    <!-- [ page-header ] end -->

    <!-- [ Main Content ] start -->
    <div class="main-content">
        <div class="row">
            <div class="col-lg-12">
                <div class="card border-top-0">
                    <div class="card-body p-0">
                        <div id="project-edit-form" class="p-5">
                                <div class="mb-5 d-flex align-items-center" style="background-color:#3858f9; height: 50px">
                                    <h2 class="fs-16 fw-bold text-white ps-3 pt-2">Project details</h2>
                                </div>
                                <div class="row">
                                    <div class="col-md-4 mb-4">
                                        <label class="form-label fw-bold fs-12 text-muted text-uppercase">Project Name <span
                                                class="text-danger">*</span></label>
                                        <input type="text" class="form-control premium-input shadow-none" id="projectName"
                                            placeholder="e.g. Website development" value="{{ $project->name }}" required>
                                    </div>
                                    <div class="col-md-4 mb-4">
                                        <label class="form-label fw-bold fs-12 text-muted text-uppercase">Technology</label>
                                        <input type="text" class="form-control premium-input shadow-none"
                                            id="projectTechnology" placeholder="e.g. PHP, Laravel, React" value="{{ $project->technology }}">
                                    </div>
                                    <div class="col-md-4 mb-4">
                                        <label class="form-label fw-bold fs-12 text-muted text-uppercase">
                                            Services <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select premium-select" id="services" name="services[]" multiple data-placeholder="Select or add services...">
                                            <option value="IoT Services">IoT Services</option>
                                            <option value="AI Development">AI Development</option>
                                            <option value="AI Consulting">AI Consulting</option>
                                            <option value="AI Chatbot">AI Chatbot</option>
                                            <option value="AI Automation">AI Automation</option>

                                            <option value="PHP Development">PHP Development</option>
                                            <option value="Node Js Development">Node Js Development</option>
                                            <option value="Angular Development">Angular Development</option>
                                            <option value="Laravel Development">Laravel Development</option>
                                            <option value="Full Stack Development">Full Stack Development</option>
                                            <option value="React Js Development">React Js Development</option>

                                            <option value="Android App Development">Android App Development</option>
                                            <option value="iOS App Development">iOS App Development</option>
                                            <option value="Flutter App Development">Flutter App Development</option>
                                            <option value="Hybrid App Development">Hybrid App Development</option>

                                            <option value="Enterprise Software Development">Enterprise Software Development</option>
                                            <option value="ERP Development">ERP Development</option>
                                            <option value="CRM Development">CRM Development</option>
                                            <option value="Software Development Outsourcing">Software Development Outsourcing</option>

                                            <option value="Search Engine Optimization">Search Engine Optimization</option>
                                            <option value="Performance Marketing">Performance Marketing</option>
                                            <option value="Social Media Marketing">Social Media Marketing</option>
                                            <option value="Content Writing">Content Writing</option>
                                            <option value="Email Marketing">Email Marketing</option>
                                            <option value="Google Ads">Google Ads</option>
                                            <option value="Meta Ads">Meta Ads</option>

                                            <option value="UI/UX Design">UI/UX Design</option>
                                            <option value="Graphic Design">Graphic Design</option>
                                            <option value="Corporate Identity Design">Corporate Identity Design</option>
                                            <option value="Digital & Print Design">Digital & Print Design</option>
                                            <option value="Motion Graphics & Animation">Motion Graphics & Animation</option>

                                            <option value="Shopify Development">Shopify Development</option>
                                            <option value="Magento Development">Magento Development</option>
                                            <option value="WooCommerce Development">WooCommerce Development</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label fw-bold fs-12 text-muted text-uppercase">Project
                                        Description</label>
                                    <textarea id="summernote-main" class="form-control"
                                        style="min-height: 200px; border-radius: 8px;">{!! $project->description !!}</textarea>
                                </div>
                                <div class="row">
                                    <div class="col-md-4 mb-4">
                                        <label class="form-label fw-bold fs-12 text-muted text-uppercase">Start Date <span
                                                class="text-danger">*</span></label>
                                        <input type="date" class="form-control premium-input shadow-none"
                                            id="projectStartDate" value="{{ $project->start_date ? $project->start_date->format('Y-m-d') : '' }}" required>
                                    </div>
                                    <div class="col-md-4 mb-4">
                                        <label class="form-label fw-bold fs-12 text-muted text-uppercase">End Date</label>
                                        <input type="date" class="form-control premium-input shadow-none"
                                            id="projectEndDate" value="{{ $project->end_date ? $project->end_date->format('Y-m-d') : '' }}" onclick="this.showPicker()">
                                    </div>
                                    <div class="col-md-4 mb-4">
                                        <label class="form-label fw-bold fs-12 text-muted text-uppercase">Status</label>
                                        <select class="form-select premium-select" id="projectStatus"
                                            data-placeholder="Select Status">
                                            <option value=""></option>
                                            @php
                                                $statusMap = [
                                                    'Not Started' => 'Pending',
                                                    'In Progress' => 'In Process',
                                                    'Finished' => 'Completed',
                                                    'Declined' => 'Rework'
                                                ];
                                                $displayStatus = $statusMap[$project->status] ?? $project->status;
                                            @endphp
                                            <option value="Pending" {{ $displayStatus == 'Pending' ? 'selected' : '' }}>Pending</option>
                                            <option value="In Process" {{ $displayStatus == 'In Process' ? 'selected' : '' }}>In Process</option>
                                            <option value="Completed" {{ $displayStatus == 'Completed' ? 'selected' : '' }}>Completed</option>
                                            <option value="On Hold" {{ $displayStatus == 'On Hold' ? 'selected' : '' }}>On Hold</option>
                                            <option value="Review" {{ $displayStatus == 'Review' ? 'selected' : '' }}>Review</option>
                                            <option value="Rework" {{ $displayStatus == 'Rework' ? 'selected' : '' }}>Rework</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4 mb-4">
                                        <label class="form-label fw-bold fs-12 text-muted text-uppercase">Department <span
                                                class="text-danger">*</span></label>
                                        <select class="form-select premium-select" id="projectDepartment" multiple
                                            data-placeholder="Select Department" required>
                                            <option value=""></option>
                                            @foreach($departments as $dept)
                                                <option value="{{ $dept->name }}"
                                                    {{ (auth()->user()->role == 'team_leader' && $dept->name == auth()->user()->employee->department) || (is_array($project->department) ? in_array($dept->name, $project->department) : $project->department == $dept->name) ? 'selected' : '' }}>
                                                    {{ $dept->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @if(auth()->user()->role == 'team_leader')
                                            <input type="hidden"
                                                name="department"
                                                value="{{ auth()->user()->employee->department }}">
                                        @endif
                                    </div>
                                    <div class="col-md-4 mb-4">
                                        <label class="form-label fw-bold fs-12 text-muted text-uppercase">Project Leads
                                            <span class="text-danger">*</span></label>
                                        <select class="form-select premium-select" id="projectLeaders" multiple="multiple"
                                            data-placeholder="Select Project Leads..." required>
                                            @foreach($employees as $emp)
                                                <option value="{{ $emp->id }}" {{ is_array($project->leaders) && in_array($emp->id, $project->leaders) ? 'selected' : '' }}>
                                                    {{ $emp->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-4">
                                        <label class="form-label fw-bold fs-12 text-muted text-uppercase">Team
                                            Members</label>
                                        <select class="form-select premium-select" id="projectMembers" multiple="multiple"
                                            data-placeholder="Select Members...">
                                            @foreach($employees as $emp)
                                                <option value="{{ $emp->id }}" {{ is_array($project->members) && in_array($emp->id, $project->members) ? 'selected' : '' }}>
                                                    {{ $emp->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-8 mb-3">
                                        <label class="form-label">Documents</label>
                                        
                                        <!-- List existing documents -->
                                        @if(is_array($project->documents) && count($project->documents) > 0)
                                            <div class="existing-documents-container mb-3 p-3 border rounded bg-light">
                                                <h6 class="fw-bold fs-12 text-muted text-uppercase mb-2">Existing Documents</h6>
                                                <ul class="list-unstyled mb-0">
                                                    @foreach($project->documents as $doc)
                                                        @php
                                                            $fileName = basename($doc);
                                                        @endphp
                                                        <li class="d-flex align-items-center justify-content-between mb-2 pb-2 border-bottom last-border-0">
                                                            <a href="{{ asset('storage/' . $doc) }}" target="_blank" class="text-primary text-decoration-none">
                                                                <i class="feather-file-text me-2"></i>{{ $fileName }}
                                                            </a>
                                                            <button type="button" class="btn btn-sm btn-outline-danger py-1 px-2 remove-existing-doc" data-file-path="{{ $doc }}">
                                                                <i class="feather-trash-2"></i> Remove
                                                            </button>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endif
                                        
                                        <input type="file" id="projectDocuments" name="documents[]" class="form-control" multiple>
                                        <small class="text-muted">
                                            Select files to upload and append to the existing documents.
                                        </small>
                                    </div>
                                    <div class="col-md-4 mb-3 d-flex align-items-end">
                                        <button type="button" id="submitProject" class="btn btn-primary w-100 mb-4" style="height: 45px;">
                                            Update Project
                                        </button>
                                    </div>
                                </div>
                        </div>

                        <!-- Hidden Form for Submission -->
                        <form id="finalEditForm" action="{{ route('projects.update', $project) }}" method="POST" enctype="multipart/form-data" style="display: none;">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="name" id="hiddenName">
                            <input type="hidden" name="technology" id="hiddenTechnology">
                            <input type="hidden" name="description" id="hiddenDesc">
                            <input type="hidden" name="start_date" id="hiddenStartDate">
                            <input type="hidden" name="end_date" id="hiddenEndDate">
                            <input type="hidden" name="department" id="hiddenDepartment">
                            <input type="hidden" name="status" id="hiddenStatus">
                            <div id="hiddenMembersContainer"></div>
                            <div id="hiddenLeadersContainer"></div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .card-input-element {
            display: none;
        }

        .card-input-element:checked+.card {
            border-color: #3454d1 !important;
            background-color: rgba(52, 84, 209, 0.05) !important;
            border-width: 2px !important;
        }

        /* Select2 Premium Styling */
        .select2-container--default .select2-selection--single,
        .select2-container--default .select2-selection--multiple {
            min-height: 48px !important;
            border-radius: 12px !important;
            border: 1px solid #ebf0f5 !important;
            display: flex !important;
            align-items: center !important;
            background-color: #fcfdfe !important;
            transition: all 0.2s ease;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered,
        .select2-container--default .select2-selection--multiple .select2-selection__rendered {
            color: #1a202c !important;
            font-weight: 600 !important;
            padding-left: 15px !important;
            font-size: 13px !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 46px !important;
            right: 10px !important;
        }

        .select2-dropdown {
            border: 0 !important;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1) !important;
            border-radius: 15px !important;
            overflow: hidden !important;
            margin-top: 8px !important;
            padding: 8px 0 !important;
            z-index: 99999 !important;
        }

        .select2-search--dropdown {
            padding: 12px 15px !important;
        }

        .select2-search--dropdown .select2-search__field {
            border-radius: 10px !important;
            border: 1px solid #ebf0f5 !important;
            padding: 10px 15px !important;
            background-color: #f8fafc !important;
            font-size: 13px !important;
        }

        .select2-results__option {
            padding: 10px 15px !important;
            margin: 2px 10px !important;
            border-radius: 10px !important;
            font-weight: 600 !important;
            font-size: 13px !important;
            color: #4a5568 !important;
            transition: all 0.2s ease;
        }

        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: rgba(56, 88, 249, 0.08) !important;
            color: #3858f9 !important;
        }

        .select2-container--default .select2-results__option[aria-selected="true"] {
            background-color: rgba(56, 88, 249, 0.1) !important;
            color: #3858f9 !important;
        }

        /* Multiple selection chip styling */
        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background-color: #3454d1 !important;
            border: none !important;
            color: #fff !important;
            border-radius: 6px !important;
            padding: 4px 10px !important;
            font-size: 11px !important;
            margin-top: 7px !important;
        }

        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
            color: #fff !important;
            margin-right: 5px !important;
        }

        #services+.select2-container .select2-selection--multiple {
            display: flex !important;
            flex-wrap: wrap !important;
            align-items: center !important;
            padding: 6px 12px !important;
        }

        #services+.select2-container .select2-selection--multiple .select2-selection__rendered {
            display: flex !important;
            flex-wrap: wrap !important;
            align-items: center !important;
            gap: 4px !important;
            width: auto !important;
            margin: 0 !important;
            padding: 0 !important;
            line-height: 1.4 !important;
        }

        #services+.select2-container .select2-selection--multiple .select2-search--inline {
            flex: 1 1 auto !important;
            margin: 0 !important;
        }

        #services+.select2-container .select2-selection--multiple .select2-search__field {
            width: 100% !important;
            height: 28px !important;
            margin: 0 !important;
            color: #1a202c !important;
            font-weight: 600 !important;
            font-size: 13px !important;
            resize: none !important;
            overflow: hidden !important;
            padding: 4px 0 !important;
            line-height: 20px !important;
            text-align: left !important;
        }

        #services+.select2-container .select2-selection--multiple .select2-search__field::placeholder {
            color: #94a3b8 !important;
            opacity: 1 !important;
        }

        #services+.select2-container .select2-selection--multiple .select2-search__field::-webkit-input-placeholder {
            color: #94a3b8 !important;
            opacity: 1 !important;
        }

        #services+.select2-container .select2-selection--multiple .select2-search__field::-moz-placeholder {
            color: #94a3b8 !important;
            opacity: 1 !important;
        }

        #services+.select2-container .select2-selection--multiple .select2-search__field:-ms-input-placeholder {
            color: #94a3b8 !important;
            opacity: 1 !important;
        }
    </style>
@endsection

@push('scripts')
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function () {
            // We will use a state variable to track if the project name is currently valid
            var isProjectNameValid = true;
            var initialProjectName = "{{ $project->name }}";

            // Initialize Summernote FIRST so it doesn't get blocked
            $('#summernote-main').summernote({
                height: 250,
                placeholder: 'Enter detailed project description...',
                toolbar: [
                    ['style', ['style']],
                    ['font', ['bold', 'underline', 'clear']],
                    ['color', ['color']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['table', ['table']],
                    ['insert', ['link', 'picture', 'video']],
                    ['view', ['fullscreen', 'codeview', 'help']]
                ]
            });

            // Initialize Select2
            if ($.fn.select2) {
                $('.premium-select').not('#services').each(function () {
                    $(this).select2({
                        width: '100%',
                        placeholder: $(this).data('placeholder'),
                        allowClear: true,
                        minimumResultsForSearch: 0
                    });
                });
            }

            // Submit button click
            $('#submitProject').on('click', function (e) {
                e.preventDefault();

                $('.invalid-feedback').remove();
                $('.form-control, .form-select').removeClass('is-invalid');

                let name = $('#projectName').val();
                let dept = $('#projectDepartment').val();
                let startDate = $('#projectStartDate').val();
                let leaders = $('#projectLeaders').val();

                let hasError = false;

                // Duplicate name validation
                if (!isProjectNameValid || $('#projectName').hasClass('is-invalid')) {
                    return false;
                }

                if (!name) {
                    $('#projectName')
                        .addClass('is-invalid')
                        .after('<div class="invalid-feedback">Project name is required.</div>');
                    hasError = true;
                }

                if (!startDate) {
                    $('#projectStartDate')
                        .addClass('is-invalid')
                        .after('<div class="invalid-feedback">Start date is required.</div>');
                    hasError = true;
                }

                if (!dept) {
                    $('#projectDepartment').addClass('is-invalid');
                    $('#projectDepartment').parent()
                        .append('<div class="invalid-feedback d-block">Department is required.</div>');
                    hasError = true;
                }

                if (!leaders || leaders.length === 0) {
                    $('#projectLeaders').parent()
                        .append('<div class="invalid-feedback d-block">Please select at least one Project Lead.</div>');
                    hasError = true;
                }

                if (hasError) {
                    return false;
                }

                // Submit form
                syncAndSubmit();
            });


            function syncAndSubmit() {
                $('#hiddenName').val($('#projectName').val());
                $('#hiddenTechnology').val($('#projectTechnology').val());
                $('#hiddenDesc').val($('#summernote-main').summernote('code'));
                $('#hiddenStartDate').val($('#projectStartDate').val());
                $('#hiddenEndDate').val($('#projectEndDate').val());
                $('#hiddenDepartment').val($('#projectDepartment').val());
                $('#hiddenStatus').val($('#projectStatus').val());

                // Sync Members
                var members = $('#projectMembers').val();
                var membersHtml = '';
                if (members && members.length > 0) {
                    members.forEach(function (id) {
                        membersHtml += '<input type="hidden" name="members[]" value="' + id + '">';
                    });
                }
                $('#hiddenMembersContainer').html(membersHtml);

                // Sync Leaders
                var leaders = $('#projectLeaders').val();
                var leadersHtml = '';
                if (leaders && leaders.length > 0) {
                    leaders.forEach(function (id) {
                        leadersHtml += '<input type="hidden" name="leaders[]" value="' + id + '">';
                    });
                }
                $('#hiddenLeadersContainer').html(leadersHtml);

                // Append documents input to the final form
                $('#finalEditForm').append($('#projectDocuments'));

                $('#finalEditForm').submit();
            }

            // Handle clicking "Remove" on an existing document
            $(document).on('click', '.remove-existing-doc', function(e) {
                e.preventDefault();
                var filePath = $(this).data('file-path');
                // Append hidden input to the hidden form
                $('#finalEditForm').append('<input type="hidden" name="deleted_documents[]" value="' + filePath + '">');
                // Remove the list item
                $(this).closest('li').remove();
                // If no documents left, remove the existing documents container
                if ($('.existing-documents-container ul li').length === 0) {
                    $('.existing-documents-container').remove();
                }
            });

            $('#finalEditForm').on('submit', function (e) {
                if ($('#hiddenName').val() === "") {
                    syncAndSubmit();
                }
            });

            // Delegated real-time duplicate listener
            $(document).on('blur change input', '#projectName', function () {
                var projectName = $(this).val().trim();
                var $inputField = $(this);

                // Reset visual styling indicators
                $inputField.removeClass('is-invalid is-valid');
                $inputField.parent().find('.duplicate-feedback').remove();
                isProjectNameValid = true;

                if (projectName === '') {
                    return;
                }

                if (projectName.toLowerCase() === initialProjectName.trim().toLowerCase()) {
                    return;
                }

                $.ajax({
                    url: "{{ route('projects.check-name') }}", 
                    type: "POST",
                    data: {
                        _token: "{{ csrf_token() }}", 
                        name: projectName
                    },
                    dataType: "json",
                    success: function (response) {
                        if (response.exists) {
                            isProjectNameValid = false;
                            $inputField.removeClass('is-valid').addClass('is-invalid');
                            $inputField.after('<div class="invalid-feedback duplicate-feedback d-block fw-bold text-danger mt-1" style="font-size: 11px;">This project name already exists.</div>');
                        } else {
                            isProjectNameValid = true;
                        }
                    },
                    error: function () {
                        console.error("Could not reach validation engine backend.");
                    }
                });
            });
        });

        $(document).ready(function () {

            const allEmployees = @json($employees);
            const initialLeaderIds = @json(is_array($project->leaders) ? array_map('strval', $project->leaders) : []);
            const initialMemberIds = @json(is_array($project->members) ? array_map('strval', $project->members) : []);

            const syncDepartmentEmployees = () => {
                const selectedDepartment = $('#projectDepartment').val();
                const currentLeaderIds = ($('#projectLeaders').val() || []).map(String);
                const currentMemberIds = ($('#projectMembers').val() || []).map(String);

                const leaderIdsToKeep = currentLeaderIds.length ? currentLeaderIds : initialLeaderIds;
                const memberIdsToKeep = currentMemberIds.length ? currentMemberIds : initialMemberIds;

                $('#projectLeaders').empty();
                $('#projectMembers').empty();

                const filteredEmployees = allEmployees.filter(emp => {
                    if (Array.isArray(selectedDepartment)) {
                        return selectedDepartment.includes(emp.department);
                    }
                    return selectedDepartment && emp.department === selectedDepartment;
                });

                filteredEmployees.forEach(function (emp) {
                    const empId = String(emp.id);
                    const leaderSelected = leaderIdsToKeep.includes(empId) ? 'selected' : '';
                    const memberSelected = memberIdsToKeep.includes(empId) ? 'selected' : '';

                    $('#projectLeaders').append(`
                        <option value="${emp.id}" ${leaderSelected}>
                            ${emp.name}
                        </option>
                    `);
                    $('#projectMembers').append(`
                        <option value="${emp.id}" ${memberSelected}>
                            ${emp.name}
                        </option>
                    `);
                });

                $('#projectLeaders').trigger('change.select2');
                $('#projectMembers').trigger('change.select2');
            };

            $('#projectDepartment').on('change', function () {
                syncDepartmentEmployees();
            });

            $('#services').select2({
                width: '100%',
                tags: true,
                placeholder: $('#services').data('placeholder'),
                allowClear: true,
                tokenSeparators: [',']
            });

            syncDepartmentEmployees();

        });
    </script>
@endpush
