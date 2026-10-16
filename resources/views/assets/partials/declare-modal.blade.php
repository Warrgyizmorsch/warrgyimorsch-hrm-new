{{--
    "Declare an asset" popup, shared by My Assets (declare for yourself) and Asset Management
    (team leader / HR declaring for a team member).
    Pass $declareFor = collection of users to show the employee picker; omit it for self.
--}}
<div class="modal fade" id="declareAssetModal" tabindex="-1" aria-labelledby="declareAssetModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg premium-modal">
            <div class="modal-header">
                <h5 class="modal-title" id="declareAssetModalLabel">
                    {{ isset($declareFor) ? 'Add an Asset a Team Member Already Has' : 'Declare an Asset I Already Have' }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('assets.declare') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-4">
                    <div class="alert alert-info small py-2 mb-3" style="border-radius: 10px;">
                        Use this for a company device that's already with {{ isset($declareFor) ? 'the employee' : 'you' }} but isn't listed yet.
                        It will show as <b>Pending verification</b> until {{ isset($declareFor) ? 'HR' : 'your Team Leader or HR' }} confirms it.
                    </div>

                    @isset($declareFor)
                    <div class="mb-3">
                        <label class="fw-semibold mb-2">Employee <span class="text-danger">*</span></label>
                        <select class="form-select" name="user_id" required style="border-radius: 10px;">
                            <option value="">Select team member</option>
                            @foreach($declareFor as $u)
                                <option value="{{ $u->id }}" @selected(old('user_id') == $u->id)>{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endisset

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="fw-semibold mb-2">Device Type <span class="text-danger">*</span></label>
                            <select class="form-select" name="asset_type" required style="border-radius: 10px;">
                                @foreach(['Laptop', 'PC', 'Monitor', 'Keyboard', 'Mouse', 'Charger', 'Other'] as $t)
                                    <option value="{{ $t }}" @selected(old('asset_type') === $t)>{{ $t }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold mb-2">Brand / Model <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="declared_name" value="{{ old('declared_name') }}" required maxlength="150" placeholder="e.g. Dell Latitude 5440" style="border-radius: 10px;">
                        </div>
                        <div class="col-12">
                            <label class="fw-semibold mb-2">Serial Number / Service Tag</label>
                            <input type="text" class="form-control" name="declared_serial_number" value="{{ old('declared_serial_number') }}" maxlength="150" placeholder="Usually on a sticker under the device" style="border-radius: 10px;">
                        </div>
                        <div class="col-12">
                            <label class="fw-semibold mb-2">Configuration</label>
                            <textarea class="form-control" name="declared_configuration" rows="2" maxlength="2000" placeholder="Processor, RAM, storage..." style="border-radius: 10px;">{{ old('declared_configuration') }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="fw-semibold mb-2">Notes</label>
                            <textarea class="form-control" name="reason" rows="2" maxlength="1000" placeholder="e.g. Received from IT on joining, Jan 2026" style="border-radius: 10px;">{{ old('reason') }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="fw-semibold mb-2">Photo <span class="text-muted fw-normal small">(optional, JPG/PNG/WebP, max 2 MB)</span></label>
                            <input type="file" class="form-control" name="image" accept="image/jpeg,image/png,image/webp" style="border-radius: 10px;">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0 d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-soft-secondary px-4" data-bs-dismiss="modal" style="border-radius: 8px;">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4" style="background: #3858f9; border: none; border-radius: 8px;">Submit for Verification</button>
                </div>
            </form>
        </div>
    </div>
</div>
