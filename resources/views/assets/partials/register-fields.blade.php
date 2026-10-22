{{--
    Make / purchase / warranty inputs (Bootstrap grid columns).
    $asset  existing asset (or null), $name  fn(field) => input name, $idp  id prefix.
--}}
@php
    $val = fn ($field) => old($name($field), $asset?->{$field} instanceof \DateTimeInterface ? $asset->{$field}->format('Y-m-d') : $asset?->{$field});
@endphp
<div class="col-md-6">
    <label class="fw-semibold small mb-1" for="{{ $idp }}Brand">Brand</label>
    <input type="text" class="form-control" name="{{ $name('brand') }}" id="{{ $idp }}Brand" value="{{ $val('brand') }}" maxlength="100" placeholder="e.g. Dell">
</div>
<div class="col-md-6">
    <label class="fw-semibold small mb-1" for="{{ $idp }}Model">Model</label>
    <input type="text" class="form-control" name="{{ $name('model') }}" id="{{ $idp }}Model" value="{{ $val('model') }}" maxlength="100" placeholder="e.g. Latitude 5420">
</div>
<div class="col-md-6">
    <label class="fw-semibold small mb-1" for="{{ $idp }}Condition">Condition</label>
    <select class="form-select" name="{{ $name('condition') }}" id="{{ $idp }}Condition">
        @foreach(\App\Models\Asset::CONDITIONS as $c)
            <option value="{{ $c }}" @selected(($val('condition') ?: 'Good') === $c)>{{ $c }}</option>
        @endforeach
    </select>
</div>
<div class="col-md-6">
    <label class="fw-semibold small mb-1" for="{{ $idp }}Location">Location</label>
    <input type="text" class="form-control" name="{{ $name('location') }}" id="{{ $idp }}Location" value="{{ $val('location') }}" maxlength="150" placeholder="e.g. Udaipur office, 2nd floor">
</div>
<div class="col-md-4">
    <label class="fw-semibold small mb-1" for="{{ $idp }}PDate">Purchase date</label>
    <input type="date" class="form-control" name="{{ $name('purchase_date') }}" id="{{ $idp }}PDate" value="{{ $val('purchase_date') }}" max="{{ now()->toDateString() }}">
</div>
<div class="col-md-4">
    <label class="fw-semibold small mb-1" for="{{ $idp }}Cost">Purchase cost (₹)</label>
    <input type="number" step="0.01" min="0" class="form-control" name="{{ $name('purchase_cost') }}" id="{{ $idp }}Cost" value="{{ $val('purchase_cost') }}">
</div>
<div class="col-md-4">
    <label class="fw-semibold small mb-1" for="{{ $idp }}Warranty">Warranty until</label>
    <input type="date" class="form-control" name="{{ $name('warranty_expiry') }}" id="{{ $idp }}Warranty" value="{{ $val('warranty_expiry') }}">
</div>
<div class="col-md-6">
    <label class="fw-semibold small mb-1" for="{{ $idp }}Vendor">Vendor</label>
    <input type="text" class="form-control" name="{{ $name('vendor') }}" id="{{ $idp }}Vendor" value="{{ $val('vendor') }}" maxlength="150">
</div>
<div class="col-md-6">
    <label class="fw-semibold small mb-1" for="{{ $idp }}Invoice">Invoice no.</label>
    <input type="text" class="form-control" name="{{ $name('invoice_number') }}" id="{{ $idp }}Invoice" value="{{ $val('invoice_number') }}" maxlength="100">
</div>
