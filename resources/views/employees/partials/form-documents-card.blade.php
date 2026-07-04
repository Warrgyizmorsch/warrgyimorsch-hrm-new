@php
    $emp = $employee ?? null;
    $photoUrl = $emp && $emp->photo ? asset('storage/' . $emp->photo) : '';
    $photoLabel = $emp ? 'Update Employee Photo' : 'Upload Employee Photo';
@endphp

<div class="erp-form-card">
    <div class="erp-form-card-header">
        <h3>Documents</h3>
    </div>
    <div class="erp-form-card-body">
        <div class="row g-0 zoho-form-fields-grid">
            <div class="col-md-6 zoho-photo-field-cell zoho-form-field-full">
                <label>{{ $photoLabel }}</label>
                <div class="zoho-photo-inline">
                    <input type="file" id="photoInput" name="photo" accept="image/*" hidden>
                    <button type="button" class="zoho-photo-inline-btn" onclick="document.getElementById('photoInput').click()">
                        <span class="zoho-photo-inline-thumb">
                            <img id="previewImg" src="{{ $photoUrl }}" alt=""
                                style="display: {{ $photoUrl ? 'block' : 'none' }};">
                            <span id="placeholderText" class="zoho-photo-placeholder" style="display: {{ $photoUrl ? 'none' : 'flex' }};">
                                <i class="bi bi-camera"></i>
                            </span>
                        </span>
                        <span class="zoho-photo-inline-text">
                            <strong>Select photo</strong>
                            <small>Max 2MB · PNG, JPG, JPEG, WEBP</small>
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('photoInput')?.addEventListener('change', function (event) {
        const file = event.target.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = function (e) {
            const img = document.getElementById('previewImg');
            const placeholder = document.getElementById('placeholderText');
            img.src = e.target.result;
            img.style.display = 'block';
            if (placeholder) placeholder.style.display = 'none';
        };
        reader.readAsDataURL(file);
    });
</script>
