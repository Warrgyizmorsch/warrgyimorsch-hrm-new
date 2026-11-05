@extends('layouts.app')

@section('content')
    @php
        $monthLabel = \Carbon\Carbon::createFromFormat('Y-m', $import['month'])->format('F Y');
        // Rows needing attention first; the line number still points back to the file.
        $rows = collect($review['rows'])
            ->sortBy(fn ($row) => [$row['errors'] ? 0 : ($row['warnings'] ? 1 : 2), $row['line']])
            ->values();
        $actionLabels = ['new' => 'New', 'replace' => 'Replace', 'skip' => 'Skip'];
        $money = fn ($value) => is_numeric($value) ? number_format((float) $value, 2) : ($value ?? '');
    @endphp

    <div class="zoho-page-shell">
        @include('layouts.partials.zoho-people-list-header', [
            'title' => 'Review Payroll Import',
            'viewLabel' => $monthLabel,
            'scopeLinks' => [
                ['label' => 'Home', 'url' => route('dashboard'), 'active' => false],
                ['label' => 'History', 'url' => route('payroll.index'), 'active' => false],
            ],
            'primaryAction' => '',
        ])

        <div class="main-content zoho-module-content">
            <p class="text-muted mb-3">
                <strong>{{ $import['file_name'] }}</strong> for <strong>{{ $monthLabel }}</strong>.
                Nothing has been saved yet.
            </p>

            <div class="row g-3 mb-3">
                <div class="col-6 col-md-3">
                    <div class="border rounded p-3 h-100">
                        <div class="small text-muted">Rows to save</div>
                        <div class="fs-4 fw-bold">{{ $review['save_count'] }}</div>
                        @if($review['replace_count'])
                            <div class="small text-muted">{{ $review['replace_count'] }} replace pending payroll</div>
                        @endif
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="border rounded p-3 h-100">
                        <div class="small text-muted">Rows with errors</div>
                        <div class="fs-4 fw-bold {{ $review['error_count'] ? 'text-danger' : '' }}">{{ $review['error_count'] }}</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="border rounded p-3 h-100">
                        <div class="small text-muted">Total gross</div>
                        <div class="fs-4 fw-bold">₹{{ number_format($review['gross_total'], 2) }}</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="border rounded p-3 h-100">
                        <div class="small text-muted">Total net</div>
                        <div class="fs-4 fw-bold">₹{{ number_format($review['net_total'], 2) }}</div>
                    </div>
                </div>
            </div>

            @if($review['error_count'])
                <div class="alert alert-danger">
                    Fix {{ $review['error_count'] }} {{ Str::plural('row', $review['error_count']) }} in your file and upload it again.
                    Nothing can be saved until every error is fixed.
                </div>
            @elseif($review['save_count'] === 0)
                <div class="alert alert-warning">This file has no payroll rows to save.</div>
            @endif

            <div class="d-flex flex-wrap gap-2 mb-3">
                @if(!$review['error_count'] && $review['save_count'] > 0)
                    <form action="{{ route('payroll.import.confirm') }}" method="POST">
                        @csrf
                        <input type="hidden" name="token" value="{{ $import['token'] }}">
                        <button type="submit" class="zoho-btn-primary">
                            Save {{ $review['save_count'] }} payroll {{ Str::plural('record', $review['save_count']) }}
                        </button>
                    </form>
                @endif
                <form action="{{ route('payroll.import.cancel') }}" method="POST">
                    @csrf
                    <button type="submit" class="zoho-btn-outline">Cancel import</button>
                </form>
            </div>

            <div class="table-responsive border rounded">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Line</th>
                            <th>Code</th>
                            <th>Name in file</th>
                            <th>Matched employee</th>
                            <th class="text-end">Working</th>
                            <th class="text-end">Payable</th>
                            <th class="text-end">Salary</th>
                            <th class="text-end">Gross</th>
                            <th class="text-end">Additional</th>
                            <th class="text-end">PF</th>
                            <th class="text-end">ESIC</th>
                            <th class="text-end">Late</th>
                            <th class="text-end">Net</th>
                            <th>Action</th>
                            <th style="min-width: 260px;">Issues</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $row)
                            @php $values = $row['values']; @endphp
                            <tr class="{{ $row['errors'] ? 'table-danger' : ($row['warnings'] ? 'table-warning' : '') }}">
                                <td>{{ $row['line'] }}</td>
                                <td>{{ $values['employee_code'] }}</td>
                                <td>{{ $values['employee_name'] }}</td>
                                <td>{{ $row['employee_name'] ?? '—' }}</td>
                                <td class="text-end">{{ $values['working_days'] }}</td>
                                <td class="text-end">{{ $values['payable_days'] }}</td>
                                <td class="text-end">{{ $money($values['monthly_salary']) }}</td>
                                <td class="text-end">{{ $money($values['gross_paid']) }}</td>
                                <td class="text-end">{{ $money($values['additional']) }}</td>
                                <td class="text-end">{{ $money($values['pf']) }}</td>
                                <td class="text-end">{{ $money($values['esic']) }}</td>
                                <td class="text-end">{{ $money($values['late_deduction']) }}</td>
                                <td class="text-end fw-bold">{{ $money($values['net_salary']) }}</td>
                                <td>
                                    {{ $actionLabels[$row['action']] }}
                                    @if($row['action'] === 'replace' && $row['existing_net'] !== null)
                                        <div class="small text-muted">was ₹{{ number_format($row['existing_net'], 2) }}</div>
                                    @endif
                                </td>
                                <td class="small">
                                    @foreach($row['errors'] as $message)
                                        <div class="text-danger fw-semibold">{{ $message }}</div>
                                    @endforeach
                                    @foreach($row['warnings'] as $message)
                                        <div>{{ $message }}</div>
                                    @endforeach
                                </td>
                            </tr>
                        @endforeach
                        @if($rows->isEmpty())
                            <tr>
                                <td colspan="15" class="text-center text-muted py-4">No rows found below the header.</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
