@extends('layouts.app')

@section('content')
    <div class="page-header d-flex justify-content-between align-items-center">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Employee Review</h5>
            </div>
            <ul class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                <li class="breadcrumb-item">Technical Review</li>
            </ul>
        </div>
        <div class="page-header-right d-flex justify-content-between">
            @if ($isTeamLeader || $isAdmin)
                <button type="button" class="btn btn-primary me-5" data-bs-toggle="modal" data-bs-target="#evaluationModal">
                    <i class="feather-settings me-1"></i>
                    Manage Evaluation
                </button>
            @endif
            <button id="openCreateReviewBtn" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createReviewModal" data-mode="create">
                <i class="fa fa-plus me-1"></i> Create Review
            </button>
        </div>
    </div>

    <div class="modal fade" id="evaluationModal">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">

                <div class="modal-header">
                    <h5>Manage Review Evaluation</h5>
                </div>

                <form action="{{ url('/technical-review-evaluation/store') }}"
                    method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Department</label>
                            @if($isTeamLeader)
                                <div class="form-control-plaintext fw-bold">{{ $employeeRecord->department ?? 'N/A' }}</div>
                                <input type="hidden" id="department" name="department" value="{{ $employeeRecord->department ?? '' }}">
                            @else
                                <select id="department" name="department" class="form-select evaluation-department-select" required>
                                    <option value="">Select Department</option>
                                    @foreach($departments as $department)
                                        <option value="{{ $department->name }}">
                                            {{ $department->name }}
                                        </option>
                                    @endforeach
                                </select>
                            @endif
                        </div>

                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Criteria Name</th>
                                    <th>Max Point</th>
                                    <th width="80">Action</th>
                                </tr>
                            </thead>

                            <tbody id="criteriaContainer">
                                <tr>
                                    <td><input type="text" name="criterianame[]" class="form-control" required></td>
                                    <td><input type="number" name="maxpoint[]" class="form-control max-point" step="0.01" required></td>
                                    <td width="120" class="d-flex justify-content-between">
                                        <button type="button" class="btn btn-primary add-row"><i class="feather-plus"></i></button>
                                        <button type="button" class="btn btn-danger remove-row"><i class="feather-minus"></i></button>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th class="text-end">Total</th>
                                    <th>
                                        <input type="number" id="totalPoints" class="form-control" readonly>
                                    </th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="modal-footer">
                        <button type="submit" id="saveEvaluationBtn" class="btn btn-primary">
                            Save
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>

    <div class="main-content">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <div class="modal fade" id="createReviewModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header bg-primary">
                        <h5 class="modal-title text-white">Technical Review Evaluation</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="technicalReviewForm" action="{{ url('/technical-review/store') }}" method="POST">
                        @csrf
                        <div class="modal-body">
                            @php
                                $months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
                            @endphp
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="fw-bold mb-1">Select Month</label>
                                    <div class="review-select" data-select>
                                        <input type="hidden" name="month" value="{{ old('month') }}" required>
                                        <button type="button" class="review-select-trigger" data-select-trigger aria-expanded="false">
                                            <span data-select-label>{{ old('month') ?: 'Select Month' }}</span>
                                            <i class="fa fa-chevron-down review-select-icon"></i>
                                        </button>
                                        <div class="review-select-menu" data-select-menu hidden>
                                            <div class="review-select-search-wrap">
                                                <input type="text" class="review-select-search" data-select-search placeholder="Search month...">
                                            </div>
                                            <div class="review-select-options" data-select-options>
                                                @foreach($months as $month)
                                                    <button
                                                        type="button"
                                                        class="review-select-option {{ old('month') === $month ? 'is-selected' : '' }}"
                                                        data-select-option
                                                        data-value="{{ $month }}"
                                                    >
                                                        {{ $month }}
                                                    </button>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                @if($isAdmin || $isTeamLeader)
                                    <div class="col-md-6">
                                        <label class="fw-bold mb-1">Select Employee</label>
                                        <div class="review-select" data-select>
                                            <input type="hidden" name="user_id" value="{{ old('user_id') }}" required>
                                            <button type="button" class="review-select-trigger" data-select-trigger aria-expanded="false">
                                                <span data-select-label>
                                                    {{ optional(($employees ?? collect())->firstWhere('id', old('user_id')))->name ?: 'Choose Employee...' }}
                                                </span>
                                                <i class="fa fa-chevron-down review-select-icon"></i>
                                            </button>
                                            <div class="review-select-menu" data-select-menu hidden>
                                                <div class="review-select-search-wrap">
                                                    <input type="text" class="review-select-search" data-select-search placeholder="Search employee...">
                                                </div>
                                                <div class="review-select-options" data-select-options>
                                                    @foreach($employees ?? [] as $employee)
                                                        <button
                                                            type="button"
                                                            class="review-select-option {{ (string) old('user_id') === (string) $employee->id ? 'is-selected' : '' }}"
                                                            data-select-option
                                                            data-value="{{ $employee->id }}"
                                                        >
                                                            {{ $employee->name }}
                                                        </button>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <table class="table table-bordered mt-4">
                                <thead class="table-light">
                                    <tr>
                                        <th>Criteria Name</th>
                                        <th>Max Point</th>
                                        <th>Self Review</th>
                                        @if($isTeamLeader)
                                            <th>Team Leader Review</th>
                                        @endif

                                        @if($isAdmin)
                                            <th>Team Leader Review</th>
                                            <th>Admin Review</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody id="technicalReviewCriteriaBody">
                                    @php
                                        $department = $employeeRecord->department ?? '';
                                    @endphp

                                    @foreach($evaluations as $evaluation)
                                        <tr>
                                            <td>
                                                {{ $evaluation->criteria_name }}
                                                <input type="hidden" name="criteria_name[]" value="{{ $evaluation->criteria_name }}">
                                            </td>
                                            <td>
                                                {{ $evaluation->max_point }}
                                                <input type="hidden" name="criteria_point[]" value="{{ $evaluation->max_point }}">
                                            </td>
                                            <td>
                                                <input type="number" name="self_review[]" class="form-control self-review" step="0.01" min="0" max="{{ $evaluation->max_point }}">
                                            </td>
                                            @if($isTeamLeader || $isAdmin)
                                                <td>
                                                    <input type="number" name="author_review[]" class="form-control author-review" step="0.01" min="0" max="{{ $evaluation->max_point }}">
                                                </td>
                                            @endif
                                            @if($isAdmin)
                                                <td>
                                                    <input type="number" name="admin_review[]" class="form-control admin-review" step="0.01" min="0" max="{{ $evaluation->max_point }}">
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="fw-bold">
                                        <td>Total</td>
                                        <td id="criteriaPointTotal">
                                            {{ $evaluations->sum('max_point') }}
                                        </td>
                                        <td id="selfTotal">0</td>
                                        @if($isTeamLeader || $isAdmin)
                                            <td id="teamTotal">0</td>
                                        @endif
                                        @if($isAdmin)
                                            <td id="adminTotal">0</td>
                                        @endif
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <div class="modal-footer bg-light">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary px-4">Save Entry</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

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

        <!-- <h4 class="">Reviews</h4> -->
        <table class="table table-striped mt-2">
            <thead class="bg-primary text-white" style="height: 50px;">
                <tr>
                    <th>Sr</th>
                    <th>Month</th>
                    <th>Employee</th>
                    <th>Self Review</th>
                    <th>Team Leader Review</th>
                    <th>Admin Review</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reviews as $review)
                    <tr style="height: 50px;">
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $review->month }}</td>
                        <td>{{ $review->employee->name ?? 'N/A' }}</td>
                        <td>{{ $review->self_total }}</td>
                        <td>{{ $review->author_total }}</td>
                        <td>{{ $review->admin_total }}</td>
                        <td class="d-flex" style="height: 50px;">
                            <button class="btn btn-primary" data-bs-toggle="modal" style="height: 20px; width:20px" data-bs-target="#reviewModal{{ $review->id }}">
                                <i class="fa fa-eye"></i>
                            </button>
                            @if($isAdmin || $isTeamLeader)
                                <button
                                    class="btn btn-success edit-technical-review-btn ms-1"
                                    style="height: 20px; width:20px"
                                    data-mode="edit"
                                    data-review-id="{{ $review->id }}"
                                    data-month="{{ $review->month }}"
                                    data-period="{{ $review->period }}"
                                    data-user-id="{{ $review->employee->id ?? '' }}"
                                    data-bs-toggle="modal"
                                    data-bs-target="#createReviewModal"
                                >
                                    <i class="fa fa-edit"></i>
                                </button>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- PAGINATION -->
        @if($reviews->hasPages())
            <div class="card-footer bg-white border-0 py-3 attendance-pagination">
                {{ $reviews->appends(request()->query())->links('pagination::bootstrap-5') }}
            </div>
        @endif

        <div class="mt-2">
            {{ $reviews->links() }}
        </div>
    </div>

    @foreach($reviews as $review)
        <div class="modal fade" id="reviewModal{{ $review->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header bg-primary">
                        <h5 class="modal-title text-white">Review Details</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Criteria</th>
                                    <th>Max Point</th>
                                    <th>Self Score</th>
                                    <th>Team Leader Score</th>
                                    <th>Admin Score</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($review->details as $detail)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $detail->criteria_name }}</td>
                                        <td>{{ $detail->criteria_point }}</td>
                                        <td>{{ $detail->self_review }}</td>
                                        <td>{{ $detail->author_review }}</td>
                                        <td>{{ $detail->admin_review }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center">No metadata logged for this entry.</td>
                                    </tr>
                                @endforelse
                                @if($review->details->isNotEmpty())
                                    @php
                                        // 1. Calculate sums safely forcing numeric values
                                        $totalMax       = $review->details->sum(fn($d) => (float) $d->criteria_point);
                                        $totalSelf      = $review->details->sum(fn($d) => (float) $d->self_review);
                                        $totalAuthor    = $review->details->sum(fn($d) => (float) $d->author_review);
                                        $totalAdmin     = $review->details->sum(fn($d) => (float) $d->admin_review);

                                        // 2. Turn sums into accurate percentages based on total possible points
                                        $selfPercent   = $totalMax > 0 ? round(($totalSelf / $totalMax) * 100, 1) : 0;
                                        $authorPercent = $totalMax > 0 ? round(($totalAuthor / $totalMax) * 100, 1) : 0;
                                        $adminPercent  = $totalMax > 0 ? round(($totalAdmin / $totalMax) * 100, 1) : 0;
                                    @endphp
                                    <tr class="table-dark fw-bold">
                                        <td colspan="2" class="text-center">Total Percentage:</td>
                                        <td>100%</td>
                                        <td>{{ $selfPercent }}%</td>
                                        <td>{{ $authorPercent }}%</td>
                                        <td>{{ $adminPercent }}%</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

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

        .review-select {
            position: relative;
        }

        .review-select-trigger {
            width: 100%;
            /* min-height: 54px; */
            border: 1.5px solid #4e6bff;
            border-radius: 16px;
            background: #ffffff;
            color: #324b72;
            /* font-size: 1.1rem; */
            /* font-weight: 600; */
            padding: 10px 15px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 10px 24px rgba(78, 107, 255, 0.08);
            transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
        }

        .review-select-trigger:focus,
        .review-select-trigger:hover {
            border-color: #3153ff;
            box-shadow: 0 14px 28px rgba(49, 83, 255, 0.14);
        }

        .review-select.is-open .review-select-trigger {
            transform: translateY(-1px);
            box-shadow: 0 18px 32px rgba(49, 83, 255, 0.18);
        }

        .review-select-icon {
            color: #5f6d86;
            font-size: 0.95rem;
            transition: transform 0.2s ease;
        }

        .review-select.is-open .review-select-icon {
            transform: rotate(180deg);
        }

        .review-select-menu {
            position: absolute;
            top: calc(100% + 14px);
            left: 0;
            width: 100%;
            z-index: 1056;
            background: #ffffff;
            border: 1px solid #e8ecf5;
            border-radius: 18px;
            box-shadow: 0 20px 45px rgba(18, 38, 63, 0.16);
            padding: 10px;
        }

        .review-select-search-wrap {
            padding-bottom: 10px;
        }

        .review-select-search {
            width: 100%;
            border: 1px solid #dbe3f0;
            border-radius: 12px;
            background: #fbfcff;
            color: #425674;
            /* font-size: 1rem; */
            padding: 10px 15px;
            outline: none;
        }

        .review-select-search:focus {
            border-color: #bfd0ff;
            box-shadow: 0 0 0 3px rgba(78, 107, 255, 0.08);
        }

        .review-select-options {
            max-height: 280px;
            overflow-y: auto;
            padding-right: 2px;
        }

        .review-select-options::-webkit-scrollbar {
            width: 8px;
        }

        .review-select-options::-webkit-scrollbar-thumb {
            background: #cbd5e7;
            border-radius: 999px;
        }

        .review-select-option {
            width: 100%;
            border: 0;
            border-radius: 14px;
            background: transparent;
            color: #324b72;
            /* font-size: 1rem; */
            text-align: left;
            padding: 14px 16px;
            margin-bottom: 6px;
            transition: background 0.2s ease, color 0.2s ease;
        }

        .review-select-option:hover,
        .review-select-option.is-selected {
            background: #eef2f8;
            color: #3153ff;
        }

        .review-select-option.is-hidden {
            display: none;
        }

        /* Main Select Box */
        .select2-container--default .select2-selection--single {
            height: 56px !important;
            border: 1.5px solid #4f6fff !important;
            border-radius: 14px !important;
            display: flex !important;
            align-items: center !important;
            padding: 0 15px !important;
            font-size: 18px;
            font-weight: 600;
            background: #fff;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #334155 !important;
            line-height: normal !important;
            padding-left: 0 !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 54px !important;
            right: 12px !important;
        }

        /* Dropdown */
        .select2-dropdown {
            border: 1px solid #e5e7eb !important;
            border-radius: 16px !important;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,.08);
        }

        /* Search Input */
        .select2-search--dropdown {
            padding: 12px;
        }

        .select2-search--dropdown .select2-search__field {
            height: 48px;
            border: 1px solid #d1d5db !important;
            border-radius: 10px !important;
            padding: 0 15px !important;
            font-size: 16px;
        }

        /* Options */
        .select2-results__option {
            padding: 14px 18px !important;
            font-size: 17px;
            color: #334155;
            border-radius: 10px;
            margin: 4px 8px;
        }

        /* Hover */
        .select2-results__option--highlighted.select2-results__option--selectable {
            background: #eef2ff !important;
            color: #4f6fff !important;
        }

        /* Selected Option */
        .select2-results__option--selected {
            background: #eef2ff !important;
            color: #4f6fff !important;
        }

        @media (max-width: 767.98px) {
            .review-select-trigger {
                min-height: 50px;
                font-size: 1rem;
                padding: 12px 14px;
            }

            .review-select-menu {
                padding: 12px;
            }
        }

        #evaluationModal .evaluation-department-select,
        #evaluationModal .select2-container--default .select2-selection--single {
            min-height: 46px;
            border: 1px solid #d9e2ef;
            border-radius: 12px;
            background-color: #fff;
            box-shadow: 0 6px 18px rgba(15, 23, 42, 0.06);
            font-size: 14px;
        }

        #evaluationModal .evaluation-department-select {
            padding: 10px 14px;
            color: #334155;
        }

        #evaluationModal .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 44px;
            padding-left: 14px;
            color: #334155;
        }

        #evaluationModal .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 44px;
            right: 10px;
        }

        .evaluation-department-dropdown.select2-dropdown {
            border: 1px solid #d9e2ef;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 14px 30px rgba(15, 23, 42, 0.14);
        }

        .evaluation-department-dropdown .select2-search--dropdown {
            padding: 10px;
        }

        .evaluation-department-dropdown .select2-search__field {
            border: 1px solid #d9e2ef !important;
            border-radius: 10px;
            padding: 8px 10px;
            outline: none;
        }

        .evaluation-department-dropdown .select2-results__option {
            padding: 10px 14px;
            font-size: 14px;
        }

        .evaluation-department-dropdown .select2-results__option--highlighted {
            background-color: #3858f9 !important;
            color: #fff !important;
        }

        .evaluation-department-dropdown .select2-results__option--selected {
            background-color: #3858f9 !important;
            color: #fff !important;
        }
    </style>

    <link href="{{ asset('assets/vendors/css/select2.min.css') }}" rel="stylesheet" />

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            function setupReviewSelect(selectEl) {
                const trigger = selectEl.querySelector('[data-select-trigger]');
                const menu = selectEl.querySelector('[data-select-menu]');
                const label = selectEl.querySelector('[data-select-label]');
                const hiddenInput = selectEl.querySelector('input[type="hidden"]');
                const searchInput = selectEl.querySelector('[data-select-search]');
                const options = selectEl.querySelectorAll('[data-select-option]');

                if (!trigger || !menu || !label || !hiddenInput) return;

                trigger.addEventListener('click', function () {
                    const isOpen = !menu.hasAttribute('hidden');
                    document.querySelectorAll('.review-select [data-select-menu]').forEach(m => m.setAttribute('hidden', ''));
                    menu.toggleAttribute('hidden', isOpen);
                });

                options.forEach(option => {
                    option.addEventListener('click', function () {
                        const value = this.dataset.value || '';
                        hiddenInput.value = value;
                        label.textContent = this.textContent.trim();

                        options.forEach(opt => opt.classList.remove('is-selected'));
                        this.classList.add('is-selected');
                        menu.setAttribute('hidden', '');
                    });
                });

                if (searchInput) {
                    searchInput.addEventListener('input', function () {
                        const q = this.value.toLowerCase();
                        options.forEach(opt => {
                            opt.style.display = opt.textContent.toLowerCase().includes(q) ? '' : 'none';
                        });
                    });
                }
            }

            document.querySelectorAll('.review-select').forEach(setupReviewSelect);

            document.addEventListener('click', function (e) {
                if (!e.target.closest('.review-select')) {
                    document.querySelectorAll('.review-select [data-select-menu]').forEach(menu => menu.setAttribute('hidden', ''));
                }
            });

            window.updateTechnicalReviewTotals = function () {
                let selfTotal = 0;
                let teamTotal = 0;
                let adminTotal = 0;

                let hasSelf = false;
                let hasTeam = false;
                let hasAdmin = false;

                document.querySelectorAll('input[name="self_review[]"]').forEach(input => {
                    const val = parseFloat(input.value);
                    if (!isNaN(val)) {
                        selfTotal += val;
                        hasSelf = true;
                    }
                });

                document.querySelectorAll('input[name="author_review[]"]').forEach(input => {
                    const val = parseFloat(input.value);
                    if (!isNaN(val)) {
                        teamTotal += val;
                        hasTeam = true;
                    }
                });

                document.querySelectorAll('input[name="admin_review[]"]').forEach(input => {
                    const val = parseFloat(input.value);
                    if (!isNaN(val)) {
                        adminTotal += val;
                        hasAdmin = true;
                    }
                });

                const selfTotalEl = document.getElementById('selfTotal');
                const teamTotalEl = document.getElementById('teamTotal');
                const adminTotalEl = document.getElementById('adminTotal');

                if (selfTotalEl) {
                    selfTotalEl.textContent = hasSelf ? selfTotal.toFixed(2) : '';
                }

                if (teamTotalEl) {
                    teamTotalEl.textContent = hasTeam ? teamTotal.toFixed(2) : '';
                }

                if (adminTotalEl) {
                    adminTotalEl.textContent = hasAdmin ? adminTotal.toFixed(2) : '';
                }
            };

            document.addEventListener('input', function (e) {
                if (
                    e.target.matches('input[name="self_review[]"]') ||
                    e.target.matches('input[name="author_review[]"]') ||
                    e.target.matches('input[name="admin_review[]"]')
                ) {
                    window.updateTechnicalReviewTotals();
                }
            });

            window.updateTechnicalReviewTotals();
        });

        document.addEventListener('DOMContentLoaded', function () {
            const reviewForm = document.getElementById('technicalReviewForm');
            const createReviewBtn = document.getElementById('openCreateReviewBtn');
            const criteriaBody = document.getElementById('technicalReviewCriteriaBody');
            const defaultCriteriaRows = criteriaBody ? criteriaBody.innerHTML : '';
            const submitBtn = reviewForm.querySelector('button[type="submit"]');
            const storeAction = `{{ url('/technical-review/store') }}`;
            const updateActionBase = `{{ url('/technical-review') }}`;
            const canReviewAsTeamLeader = @json($isTeamLeader || $isAdmin);
            const canReviewAsAdmin = @json($isAdmin);

            function setSelectValue(name, value) {
                const input = reviewForm.querySelector(`input[name="${name}"]`);
                if (!input) return;

                input.value = value;

                const select = input.closest('.review-select');
                if (!select) return;

                const options = select.querySelectorAll('[data-select-option]');
                const label = select.querySelector('[data-select-label]');

                let foundText = '';
                options.forEach(opt => {
                    if (opt.dataset.value === String(value)) {
                        opt.classList.add('is-selected');
                        foundText = opt.textContent.trim();
                    } else {
                        opt.classList.remove('is-selected');
                    }
                });

                if (label) {
                    label.textContent = foundText || value || 'Select';
                }
            }

            function displayReviewValue(value) {
                const numberValue = parseFloat(value);

                if (value === null || value === undefined || value === '' || numberValue === 0) {
                    return '';
                }

                return value;
            }

            function escapeHtml(value) {
                return String(value ?? '').replace(/[&<>"']/g, function (char) {
                    return {
                        '&': '&amp;',
                        '<': '&lt;',
                        '>': '&gt;',
                        '"': '&quot;',
                        "'": '&#039;',
                    }[char];
                });
            }

            function updateCriteriaPointTotal() {
                const criteriaPointTotal = document.getElementById('criteriaPointTotal');
                let total = 0;

                document.querySelectorAll('input[name="criteria_point[]"]').forEach(input => {
                    total += parseFloat(input.value) || 0;
                });

                if (criteriaPointTotal) {
                    criteriaPointTotal.textContent = total.toFixed(2);
                }
            }

            function renderEditRows(details) {
                if (!criteriaBody) {
                    return;
                }

                criteriaBody.innerHTML = '';

                details.forEach(detail => {
                    const criteriaName = escapeHtml(detail.criteria_name);
                    const criteriaPoint = escapeHtml(detail.criteria_point);
                    const selfReview = escapeHtml(displayReviewValue(detail.self_review));
                    const authorReview = escapeHtml(displayReviewValue(detail.author_review));
                    const adminReview = escapeHtml(displayReviewValue(detail.admin_review));
                    let reviewCells = `
                        <td>
                            <input type="number" name="self_review[]" class="form-control" step="0.01" min="0" value="${selfReview}" max="${criteriaPoint}">
                        </td>`;

                    if (canReviewAsTeamLeader) {
                        reviewCells += `
                            <td>
                                <input type="number" name="author_review[]" class="form-control" step="0.01" min="0" value="${authorReview}" max="${criteriaPoint}">
                            </td>`;
                    }

                    if (canReviewAsAdmin) {
                        reviewCells += `
                            <td>
                                <input type="number" name="admin_review[]" class="form-control" step="0.01" min="0" value="${adminReview}" max="${criteriaPoint}">
                            </td>`;
                    }

                    criteriaBody.insertAdjacentHTML('beforeend', `
                        <tr>
                            <td>
                                ${criteriaName}
                                <input type="hidden" name="detail_id[]" value="${escapeHtml(detail.id)}">
                                <input type="hidden" name="criteria_name[]" value="${criteriaName}">
                            </td>
                            <td>
                                ${criteriaPoint}
                                <input type="hidden" name="criteria_point[]" value="${criteriaPoint}">
                            </td>
                            ${reviewCells}
                        </tr>
                    `);
                });

                updateCriteriaPointTotal();
                window.updateTechnicalReviewTotals();
            }

            function resetReviewFormForCreate() {
                reviewForm.action = storeAction;

                if (submitBtn) {
                    submitBtn.textContent = 'Save Entry';
                }

                if (criteriaBody) {
                    criteriaBody.innerHTML = defaultCriteriaRows;
                }

                reviewForm.querySelectorAll('input[name="self_review[]"], input[name="author_review[]"], input[name="admin_review[]"]').forEach(input => {
                    input.value = '';
                });

                updateCriteriaPointTotal();
                window.updateTechnicalReviewTotals();
            }

            if (createReviewBtn) {
                createReviewBtn.addEventListener('click', resetReviewFormForCreate);
            }

            document.querySelectorAll('.edit-technical-review-btn').forEach(btn => {
                btn.addEventListener('click', async function () {
                    const reviewId = this.dataset.reviewId;

                    reviewForm.action = `${updateActionBase}/${reviewId}/update`;

                    if (submitBtn) {
                        submitBtn.textContent = 'Update Entry';
                    }

                    setSelectValue('month', this.dataset.month);
                    setSelectValue('period', this.dataset.period);
                    setSelectValue('user_id', this.dataset.userId);

                    try {
                        const res = await fetch(`{{ url('/technical-review-details') }}/${reviewId}`);
                        const details = await res.json();

                        renderEditRows(details);
                    } catch (error) {
                        console.error('Failed to load review details:', error);
                    }
                });
            });
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const departmentSelect = document.getElementById('department');
            const criteriaContainer = document.getElementById('criteriaContainer');
            const totalPoints = document.getElementById('totalPoints');
            const saveBtn = document.getElementById('saveEvaluationBtn');

            function rowHtml(name = '', point = '') {
                return `
                    <tr>
                        <td>
                            <input type="text" name="criterianame[]" class="form-control" value="${name}" required>
                        </td>
                        <td>
                            <input type="number" name="maxpoint[]" class="form-control max-point" step="0.01" value="${point}" required>
                        </td>
                        <td width="120" class="d-flex justify-content-between">
                            <button type="button" class="btn btn-primary add-row">
                                <i class="feather-plus"></i>
                            </button>
                            <button type="button" class="btn btn-danger remove-row">
                                <i class="feather-minus"></i>
                            </button>
                        </td>
                    </tr>`;
            }

            function calculateTotal() {
                let total = 0;
                document.querySelectorAll('#criteriaContainer .max-point').forEach(input => {
                    total += parseFloat(input.value) || 0;
                });

                if (totalPoints) {
                    totalPoints.value = total.toFixed(2);
                }

                if (saveBtn) {
                    saveBtn.disabled = false;
                }
            }

            function renderRows(rows) {
                criteriaContainer.innerHTML = '';

                if (rows && rows.length > 0) {
                    rows.forEach(row => {
                        criteriaContainer.insertAdjacentHTML('beforeend', `
                            <tr>
                                <td>
                                    <input type="text" name="criterianame[]" class="form-control" value="${row.criteria_name ?? ''}" required>
                                </td>
                                <td>
                                    <input type="number" name="maxpoint[]" class="form-control max-point" step="0.01" value="${row.max_point ?? ''}" required>
                                </td>
                                <td width="120" class="d-flex justify-content-between">
                                    <button type="button" class="btn btn-primary add-row"><i class="feather-plus"></i></button>
                                    <button type="button" class="btn btn-danger remove-row"><i class="feather-minus"></i></button>
                                </td>
                            </tr>
                        `);
                    });
                } else {
                    criteriaContainer.insertAdjacentHTML('beforeend', rowHtml());
                }

                calculateTotal();
            }

            function loadDepartmentRows(deptName) {
                if (!deptName) {
                    renderRows([]);
                    return;
                }

                fetch(`{{ url('technical-review-evaluation/fetch') }}?department=${encodeURIComponent(deptName)}`)
                    .then(response => response.json())
                    .then(data => {
                        renderRows(data);
                    })
                    .catch(error => {
                        console.error('Failed to load department rows:', error);
                        renderRows([]);
                    });
            }

            document.addEventListener('click', function (e) {
                if (e.target.closest('.add-row')) {
                    criteriaContainer.insertAdjacentHTML('beforeend', rowHtml());
                    calculateTotal();
                }

                if (e.target.closest('.remove-row')) {
                    const rows = criteriaContainer.querySelectorAll('tr');

                    if (rows.length > 1) {
                        e.target.closest('tr').remove();
                    } else {
                        e.target.closest('tr').querySelectorAll('input').forEach(input => input.value = '');
                    }

                    calculateTotal();
                }
            });

            document.addEventListener('input', function (e) {
                if (e.target.classList.contains('max-point')) {
                    calculateTotal();
                }
            });

            if (departmentSelect) {
                const handleChange = function () {
                    loadDepartmentRows(departmentSelect.value);
                };

                departmentSelect.addEventListener('change', handleChange);

                if (window.jQuery) {
                    jQuery(departmentSelect).on('change', handleChange);
                }

                if (departmentSelect.value) {
                    loadDepartmentRows(departmentSelect.value);
                }
            }
        });

        document.addEventListener('DOMContentLoaded', function () {
            const departmentSelect = document.getElementById('department');

            if (!departmentSelect || !window.jQuery) {
                return;
            }

            function initializeEvaluationDepartmentSelect() {
                if (!jQuery.fn || !jQuery.fn.select2 || !jQuery(departmentSelect).is('select') || jQuery(departmentSelect).hasClass('select2-hidden-accessible')) {
                    return;
                }

                jQuery(departmentSelect).select2({
                    placeholder: 'Search department...',
                    width: '100%',
                    dropdownParent: jQuery('#evaluationModal'),
                    dropdownCssClass: 'evaluation-department-dropdown'
                });
            }

            if (jQuery.fn && jQuery.fn.select2) {
                initializeEvaluationDepartmentSelect();
                return;
            }

            const select2Script = document.createElement('script');
            select2Script.src = `{{ asset('assets/vendors/js/select2.min.js') }}`;
            select2Script.onload = initializeEvaluationDepartmentSelect;
            document.body.appendChild(select2Script);
        });

        document.addEventListener('input', function (e) {
            if (
                e.target.classList.contains('self-review') ||
                e.target.classList.contains('author-review') ||
                e.target.classList.contains('admin-review')
            ) {
                let max = parseFloat(e.target.getAttribute('max')) || 0;
                let value = parseFloat(e.target.value) || 0;

                if (value > max) {
                    alert(`Review point cannot be greater than ${max}`);
                    e.target.value = max;
                }
            }
        });
    </script>
@endsection
