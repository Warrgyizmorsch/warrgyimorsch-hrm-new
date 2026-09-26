@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/attendance-management.css') }}?v={{ filemtime(public_path('assets/css/attendance-management.css')) ?: time() }}">
<link rel="stylesheet" href="{{ asset('assets/css/master-management.css') }}?v={{ filemtime(public_path('assets/css/master-management.css')) ?: time() }}">
@endpush

@section('content')
@php
    $primaryAction = '<button type="button" class="zoho-btn-primary" data-bs-toggle="offcanvas" data-bs-target="#addLetterTemplateModal" title="Add letter template"><i class="feather-plus"></i> Add</button>';
@endphp

<div class="zoho-page-shell master-page attendance-page">
    @include('master.partials.list-header', [
        'masterTitle' => 'Master Module',
        'masterViewLabel' => 'Letter Templates',
        'masterActive' => 'letter-templates',
        'primaryAction' => $primaryAction,
    ])

    <div class="main-content zoho-module-content">
        @if ($message = Session::get('success'))
            <div class="attendance-alert" role="alert">
                <i class="feather-check-circle"></i>
                <span>{{ $message }}</span>
                <button type="button" class="btn-close ms-auto shadow-none" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="mst-filter-summary mb-3">
            <span class="att-stat-chip mst-summary-chip mst-summary-chip--total">
                Total <strong>{{ $totalCount }}</strong>
            </span>
            <span class="att-stat-chip mst-summary-chip mst-summary-chip--active">
                Active <strong>{{ $activeCount }}</strong>
            </span>
        </div>

        <div class="zoho-people-table-card">
            <div class="card-body p-0 zoho-list-body">
                <div class="table-responsive zoho-table-wrap">
                    <table class="table zoho-data-table mb-0">
                        <thead>
                            <tr>
                                <th class="col-num">Sr. No.</th>
                                <th>Letter Type</th>
                                <th>Title</th>
                                <th>Status</th>
                                <th class="text-end" style="width: 100px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($templates as $template)
                                <tr>
                                    <td class="text-muted fw-semibold">{{ $loop->iteration }}</td>
                                    <td><span class="mst-meta-badge">{{ $template->label }}</span></td>
                                    <td><span class="mst-name-cell">{{ $template->title }}</span></td>
                                    <td>
                                        @if($template->is_active)
                                            <span class="mst-status-badge mst-status-badge--active">Active</span>
                                        @else
                                            <span class="mst-status-badge mst-status-badge--inactive">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="mst-row-actions">
                                            <button type="button"
                                                    class="zoho-icon-btn"
                                                    onclick='editLetterTemplate(@json($template))'
                                                    title="Edit template">
                                                <i class="feather-edit-2"></i>
                                            </button>
                                            <form action="{{ route('master.letter-template.destroy', $template->id) }}"
                                                  method="POST"
                                                  class="d-inline"
                                                  onsubmit="return deleteData(event);">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="zoho-icon-btn zoho-icon-btn--danger"
                                                        title="Delete template">
                                                    <i class="feather-trash-2"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5">
                                        <div class="attendance-empty">
                                            <i class="feather-file-text"></i>
                                            <p>No letter templates yet.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@php
    $placeholderList = \App\Models\LetterTemplate::PLACEHOLDERS;
    $typeOptions = \App\Models\LetterTemplate::TYPES;
@endphp

<div class="offcanvas offcanvas-end mst-offcanvas shadow-lg" tabindex="-1" id="addLetterTemplateModal" style="width: 640px;">
    <div class="offcanvas-header zoho-offcanvas-head border-bottom">
        <h5 class="offcanvas-title zoho-offcanvas-title">Add Letter Template</h5>
        <button type="button" class="zoho-offcanvas-close" data-bs-dismiss="offcanvas" aria-label="Close">
            <i class="feather-x"></i>
        </button>
    </div>
    <form action="{{ route('master.letter-template.store') }}" method="POST" class="d-flex flex-column h-100">
        @csrf
        <div class="offcanvas-body flex-grow-1">
            @include('master.partials.letter-template-fields', ['prefix' => 'add'])
        </div>
        <div class="mst-offcanvas-foot">
            <button type="button" class="zoho-btn-outline flex-fill" data-bs-dismiss="offcanvas">Cancel</button>
            <button type="submit" class="zoho-btn-primary flex-fill">
                <i class="feather-save"></i> Save
            </button>
        </div>
    </form>
</div>

<div class="offcanvas offcanvas-end mst-offcanvas shadow-lg" tabindex="-1" id="editLetterTemplateModal" style="width: 640px;">
    <div class="offcanvas-header zoho-offcanvas-head border-bottom">
        <h5 class="offcanvas-title zoho-offcanvas-title">Edit Letter Template</h5>
        <button type="button" class="zoho-offcanvas-close" data-bs-dismiss="offcanvas" aria-label="Close">
            <i class="feather-x"></i>
        </button>
    </div>
    <form id="editLetterTemplateForm" method="POST" class="d-flex flex-column h-100">
        @csrf
        @method('PUT')
        <div class="offcanvas-body flex-grow-1">
            @include('master.partials.letter-template-fields', ['prefix' => 'edit'])
            <div class="mst-edit-switch mb-0">
                <div>
                    <div class="mst-edit-switch-label">Active status</div>
                    <p class="mst-edit-switch-desc">Inactive templates cannot be used to generate new letters</p>
                </div>
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" name="is_active" id="editLetterTemplateActive" value="1">
                </div>
            </div>
        </div>
        <div class="mst-offcanvas-foot">
            <button type="button" class="zoho-btn-outline flex-fill" data-bs-dismiss="offcanvas">Cancel</button>
            <button type="submit" class="zoho-btn-primary flex-fill">
                <i class="feather-check"></i> Update
            </button>
        </div>
    </form>
</div>

<script>
    function editLetterTemplate(data) {
        document.getElementById('editLetterTemplateForm').action = "{{ url('/master/letter-template') }}/" + data.id;
        document.getElementById('editType').value = data.type || '';
        document.getElementById('editTitle').value = data.title || '';
        document.getElementById('editContent').value = data.content || '';
        document.getElementById('editLetterTemplateActive').checked = !!data.is_active;
        new bootstrap.Offcanvas(document.getElementById('editLetterTemplateModal')).show();
    }
</script>
@endsection
