{{-- Success, error and validation messages for the Performance pages (KRA / KPIs / Assignments / SOPs). --}}
@if ($message = Session::get('success'))
    <div class="attendance-alert" role="alert">
        <i class="feather-check-circle"></i>
        <span>{{ $message }}</span>
        <button type="button" class="btn-close ms-auto shadow-none" data-bs-dismiss="alert"></button>
    </div>
@endif

@if ($message = Session::get('error'))
    <div class="alert alert-danger d-flex align-items-center gap-2 py-2" role="alert">
        <i class="feather-alert-circle"></i>
        <span>{{ $message }}</span>
        <button type="button" class="btn-close ms-auto shadow-none" data-bs-dismiss="alert"></button>
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger py-2" role="alert">
        <div class="d-flex align-items-center gap-2"><i class="feather-alert-circle"></i><strong>Not saved — please fix:</strong></div>
        <ul class="mb-0 mt-1 small">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
