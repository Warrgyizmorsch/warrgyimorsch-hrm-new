<div class="mst-offcanvas-card">
    <div class="mst-offcanvas-card-head">
        <i class="feather-file-text"></i>
        <div>
            <h3>SOP Details</h3>
        </div>
    </div>
    <div class="mst-offcanvas-card-body">
        <div class="mst-field">
            <label>Department</label>
            <div class="dropdown">
                <button class="wghrm-custom-select-btn dropdown-toggle w-100" type="button"
                    data-bs-toggle="dropdown" data-bs-auto-close="outside" id="{{ $prefix }}DepartmentBtn">
                    All Departments
                </button>
                <div class="dropdown-menu wghrm-custom-dropdown-menu w-100">
                    <div class="wghrm-custom-search-box">
                        <input type="text" class="wghrm-custom-search-input w-100" placeholder="Search department..."
                            onkeyup="wghrmFilterItems(this)" onclick="event.stopPropagation();" onkeydown="event.stopPropagation();">
                    </div>
                    <div style="max-height: 220px; overflow-y: auto;">
                        <a class="dropdown-item wghrm-custom-dropdown-item" href="javascript:void(0);"
                            onclick="document.getElementById('{{ $prefix }}DepartmentId').value=''; document.getElementById('{{ $prefix }}DepartmentBtn').innerText='All Departments'; bootstrap.Dropdown.getInstance(this.closest('.dropdown').querySelector('.dropdown-toggle')).hide();">
                            All Departments
                        </a>
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
            <label for="{{ $prefix }}Role">Role</label>
            <div class="mst-input-wrap">
                <i class="feather-user"></i>
                <input type="text" name="role" id="{{ $prefix }}Role" class="form-control" placeholder="Leave blank for all roles">
            </div>
        </div>
        <div class="mst-field">
            <label for="{{ $prefix }}Title">Title <span class="req">*</span></label>
            <div class="mst-input-wrap">
                <i class="feather-type"></i>
                <input type="text" name="title" id="{{ $prefix }}Title" class="form-control" required>
            </div>
        </div>
        <div class="mst-field">
            <label for="{{ $prefix }}Content">Content</label>
            <textarea name="content" id="{{ $prefix }}Content" class="form-control"></textarea>
        </div>
        <div class="mst-field mb-0">
            <label for="{{ $prefix }}Attachment">Document</label>
            @if($prefix === 'edit')
                {{-- Filled in by editSop() when the SOP already has a file. --}}
                <div id="editCurrentAttachment" class="d-none align-items-center justify-content-between gap-2 border rounded px-3 py-2 mb-2 small">
                    <a href="#" target="_blank" id="editCurrentAttachmentLink" class="text-primary text-truncate">
                        <i class="feather-paperclip me-1"></i><span></span>
                    </a>
                    <div class="form-check mb-0 flex-shrink-0">
                        <input class="form-check-input" type="checkbox" name="remove_attachment" value="1" id="editRemoveAttachment">
                        <label class="form-check-label text-danger" for="editRemoveAttachment">Remove</label>
                    </div>
                </div>
            @endif
            <input type="file" name="attachment" id="{{ $prefix }}Attachment" class="form-control"
                accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.png,.jpg,.jpeg,.webp">
            <div class="form-text">
                PDF, Word, Excel, PowerPoint or image · max 10 MB.
                {{ $prefix === 'edit' ? 'Uploading a new file replaces the current one.' : '' }}
                Write the content, upload a document, or both.
            </div>
        </div>
    </div>
</div>
