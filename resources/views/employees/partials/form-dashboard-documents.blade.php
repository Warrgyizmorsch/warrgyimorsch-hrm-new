@php
    $emp = $employee ?? null;
    $photoUrl = ($emp && $emp->photo) ? asset('storage/' . $emp->photo) : '';
    $docTypes = \App\Models\EmployeeDocument::TYPES;
    $existingDocs = $emp ? $emp->documents->keyBy('type') : collect();
@endphp

<div class="hrm-card hrm-card-full">
    <div class="hrm-card-head">
        <i class="bi bi-folder2-open"></i>
        <h3>Documents</h3>
    </div>
    <div class="hrm-card-body">
        <div class="hrm-doc-grid">

            {{-- Employee Photo --}}
            <div class="hrm-doc-box {{ $photoUrl ? 'has-file has-preview' : '' }}" data-doc-type="image">
                <div class="hrm-doc-preview">
                    <img class="hrm-doc-preview-img" src="{{ $photoUrl }}" alt="Employee photo"
                        @if(!$photoUrl) hidden @endif
                        onerror="this.hidden=true;this.parentElement.querySelector('.hrm-doc-preview-icon')&&(this.parentElement.querySelector('.hrm-doc-preview-icon').hidden=false)">
                    <span class="hrm-doc-preview-icon" @if($photoUrl) hidden @endif>
                        <i class="bi bi-cloud-upload"></i>
                    </span>
                </div>
                <strong>Upload Employee Photo</strong>
                <small>Max size 2MB · PNG, JPG, WEBP</small>
                <span class="hrm-doc-btn">Choose File</span>
                <span class="hrm-doc-filename">{{ $photoUrl ? 'Current photo' : '' }}</span>
                <input type="file" id="photoInput" name="photo" accept="image/*">
            </div>

            {{-- Aadhaar / PAN / Offer Letter — types, limits and labels come from EmployeeDocument::TYPES --}}
            @foreach($docTypes as $type => $meta)
                @php
                    $doc = $existingDocs->get($type);
                    $isWord = str_contains($meta['rules'], 'docx');
                @endphp
                <div class="hrm-doc-box {{ $doc ? 'has-file has-preview' : '' }}" data-doc-type="file">
                    <div class="hrm-doc-preview">
                        <img class="hrm-doc-preview-img" alt="{{ $meta['label'] }} preview" hidden>
                        <span class="hrm-doc-preview-icon">
                            <i class="bi {{ $doc ? 'bi-file-earmark-check' : $meta['icon'] }}"></i>
                        </span>
                    </div>
                    <strong>{{ $meta['label'] }}</strong>
                    <small>{{ $isWord ? 'Max size 10MB · PDF or DOC' : 'Max size 5MB · PDF or image' }}</small>
                    <span class="hrm-doc-btn">{{ $doc ? 'Replace File' : 'Choose File' }}</span>
                    <span class="hrm-doc-filename">{{ $doc?->original_name }}</span>
                    @if($doc)
                        <a href="{{ route('employee-documents.download', $doc->id) }}" target="_blank" class="hrm-doc-view">
                            <i class="bi bi-eye"></i> View current
                        </a>
                    @endif
                    @error("documents.$type")
                        <span class="hrm-doc-error">{{ $message }}</span>
                    @enderror
                    <input type="file" name="documents[{{ $type }}]"
                        accept="{{ $isWord ? '.pdf,.doc,.docx' : '.pdf,.png,.jpg,.jpeg,.webp' }}">
                </div>
            @endforeach

        </div>
    </div>
</div>

<script>
(function () {
    function previewIconForFile(file) {
        if (file.type.startsWith('image/')) return null;
        const name = (file.name || '').toLowerCase();
        if (file.type === 'application/pdf' || name.endsWith('.pdf')) {
            return 'bi-file-earmark-pdf';
        }
        if (name.endsWith('.doc') || name.endsWith('.docx') || file.type.includes('word')) {
            return 'bi-file-earmark-word';
        }
        return 'bi-file-earmark-check';
    }

    function updateDocBox(input) {
        const box = input.closest('.hrm-doc-box');
        if (!box) return;

        const file = input.files && input.files[0];
        const previewImg = box.querySelector('.hrm-doc-preview-img');
        const previewIcon = box.querySelector('.hrm-doc-preview-icon');
        const nameEl = box.querySelector('.hrm-doc-filename');

        if (!file) return;

        box.classList.add('has-file');
        if (nameEl) nameEl.textContent = file.name;

        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function (e) {
                if (previewImg) {
                    previewImg.src = e.target.result;
                    previewImg.hidden = false;
                }
                if (previewIcon) previewIcon.hidden = true;
                box.classList.add('has-preview');
            };
            reader.readAsDataURL(file);
            return;
        }

        if (previewImg) {
            previewImg.hidden = true;
            previewImg.removeAttribute('src');
        }
        if (previewIcon) {
            const iconClass = previewIconForFile(file);
            previewIcon.innerHTML = '<i class="bi ' + iconClass + '"></i>';
            previewIcon.hidden = false;
        }
        box.classList.add('has-preview');
    }

    document.querySelectorAll('.hrm-doc-box input[type="file"]').forEach(function (input) {
        input.addEventListener('change', function () {
            updateDocBox(this);
        });
    });
})();
</script>
