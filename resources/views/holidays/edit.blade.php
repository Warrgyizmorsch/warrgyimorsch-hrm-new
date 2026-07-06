@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/holidays-management.css') }}?v={{ filemtime(public_path('assets/css/holidays-management.css')) ?: time() }}">
@endpush

@section('content')
<div class="zoho-page-shell hol-edit-page">
    @include('layouts.partials.zoho-people-list-header', [
        'title' => 'Holiday Master',
        'viewLabel' => 'Edit Holiday',
        'scopeLinks' => [
            ['label' => 'Home', 'url' => route('dashboard'), 'active' => false],
            ['label' => 'Holidays', 'url' => route('holidays.index'), 'active' => false],
            ['label' => 'Edit', 'url' => route('holidays.edit', $holiday->id), 'active' => true],
        ],
    ])

    <div class="main-content zoho-module-content">
        <div class="hol-edit-card">
            <div class="hol-edit-card-head">
                <h2>Edit Holiday</h2>
                <p>Update holiday title and date for the company calendar.</p>
            </div>

            <form method="POST" action="{{ route('holidays.update', $holiday->id) }}">
                @csrf
                @method('PUT')

                <div class="hol-edit-card-body">
                    <div class="hol-form-field">
                        <label for="holidayTitle">Holiday Title</label>
                        <input type="text"
                               name="title"
                               id="holidayTitle"
                               class="form-control hol-uppercase"
                               value="{{ $holiday->title }}"
                               placeholder="Enter holiday title"
                               required>
                    </div>

                    <div class="hol-form-field mb-0">
                        <label for="holidayDate">Holiday Date</label>
                        <input type="date"
                               name="date"
                               id="holidayDate"
                               class="form-control"
                               value="{{ \Carbon\Carbon::parse($holiday->date)->format('Y-m-d') }}"
                               required>
                    </div>
                </div>

                <div class="hol-edit-actions">
                    <a href="{{ route('holidays.index') }}" class="zoho-btn-outline">
                        <i class="feather-arrow-left"></i> Back
                    </a>
                    <button type="submit" class="zoho-btn-primary">
                        <i class="feather-check"></i> Update Holiday
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.hol-uppercase').forEach(function (input) {
    input.addEventListener('input', function () {
        this.value = this.value.toUpperCase();
    });
});
</script>
@endsection
