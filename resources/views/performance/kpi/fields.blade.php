<div class="mst-offcanvas-card">
    <div class="mst-offcanvas-card-head">
        <i class="feather-bar-chart-2"></i>
        <div>
            <h3>KPI Details</h3>
        </div>
    </div>
    <div class="mst-offcanvas-card-body">
        <div class="mst-field">
            <label>Department <span class="req">*</span></label>
            <div class="dropdown">
                <button class="wghrm-custom-select-btn dropdown-toggle w-100" type="button"
                    data-bs-toggle="dropdown" data-bs-auto-close="outside" id="{{ $prefix }}DepartmentBtn">
                    Select department
                </button>
                <div class="dropdown-menu wghrm-custom-dropdown-menu w-100">
                    <div class="wghrm-custom-search-box">
                        <input type="text" class="wghrm-custom-search-input" placeholder="Search department..."
                            onkeyup="wghrmFilterItems(this)" onclick="event.stopPropagation();" onkeydown="event.stopPropagation();">
                    </div>
                    <div style="max-height: 220px; overflow-y: auto;">
                        @foreach($departments as $dept)
                            <a class="dropdown-item wghrm-custom-dropdown-item" href="javascript:void(0);"
                                onclick="document.getElementById('{{ $prefix }}DepartmentId').value='{{ $dept->id }}'; document.getElementById('{{ $prefix }}DepartmentBtn').innerText='{{ addslashes($dept->name) }}'; bootstrap.Dropdown.getInstance(this.closest('.dropdown').querySelector('.dropdown-toggle')).hide();">
                                {{ $dept->name }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
            <input type="hidden" id="{{ $prefix }}DepartmentId" name="department_id" value="">
        </div>
        <div class="mst-field">
            <label for="{{ $prefix }}Title">Title <span class="req">*</span></label>
            <div class="mst-input-wrap">
                <i class="feather-type"></i>
                <input type="text" name="title" id="{{ $prefix }}Title" class="form-control" placeholder="e.g. Monthly Sales Target" required>
            </div>
        </div>
        <div class="row g-2">
            <div class="col-6">
                <div class="mst-field">
                    <label for="{{ $prefix }}TargetValue">Target Value <span class="req">*</span></label>
                    <input type="number" step="0.01" min="0" name="target_value" id="{{ $prefix }}TargetValue" class="form-control" required>
                </div>
            </div>
            <div class="col-6">
                <div class="mst-field">
                    <label for="{{ $prefix }}Unit">Unit</label>
                    <input type="text" name="unit" id="{{ $prefix }}Unit" class="form-control" placeholder="%, count, ₹...">
                </div>
            </div>
        </div>
        <div class="mst-field">
            <label for="{{ $prefix }}Weightage">Weightage (%)</label>
            <input type="number" step="0.01" min="0" max="100" name="weightage" id="{{ $prefix }}Weightage" class="form-control" placeholder="0">
        </div>
        <div class="mst-field mb-0">
            <label for="{{ $prefix }}AutoMetric">Auto-calculate from</label>
            <select name="auto_metric" id="{{ $prefix }}AutoMetric" class="form-control">
                <option value="">Manual entry (default)</option>
                @foreach(\App\Models\Kpi::AUTO_METRICS as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
            <p class="text-muted small mt-1 mb-0">When set, the actual value is computed automatically at assignment time and can be recomputed later — no manual typing needed.</p>
        </div>
    </div>
</div>
