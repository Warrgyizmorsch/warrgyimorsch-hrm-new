<div class="mst-offcanvas-card">
    <div class="mst-offcanvas-card-head">
        <i class="feather-file-text"></i>
        <div>
            <h3>Template Details</h3>
            <p>Use placeholders below — they are replaced with the employee's actual details when a letter is generated.</p>
        </div>
    </div>
    <div class="mst-offcanvas-card-body">
        <div class="mst-field">
            <label for="{{ $prefix }}Type">Letter Type <span class="req">*</span></label>
            <select name="type" id="{{ $prefix }}Type" class="form-control" required>
                <option value="">Select type</option>
                @foreach($typeOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="mst-field">
            <label for="{{ $prefix }}Title">Title <span class="req">*</span></label>
            <div class="mst-input-wrap">
                <i class="feather-type"></i>
                <input type="text" name="title" id="{{ $prefix }}Title" class="form-control" placeholder="e.g. Offer Letter" required>
            </div>
        </div>
        <div class="mst-field mb-0">
            <label for="{{ $prefix }}Content">Letter Content (HTML) <span class="req">*</span></label>
            <textarea name="content" id="{{ $prefix }}Content" class="form-control" rows="12" placeholder="Dear {{ '{{employee_name}}' }}, ..." required></textarea>
        </div>
    </div>
</div>

<div class="mst-offcanvas-card mt-3">
    <div class="mst-offcanvas-card-head">
        <i class="feather-hash"></i>
        <div>
            <h3>Available Placeholders</h3>
        </div>
    </div>
    <div class="mst-offcanvas-card-body">
        <div class="row g-2">
            @foreach($placeholderList as $tag => $desc)
                <div class="col-6">
                    <code style="font-size: 11px;">{{ $tag }}</code>
                    <div class="text-muted" style="font-size: 10px;">{{ $desc }}</div>
                </div>
            @endforeach
        </div>
    </div>
</div>
