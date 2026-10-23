{{-- Cost + recovery inputs. $prefix makes ids unique per dialog; $withDate adds "recovered on". --}}
<div class="row g-3 mb-3">
    <div class="col-sm-6">
        <label class="fw-semibold small mb-1" for="{{ $prefix }}Cost">Cost to company (₹)</label>
        <input type="number" step="0.01" min="0" class="form-control" name="cost" id="{{ $prefix }}Cost" placeholder="Repair / replacement">
    </div>
    <div class="col-sm-6">
        <label class="fw-semibold small mb-1" for="{{ $prefix }}Status">Recover from employee</label>
        <select class="form-select" name="recovery_status" id="{{ $prefix }}Status" onchange="amToggleRecovery('{{ $prefix }}')">
            @foreach(\App\Models\AssetIncident::RECOVERY_STATUSES as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>
</div>
<div class="row g-3 mb-3 d-none" id="{{ $prefix }}Box">
    <div class="col-sm-{{ !empty($withDate) ? 4 : 6 }}">
        <label class="fw-semibold small mb-1" for="{{ $prefix }}Amount">Amount to recover (₹)</label>
        <input type="number" step="0.01" min="0" class="form-control" name="recovery_amount" id="{{ $prefix }}Amount">
    </div>
    <div class="col-sm-{{ !empty($withDate) ? 4 : 6 }}">
        <label class="fw-semibold small mb-1" for="{{ $prefix }}Recovered">Recovered so far (₹)</label>
        <input type="number" step="0.01" min="0" class="form-control" name="recovered_amount" id="{{ $prefix }}Recovered">
    </div>
    @if(!empty($withDate))
    <div class="col-sm-4">
        <label class="fw-semibold small mb-1" for="{{ $prefix }}Date">Recovered on</label>
        <input type="date" class="form-control" name="recovered_at" id="{{ $prefix }}Date" max="{{ now()->toDateString() }}">
    </div>
    @endif
</div>
