@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/holidays-management.css') }}?v={{ filemtime(public_path('assets/css/holidays-management.css')) ?: time() }}">
@endpush

@section('content')
@php
    $role = str_replace(' ', '_', strtolower(auth()->user()->role ?? 'employee'));
    $isAdmin = in_array($role, ['super_admin', 'manager', 'hr_executive', 'hr_intern', 'business_operation_head']);
    $today = now()->startOfDay();
@endphp

<div class="zoho-page-shell holidays-page">
    @include('layouts.partials.zoho-people-list-header', [
        'title' => 'Holiday Master',
        'viewLabel' => 'Holiday List',
        'scopeLinks' => [
            ['label' => 'Home', 'url' => route('dashboard'), 'active' => false],
            ['label' => 'Leave', 'url' => route('leave.history'), 'active' => false],
            ['label' => 'Holidays', 'url' => route('holidays.index'), 'active' => true],
        ],
    ])

    <div class="main-content zoho-module-content">
        <div class="hol-stat-row">
            <span class="hol-stat-chip">
                <i class="feather-calendar"></i>
                <span><strong>{{ $totalCount }}</strong> Total holidays</span>
            </span>
            <span class="hol-stat-chip hol-stat-chip--upcoming">
                <i class="feather-sun"></i>
                <span><strong>{{ $upcomingCount }}</strong> Upcoming</span>
            </span>
            <span class="hol-stat-chip">
                <i class="feather-clock"></i>
                <span><strong>{{ $thisYearCount }}</strong> In {{ now()->year }}</span>
            </span>
            @if($nextHoliday)
                <span class="hol-stat-chip hol-stat-chip--next">
                    <i class="feather-star"></i>
                    <span>Next: <strong>{{ $nextHoliday->title }}</strong> · {{ \Carbon\Carbon::parse($nextHoliday->date)->format('d M Y') }}</span>
                </span>
            @endif
        </div>

        <div class="row g-3">
            @if($isAdmin)
                <div class="col-xl-4 col-lg-5">
                    <div class="hol-form-card">
                        <div class="hol-form-card-head">
                            <span class="hol-form-card-icon"><i class="feather-plus-circle"></i></span>
                            <div>
                                <h3>Add Holiday</h3>
                                <p>Create a new company holiday entry</p>
                            </div>
                        </div>
                        <form id="holidayForm">
                            @csrf
                            <div class="hol-form-card-body">
                                <div class="hol-form-field">
                                    <label for="holidayTitle">Holiday Title</label>
                                    <input type="text"
                                           name="title"
                                           id="holidayTitle"
                                           class="form-control"
                                           placeholder="e.g. Independence Day"
                                           required>
                                </div>
                                <div class="hol-form-field mb-0">
                                    <label for="holidayDate">Holiday Date</label>
                                    <input type="date"
                                           name="date"
                                           id="holidayDate"
                                           class="form-control"
                                           value="{{ date('Y-m-d') }}"
                                           onclick="this.showPicker()"
                                           required>
                                </div>
                            </div>
                            <div class="hol-form-card-foot">
                                <button type="submit" class="zoho-btn-primary w-100">
                                    <i class="feather-save"></i> Save Holiday
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

            <div class="{{ $isAdmin ? 'col-xl-8 col-lg-7' : 'col-12' }}">
                <div class="zoho-people-table-card">
                    <div class="zoho-people-table-toolbar d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <form method="GET" action="{{ route('holidays.index') }}" class="zoho-people-table-search">
                            <i class="feather-search"></i>
                            <input type="text"
                                   name="search"
                                   id="holidaySearch"
                                   value="{{ request('search') }}"
                                   placeholder="Search holidays..."
                                   onkeydown="if(event.key==='Enter') this.form.submit()">
                        </form>
                        <div class="zoho-list-bar mb-0 border-0 bg-transparent p-0">
                            <span class="text-muted small fw-semibold">Show</span>
                            <div class="dropdown">
                                <button class="wghrm-custom-select-btn dropdown-toggle" type="button"
                                        data-bs-toggle="dropdown" id="showEntriesBtn"
                                        style="width: 72px; height: 34px; padding: 0 10px;">
                                    {{ $perPage ?? 10 }}
                                </button>
                                <div class="dropdown-menu wghrm-custom-dropdown-menu shadow-sm border-0" style="min-width: 72px;">
                                    @foreach([10, 20] as $size)
                                        <a class="dropdown-item wghrm-custom-dropdown-item {{ ($perPage ?? 10) == $size ? 'active' : '' }}"
                                           href="{{ request()->fullUrlWithQuery(['show' => $size, 'page' => 1]) }}">{{ $size }}</a>
                                    @endforeach
                                </div>
                            </div>
                            <span class="text-muted small fw-semibold">entries</span>
                        </div>
                    </div>

                    <div class="card-body p-0 zoho-list-body">
                        <div class="table-responsive zoho-table-wrap">
                            <table class="table zoho-data-table mb-0" id="holidayTable">
                                <thead>
                                    <tr>
                                        <th class="col-num">Sr. No.</th>
                                        <th>Holiday Title</th>
                                        <th>Date</th>
                                        @if($isAdmin)
                                            <th class="text-center">Action</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($holidays as $index => $h)
                                        @php
                                            $holidayDate = \Carbon\Carbon::parse($h->date)->startOfDay();
                                            $dateClass = 'hol-date-badge';
                                            if ($holidayDate->equalTo($today)) {
                                                $dateClass .= ' hol-date-badge--today';
                                            } elseif ($holidayDate->lt($today)) {
                                                $dateClass .= ' hol-date-badge--past';
                                            } else {
                                                $dateClass .= ' hol-date-badge--upcoming';
                                            }
                                        @endphp
                                        <tr class="holiday-row">
                                            <td class="text-muted fw-semibold">{{ ($holidays->currentPage() - 1) * $holidays->perPage() + $loop->iteration }}</td>
                                            <td><span class="hol-title-cell">{{ $h->title }}</span></td>
                                            <td>
                                                <span class="{{ $dateClass }}">
                                                    <i class="feather-calendar"></i>
                                                    {{ $holidayDate->format('D, d M Y') }}
                                                </span>
                                            </td>
                                            @if($isAdmin)
                                                <td class="text-center">
                                                    <div class="hol-row-actions">
                                                        <a href="{{ route('holidays.edit', $h->id) }}"
                                                           class="zoho-icon-btn"
                                                           title="Edit holiday">
                                                            <i class="feather-edit-2"></i>
                                                        </a>
                                                        <button type="button"
                                                                class="zoho-icon-btn zoho-icon-btn--danger"
                                                                onclick="confirmDelete({{ $h->id }})"
                                                                title="Delete holiday">
                                                            <i class="feather-trash-2"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            @endif
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ $isAdmin ? 4 : 3 }}">
                                                <div class="hol-empty-state">
                                                    <div class="hol-empty-state-icon"><i class="feather-calendar"></i></div>
                                                    <h4>No holidays found</h4>
                                                    <p>{{ request('search') ? 'Try a different search term.' : 'Add company holidays to display them here.' }}</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if($holidays->hasPages())
                            <div class="hol-list-footer d-flex justify-content-center">
                                {{ $holidays->links('pagination::bootstrap-5') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if($isAdmin)
<script>
    const holidayForm = document.getElementById('holidayForm');
    if (holidayForm) {
        holidayForm.addEventListener('submit', function (e) {
            e.preventDefault();

            fetch('{{ route('holidays.store') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    title: document.getElementById('holidayTitle').value,
                    date: document.getElementById('holidayDate').value
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Toast.fire({ icon: 'success', title: 'Holiday added successfully!' });
                    setTimeout(() => window.location.reload(), 1200);
                } else {
                    Toast.fire({ icon: 'error', title: data.message || 'Error adding holiday' });
                }
            })
            .catch(() => Toast.fire({ icon: 'error', title: 'Something went wrong!' }));
        });
    }

    function confirmDelete(id) {
        Swal.fire({
            title: 'Delete holiday?',
            text: 'This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete',
            cancelButtonText: 'Cancel',
            reverseButtons: true,
            customClass: {
                confirmButton: 'zoho-btn-primary px-4',
                cancelButton: 'zoho-btn-outline px-4 me-2'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) deleteHoliday(id);
        });
    }

    function deleteHoliday(id) {
        fetch('/holidays/' + id, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                Toast.fire({ icon: 'success', title: 'Holiday deleted successfully!' });
                setTimeout(() => window.location.reload(), 1200);
            } else {
                Toast.fire({ icon: 'error', title: 'Error deleting holiday' });
            }
        })
        .catch(() => Toast.fire({ icon: 'error', title: 'Something went wrong!' }));
    }
</script>
@endif
@endsection
