@extends('layouts.app')

@section('content')
    <div class="page-header d-flex justify-content-between align-items-center">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Employee Review</h5>
            </div>
            <ul class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                <li class="breadcrumb-item">Personal Review</li>
            </ul>
        </div>
        <div class="page-header-right">
            <button id="openCreateReviewBtn" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createReviewModal" data-mode="create">
                <i class="fa fa-plus me-1"></i> Create Review
            </button>
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
                        <h5 class="modal-title text-white">Personal Review Evaluation</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="reviewForm" action="{{ url('/employee-review/store') }}" method="POST">
                        @csrf
                        <div class="modal-body">
                            @php
                                $months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
                                $periods = ['First Half', 'Second Half'];
                            @endphp
                            <div class="row">
                                @php
                                    $columnClass = ($isAdmin || $isTeamLeader) ? 'col-md-4' : 'col-md-6';
                                @endphp
                                <div class="{{ $columnClass }}">
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

                                <div class="{{ $columnClass }}">
                                    <label class="fw-bold mb-1">Select Period</label>
                                    <div class="review-select" data-select>
                                        <input type="hidden" name="period" value="{{ old('period', 'First Half') }}" required>
                                        <button type="button" class="review-select-trigger" data-select-trigger aria-expanded="false">
                                            <span data-select-label>{{ old('period', 'First Half') }}</span>
                                            <i class="fa fa-chevron-down review-select-icon"></i>
                                        </button>
                                        <div class="review-select-menu" data-select-menu hidden>
                                            <div class="review-select-options" data-select-options>
                                                @foreach($periods as $period)
                                                    <button
                                                        type="button"
                                                        class="review-select-option {{ old('period', 'First Half') === $period ? 'is-selected' : '' }}"
                                                        data-select-option
                                                        data-value="{{ $period }}"
                                                    >
                                                        {{ $period }}
                                                    </button>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                @if($isAdmin || $isTeamLeader)
                                    <div class="{{ $columnClass }}">
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
                                <tbody>
                                    <tr>
                                        <td>Attendance <input type="hidden" name="criteria_name[]" value="Attendance"></td>
                                        <td>5 <input type="hidden" name="criteria_point[]" value="5"></td>
                                        <td><input type="number" step=".5" class="form-control self-input" data-point="5" name="self_review[]" min="0" max="5" value="{{ old('self_review.0') }}"></td>
                                        @if($isTeamLeader)
                                            <td><input type="number" step=".5" class="form-control author-input" data-point="5" name="author_review[]" min="0" max="5" value="{{ old('author_review.0') }}"></td>                                      
                                        @endif
                                        @if($isAdmin)
                                            <td><input type="number" step=".5" class="form-control author-input" data-point="5" name="author_review[]" min="0" max="5" value="{{ old('author_review.0') }}"></td>
                                            <td><input type="number" step=".5" class="form-control admin-input" data-point="5" name="admin_review[]" min="0" max="5" value="{{ old('admin_review.0') }}"></td>
                                        @endif
                                    </tr>
                                    <tr>
                                        <td>Behaviour <input type="hidden" name="criteria_name[]" value="Behaviour"></td>
                                        <td>7.5 <input type="hidden" name="criteria_point[]" value="7.5"></td>
                                        <td><input type="number" step=".5" class="form-control self-input" data-point="7.5" name="self_review[]" min="0" max="7.5" value="{{ old('self_review.1') }}"></td>
                                        @if($isTeamLeader)
                                            <td><input type="number" step=".5" class="form-control author-input" data-point="7.5" name="author_review[]" min="0" max="7.5" value="{{ old('author_review.1') }}"></td>
                                        @endif
                                        @if($isAdmin)
                                            <td><input type="number" step=".5" class="form-control author-input" data-point="7.5" name="author_review[]" min="0" max="7.5" value="{{ old('author_review.1') }}"></td>
                                            <td><input type="number" step=".5" class="form-control admin-input" data-point="7.5" name="admin_review[]" min="0" max="7.5" value="{{ old('admin_review.1') }}"></td>
                                        @endif
                                    </tr>
                                    <tr>
                                        <td>Results <input type="hidden" name="criteria_name[]" value="Results"></td>
                                        <td>12.5 <input type="hidden" name="criteria_point[]" value="12.5"></td>
                                        <td><input type="number" step=".5" class="form-control self-input" data-point="12.5" name="self_review[]" min="0" max="12.5" value="{{ old('self_review.2') }}"></td>
                                        @if($isTeamLeader)
                                            <td><input type="number" step=".5" class="form-control author-input" data-point="12.5" name="author_review[]" min="0" max="12.5" value="{{ old('author_review.2') }}"></td>
                                        @endif
                                        @if($isAdmin)
                                            <td><input type="number" step=".5" class="form-control author-input" data-point="12.5" name="author_review[]" min="0" max="12.5" value="{{ old('author_review.2') }}"></td>
                                            <td><input type="number" step=".5" class="form-control admin-input" data-point="12.5" name="admin_review[]" min="0" max="12.5" value="{{ old('admin_review.2') }}"></td>
                                        @endif
                                    </tr>
                                    <tr>
                                        <td>Extra Efforts <input type="hidden" name="criteria_name[]" value="Extra Efforts"></td>
                                        <td>5 <input type="hidden" name="criteria_point[]" value="5"></td>
                                        <td><input type="number" step=".5" class="form-control self-input" data-point="5" name="self_review[]" min="0" max="5" value="{{ old('self_review.3') }}"></td>
                                        @if($isTeamLeader)
                                            <td><input type="number" step=".5" class="form-control author-input" data-point="5" name="author_review[]" min="0" max="5" value="{{ old('author_review.3') }}"></td>
                                        @endif
                                        @if($isAdmin)
                                            <td><input type="number" step=".5" class="form-control author-input" data-point="5" name="author_review[]" min="0" max="5" value="{{ old('author_review.3') }}"></td>
                                            <td><input type="number" step=".5" class="form-control admin-input" data-point="5" name="admin_review[]" min="0" max="5" value="{{ old('admin_review.3') }}"></td>
                                        @endif
                                    </tr>
                                    <tr>
                                        <td>Honesty <input type="hidden" name="criteria_name[]" value="Honesty"></td>
                                        <td>5 <input type="hidden" name="criteria_point[]" value="5"></td>
                                        <td><input type="number" step=".5" class="form-control self-input" data-point="5" name="self_review[]" min="0" max="5" value="{{ old('self_review.4') }}"></td>
                                        @if($isTeamLeader)
                                            <td><input type="number" step=".5" class="form-control author-input" data-point="5" name="author_review[]" min="0" max="5" value="{{ old('author_review.4') }}"></td>          
                                        @endif
                                        @if($isAdmin)                                        
                                            <td><input type="number" step=".5" class="form-control author-input" data-point="5" name="author_review[]" min="0" max="5" value="{{ old('author_review.4') }}"></td>
                                            <td><input type="number" step=".5" class="form-control admin-input" data-point="5" name="admin_review[]" min="0" max="5" value="{{ old('admin_review.4') }}"></td>
                                        @endif
                                    </tr>
                                    <tr>
                                        <td>Punctuality <input type="hidden" name="criteria_name[]" value="Punctuality"></td>
                                        <td>5 <input type="hidden" name="criteria_point[]" value="5"></td>
                                        <td><input type="number" step=".5" class="form-control self-input" data-point="5" name="self_review[]" min="0" max="5" value="{{ old('self_review.5') }}"></td>
                                        @if($isTeamLeader)
                                            <td><input type="number" step=".5" class="form-control author-input" data-point="5" name="author_review[]" min="0" max="5" value="{{ old('author_review.5') }}"></td>                                        
                                        @endif
                                        @if($isAdmin)
                                            <td><input type="number" step=".5" class="form-control author-input" data-point="5" name="author_review[]" min="0" max="5" value="{{ old('author_review.5') }}"></td>                                        
                                            <td><input type="number" step=".5" class="form-control admin-input" data-point="5" name="admin_review[]" min="0" max="5" value="{{ old('admin_review.5') }}"></td>
                                        @endif
                                    </tr>
                                    <tr>
                                        <td>Reporting <input type="hidden" name="criteria_name[]" value="Reporting"></td>
                                        <td>7.5 <input type="hidden" name="criteria_point[]" value="7.5"></td>
                                        <td><input type="number" step=".5" class="form-control self-input" data-point="7.5" name="self_review[]" min="0" max="7.5" value="{{ old('self_review.6') }}"></td>
                                        @if($isTeamLeader)
                                            <td><input type="number" step=".5" class="form-control author-input" data-point="7.5" name="author_review[]" min="0" max="7.5" value="{{ old('author_review.6') }}"></td>
                                        @endif
                                        @if($isAdmin)
                                        <td><input type="number" step=".5" class="form-control author-input" data-point="7.5" name="author_review[]" min="0" max="7.5" value="{{ old('author_review.6') }}"></td>
                                        <td><input type="number" step=".5" class="form-control admin-input" data-point="7.5" name="admin_review[]" min="0" max="7.5" value="{{ old('admin_review.6') }}"></td>
                                        @endif
                                    </tr>
                                    <tr>
                                        <td>Customer Relationship <input type="hidden" name="criteria_name[]" value="Customer Relationship"></td>
                                        <td>2.5 <input type="hidden" name="criteria_point[]" value="2.5"></td>
                                        <td><input type="number" step=".5" class="form-control self-input" data-point="2.5" name="self_review[]" min="0" max="2.5" value="{{ old('self_review.7') }}"></td>
                                        @if($isTeamLeader)
                                            <td><input type="number" step=".5" class="form-control author-input" data-point="2.5" name="author_review[]" min="0" max="2.5" value="{{ old('author_review.7') }}"></td>
                                        @endif
                                        @if($isAdmin)
                                            <td><input type="number" step=".5" class="form-control author-input" data-point="2.5" name="author_review[]" min="0" max="2.5" value="{{ old('author_review.7') }}"></td>
                                            <td><input type="number" step=".5" class="form-control admin-input" data-point="2.5" name="admin_review[]" min="0" max="2.5" value="{{ old('admin_review.7') }}"></td>
                                        @endif
                                    </tr>
                                    <tr>
                                        <td><b>Total</b></td>
                                        <td><input readonly value="50" class="form-control"></td>
                                        <td><input readonly id="selfTotal" name="self_total" class="form-control" value="{{ old('self_total', 0) }}"></td>
                                        @if($isTeamLeader)
                                            <td><input readonly id="authorTotal" name="author_total" class="form-control" value="{{ old('author_total', 0) }}"></td>
                                        @endif
                                        @if($isAdmin)
                                            <td><input readonly id="authorTotal" name="author_total" class="form-control" value="{{ old('author_total', 0) }}"></td>
                                            <td><input readonly id="adminTotal" name="admin_total" class="form-control" value="{{ old('admin_total', 0) }}"></td>
                                        @endif
                                    </tr>
                                </tbody>
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

        @php
            $scoreText = fn ($value) => rtrim(rtrim(number_format((float) $value, 1), '0'), '.');
            $reviewScore = function ($review) {
                if (!$review) {
                    return null;
                }

                if ((float) $review->admin_total > 0) {
                    return $review->admin_total;
                }

                if ((float) $review->author_total > 0) {
                    return $review->author_total;
                }

                return $review->self_total;
            };
        @endphp

        <!-- <h4 class="">Reviews</h4> -->
        <table class="table table-striped mt-2">
            <thead class="bg-primary text-white" style="height: 50px;">
                <tr>
                    <th>Sr</th>
                    <th>Rank</th>
                    <th>Employee</th>
                    <th>Month</th>
                    <th>1-15 Review</th>
                    <th>16-30 Review</th>
                    <th>Combined Result</th>
                    <th>System Result</th>
                    <th>Real Checks</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reviews as $reviewGroup)
                    <tr style="height: 50px;">
                        <td>{{ $loop->iteration }}</td>
                        <td>
                            <span class="badge {{ ($reviewGroup->objective['rank'] ?? 0) === 1 ? 'bg-success' : 'bg-secondary' }}">
                                #{{ $reviewGroup->objective['rank'] ?? '-' }}
                            </span>
                        </td>
                        <td>{{ $reviewGroup->employee_name }}</td>
                        <td>{{ $reviewGroup->month }}</td>
                        <td>
                            @if($reviewGroup->firstHalf)
                                <div class="fw-bold">{{ $scoreText($reviewScore($reviewGroup->firstHalf)) }} / 50</div>
                                <small class="text-muted">
                                    Self: {{ $scoreText($reviewGroup->firstHalf->self_total) }},
                                    TL: {{ $scoreText($reviewGroup->firstHalf->author_total) }},
                                    Admin: {{ $scoreText($reviewGroup->firstHalf->admin_total) }}
                                </small>
                            @else
                                <span class="text-muted">Pending</span>
                            @endif
                        </td>
                        <td>
                            @if($reviewGroup->secondHalf)
                                <div class="fw-bold">{{ $scoreText($reviewScore($reviewGroup->secondHalf)) }} / 50</div>
                                <small class="text-muted">
                                    Self: {{ $scoreText($reviewGroup->secondHalf->self_total) }},
                                    TL: {{ $scoreText($reviewGroup->secondHalf->author_total) }},
                                    Admin: {{ $scoreText($reviewGroup->secondHalf->admin_total) }}
                                </small>
                            @else
                                <span class="text-muted">Pending</span>
                            @endif
                        </td>
                        <td class="fw-bold">{{ $scoreText($reviewGroup->combined_total) }} / 100</td>
                        <td class="fw-bold text-primary">
                            {{ $scoreText($reviewGroup->objective['score'] ?? 0) }} / 100
                        </td>
                        <td>
                            <small class="d-block text-muted">
                                Late: {{ $reviewGroup->objective['late_days'] ?? 0 }} days
                                @if(($reviewGroup->objective['late_minutes'] ?? 0) > 0)
                                    ({{ intdiv($reviewGroup->objective['late_minutes'], 60) }}h {{ $reviewGroup->objective['late_minutes'] % 60 }}m)
                                @endif
                            </small>
                            <small class="d-block text-muted">
                                Missed reports: {{ $reviewGroup->objective['missed_reports'] ?? 0 }} / {{ $reviewGroup->objective['report_days'] ?? 0 }}
                            </small>
                            <small class="d-block text-muted">
                                Tasks: {{ $reviewGroup->objective['completed_tasks'] ?? 0 }} done, {{ $reviewGroup->objective['pending_tasks'] ?? 0 }} pending
                            </small>
                            <small class="d-block text-muted">
                                Penalty: -{{ $scoreText($reviewGroup->objective['penalty'] ?? 0) }},
                                Bonus: +{{ $scoreText($reviewGroup->objective['bonus'] ?? 0) }}
                            </small>
                        </td>
                        <td>
                            <div class="d-flex gap-1" style="height: 50px;">
                                @foreach([$reviewGroup->firstHalf, $reviewGroup->secondHalf] as $review)
                                    @if($review)
                                        <button class="btn btn-primary" data-bs-toggle="modal" style="height: 20px; width:20px" title="{{ $review->period }}" data-bs-target="#reviewModal{{ $review->id }}">
                                            <i class="fa fa-eye"></i>
                                        </button>
                                        @if($isAdmin || $isTeamLeader)
                                            <button
                                                class="btn btn-success edit-review-btn"
                                                style="height: 20px; width:20px"
                                                title="Edit {{ $review->period }}"
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
                                    @endif
                                @endforeach
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center text-muted">No reviews found.</td>
                    </tr>
                @endforelse
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

    @foreach($modalReviews as $review)
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
    </style>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const customSelects = document.querySelectorAll("[data-select]");

            function closeAllSelects(exceptSelect = null) {
                customSelects.forEach(function(select) {
                    if (select === exceptSelect) {
                        return;
                    }

                    const trigger = select.querySelector("[data-select-trigger]");
                    const menu = select.querySelector("[data-select-menu]");
                    if (!trigger || !menu) {
                        return;
                    }

                    select.classList.remove("is-open");
                    trigger.setAttribute("aria-expanded", "false");
                    menu.hidden = true;
                });
            }

            customSelects.forEach(function(select) {
                const hiddenInput = select.querySelector("input[type='hidden']");
                const trigger = select.querySelector("[data-select-trigger]");
                const menu = select.querySelector("[data-select-menu]");
                const searchInput = select.querySelector("[data-select-search]");
                const label = select.querySelector("[data-select-label]");
                const options = select.querySelectorAll("[data-select-option]");

                if (!hiddenInput || !trigger || !menu || !label) {
                    return;
                }

                trigger.addEventListener("click", function() {
                    const isOpen = select.classList.contains("is-open");
                    closeAllSelects(select);

                    select.classList.toggle("is-open", !isOpen);
                    trigger.setAttribute("aria-expanded", String(!isOpen));
                    menu.hidden = isOpen;

                    if (!isOpen && searchInput) {
                        searchInput.value = "";
                        options.forEach(option => option.classList.remove("is-hidden"));
                        searchInput.focus();
                    }
                });

                options.forEach(function(option) {
                    option.addEventListener("click", function() {
                        hiddenInput.value = option.dataset.value || "";
                        label.textContent = option.textContent.trim();
                        options.forEach(item => item.classList.remove("is-selected"));
                        option.classList.add("is-selected");
                        closeAllSelects();
                    });
                });

                if (searchInput) {
                    searchInput.addEventListener("input", function() {
                        const keyword = searchInput.value.trim().toLowerCase();

                        options.forEach(function(option) {
                            const text = option.textContent.toLowerCase();
                            option.classList.toggle("is-hidden", !text.includes(keyword));
                        });
                    });
                }
            });

            document.addEventListener("click", function(e) {
                if (!e.target.closest("[data-select]")) {
                    closeAllSelects();
                }
            });
            
            function updateTotals() {
                let selfTotal = 0;
                let authorTotal = 0;
                let adminTotal = 0;

                document.querySelectorAll(".self-input").forEach(function(input) {
                    let val = parseFloat(input.value);
                    if (!isNaN(val)) selfTotal += val;
                });

                document.querySelectorAll(".author-input").forEach(function(input) {
                    let val = parseFloat(input.value);
                    if (!isNaN(val)) authorTotal += val;
                });

                document.querySelectorAll(".admin-input").forEach(function(input) {
                    let val = parseFloat(input.value);
                    if (!isNaN(val)) adminTotal += val;
                });

                document.getElementById("selfTotal").value = selfTotal % 1 === 0 ? selfTotal : selfTotal.toFixed(1);
                document.getElementById("authorTotal").value = authorTotal % 1 === 0 ? authorTotal : authorTotal.toFixed(1);
                document.getElementById("adminTotal").value = adminTotal % 1 === 0 ? adminTotal : adminTotal.toFixed(1);
            }

            document.addEventListener("input", function(e) {
                if (e.target.classList.contains("self-input") || e.target.classList.contains("author-input") || e.target.classList.contains("admin-input")) {
                    let max = parseFloat(e.target.getAttribute("data-point")) || 0;
                    let val = parseFloat(e.target.value) || 0;

                    if (val > max && e.target.value !== "") {
                        alert("Cannot exceed " + max + " points for this criteria");
                        e.target.value = "";
                    }
                    updateTotals();
                }
            });

            // Run validation check on blur fallback
            document.addEventListener("focusout", function(e) {
                if (e.target.classList.contains("self-input") || e.target.classList.contains("author-input") || e.target.classList.contains("author-input")) {
                    let max = parseFloat(e.target.getAttribute("data-point")) || 0;
                    let val = parseFloat(e.target.value) || 0;

                    if (val > max && e.target.value !== "") {
                        e.target.value = "";
                        updateTotals();
                    }
                }
            });

            // Handle switching modal between create and edit modes
            const reviewForm = document.getElementById('reviewForm');
            const openCreateBtn = document.getElementById('openCreateReviewBtn');

            function setSelectValueWithinForm(name, value) {
                const input = reviewForm.querySelector(`input[name="${name}"]`);
                if (!input) return;
                input.value = value || '';
                const select = input.closest('.review-select');
                if (select) {
                    const options = select.querySelectorAll('[data-select-option]');
                    let foundText = '';
                    options.forEach(opt => {
                        if (opt.dataset.value == value) {
                            opt.classList.add('is-selected');
                            foundText = opt.textContent.trim();
                        } else {
                            opt.classList.remove('is-selected');
                        }
                    });
                    const label = select.querySelector('[data-select-label]');
                    if (label) label.textContent = foundText || value || label.textContent;
                }
            }

            function resetFormToCreate() {
                reviewForm.action = '{{ url('/employee-review/store') }}';
                // Clear month/period/employee selects
                ['month', 'period', 'user_id'].forEach(name => {
                    const input = reviewForm.querySelector(`input[name="${name}"]`);
                    if (!input) return;
                    input.value = '';
                    const select = input.closest('.review-select');
                    if (select) {
                        const label = select.querySelector('[data-select-label]');
                        if (label) {
                            if (name === 'user_id') label.textContent = 'Choose Employee...';
                            else if (name === 'month') label.textContent = 'Select Month';
                            else if (name === 'period') label.textContent = 'First Half';
                        }
                        select.querySelectorAll('[data-select-option]').forEach(op => op.classList.remove('is-selected'));
                    }
                });
                // Clear numeric inputs
                reviewForm.querySelectorAll('.self-input, .author-input, .admin-input').forEach(inp => inp.value = '');
                updateTotals();
            }

            if (openCreateBtn) {
                openCreateBtn.addEventListener('click', resetFormToCreate);
            }

            document.querySelectorAll('.edit-review-btn').forEach(btn => {
                btn.addEventListener('click', async function() {
                    const id = this.dataset.reviewId;
                    reviewForm.action = `/employee-review/${id}/update`;
                    setSelectValueWithinForm('month', this.dataset.month);
                    setSelectValueWithinForm('period', this.dataset.period);
                    setSelectValueWithinForm('user_id', this.dataset.userId);

                    try {
                        const res = await fetch(`/review-details/${id}`);
                        if (!res.ok) throw new Error('Failed to fetch review details');
                        const details = await res.json();

                        reviewForm.querySelectorAll('.self-input').forEach((inp, i) => {
                            if (details[i] && details[i].self_review !== undefined && details[i].self_review !== null) {
                                inp.value = details[i].self_review;
                            }
                        });
                        reviewForm.querySelectorAll('.author-input').forEach((inp, i) => {
                            if (details[i] && details[i].author_review !== undefined && details[i].author_review !== null && Number(details[i].author_review) !== 0) {
                                inp.value = details[i].author_review;
                            } else {
                                inp.value = '';
                            }
                        });
                        reviewForm.querySelectorAll('.admin-input').forEach((inp, i) => {
                            if (details[i] && details[i].admin_review !== undefined && details[i].admin_review !== null && Number(details[i].admin_review) !== 0) {
                                inp.value = details[i].admin_review;
                            } else {
                                inp.value = '';
                            }
                        });

                        updateTotals();
                    } catch (err) {
                        console.error(err);
                    }
                });
            });

            updateTotals();
        });
    </script>
@endsection
