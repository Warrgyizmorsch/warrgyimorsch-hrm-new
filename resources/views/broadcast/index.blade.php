@extends('layouts.app')

@section('content')
    <!-- [ page-header ] start -->
    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Broadcast</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                <li class="breadcrumb-item">Broadcast</li>
            </ul>
        </div>
        <div class="page-header-right ms-auto">
            <div class="page-header-right-items">
                <div class="d-flex d-md-none">
                    <a href="javascript:void(0)" class="page-header-right-close-toggle">
                        <i class="feather-arrow-left me-2"></i>
                        <span>Back</span>
                    </a>
                </div>
                <!-- <div class="d-flex align-items-center gap-2 page-header-right-items-wrapper">
                    <div id="bulk-action-wrapper" style="display: none;">
                        <a href="javascript:void(0);" id="btn-bulk-delete" class="btn btn-icon btn-soft-danger"
                            style="width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; 
                            justify-content: center;">
                            <i class="feather-trash-2 fs-18"></i>
                        </a>
                    </div>
                    <div class="filter-toggle-wrapper">
                        <a href="javascript:void(0);" class="btn btn-icon btn-light-brand" id="toggleFilter"
                            style="cursor: pointer;">
                            <i class="feather-filter"></i>
                        </a>
                    </div>
                    <a href="javascript:void(0)" class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#projectOffcanvas">
                        <i class="feather-plus me-2"></i>
                        <span>Add</span>
                    </a>
                </div> -->
            </div>
            <!-- <div class="d-md-none d-flex align-items-center">
                <a href="javascript:void(0)" class="page-header-right-open-toggle">
                    <i class="feather-align-right fs-20"></i>
                </a>
            </div> -->
        </div>
    </div>

    <div class="main-container">
        <div class="container-fluid py-4">
            {{-- Card Form for Create / Edit --}}
            <div class="card mb-4 shadow-sm border-0">
                <div class="card-header bg-primary text-white" style="font-size: large; font-weight:700">
                    {{ isset($broadcastToEdit) ? 'Edit Broadcast' : 'Broadcast' }}
                </div>
                <div class="card-body">
                    {{-- Form dynamically targets update or store route --}}
                    <form action="{{ isset($broadcastToEdit) ? route('broadcasts.update', $broadcastToEdit->id) : route('broadcasts.store') }}" method="POST">
                        @csrf
                        @if(isset($broadcastToEdit))
                            @method('PUT')
                        @endif

                        <div class="row">
                            {{-- Department Selection --}}
                            <div class="col-md-4 mb-3">
                                <label for="department" class="form-label text-secondary" style="font-size: 14px !important;">Department <span class="text-danger">*</span></label>
                                <select name="department" id="department" class="form-control" required>
                                    {{-- Keep "All" as a static fallback option --}}
                                    <option value="All" {{ (old('department', $broadcastToEdit->department ?? '') == 'All') ? 'selected' : '' }}>All</option>
                                    
                                    {{-- Loop dynamically through database departments --}}
                                    @foreach($departments as $dept)
                                        <option value="{{ $dept->name }}" {{ (old('department', $broadcastToEdit->department ?? '') == $dept->name) ? 'selected' : '' }}>
                                            {{ $dept->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('department') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>

                            {{-- Message Textarea --}}
                            <div class="col-md-8 mb-3">
                                <label for="message" class="form-label text-secondary" style="font-size: 14px !important;">message <span class="text-danger">*</span></label>
                                <textarea name="message" id="message" rows="3" class="form-control" placeholder="Enter Message" required>{{ old('message', $broadcastToEdit->message ?? '') }}</textarea>
                                @error('message') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                        </div>

                        <div class="row mt-2">
                            <div class="col-12 d-flex flex-column align-items-center">
                                <div style="width: 95%;">
                                    <button type="submit" class="btn btn-primary w-100 font-weight-bold py-2" style="font-size: 12px;">
                                        {{ isset($broadcastToEdit) ? 'Update' : 'Save' }}
                                    </button>

                                    @if(isset($broadcastToEdit))
                                        <a href="{{ route('broadcasts.index') }}" class="btn btn-link w-100 text-center mt-2 text-secondary" style="font-size: 12px; text-decoration: none;">
                                            Cancel Edit
                                        </a>
                                    @endif
                                    
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Data Table List Section --}}
            <div class="card shadow-sm border-0">
                <div class="card-header" style="font-size: 20px; font-weight: 500;">
                    Broadcast List
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="broadcastTable" class="table table-striped table-bordered text-secondary">
                            <thead class="bg-primary" style="height: 60px;">
                                <tr>
                                    <th style="width: 8%; font-size:14px !important" class="text-white">Sr.No.</th>
                                    <th style="width: 52%; font-size:14px !important" class="text-white">Message</th>
                                    <th style="width: 15%; font-size:14px !important" class="text-white">Department</th>
                                    <th style="width: 13%; font-size:14px !important" class="text-white">Date Time</th>
                                    <th style="width: 12%; font-size:14px !important" class="text-white">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($broadcasts as $index => $broadcast)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td style="width: 50%; white-space: normal; word-wrap: break-word; overflow-wrap: break-word;">{{ $broadcast->message }}</td>
                                    <td>
                                        {{ $broadcast->department }}
                                    </td>
                                    <td>
                                        <span class="text-muted small">
                                            <i class="far fa-clock m-1"></i>{{ $broadcast->created_at->diffForHumans() }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            {{-- Edit Button (Triggers standard GET request to reload and fill form) --}}
                                            <a href="{{ route('broadcasts.edit', $broadcast->id) }}" class="btn btn-primary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            {{-- View Button --}}
                                            <button type="button" class="btn btn-secondary btn-view-report" title="View" data-id="{{ $broadcast->id }}">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="receiptModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog" role="document">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white" style="height: 60px;">
                    <h5 class="modal-title font-weight-bold text-white" style="font-size:16px;">Read Receipts Report</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <div class="modal-body p-0 d-flex justify-content-center">
                    <table class="table mb-0 text-secondary mt-2" style="font-size: 14px; width:95%">
                        <thead class="bg-light" style="height:40px">
                            <tr>
                                <th class="border-top-0 px-4" style="width: 15%">#</th>
                                <th class="border-top-0" style="width: 45%">User</th>
                                <th class="border-top-0" style="width: 40%">Time</th>
                            </tr>
                        </thead>
                        <tbody id="receiptsTableBody"></tbody>
                    </table>
                </div>
                <div class="modal-footer border-top-0 pr-4">
                    <button type="button" class="btn btn-primary px-4 py-1 font-weight-bold" data-bs-dismiss="modal" style="font-size: 13px; height:40px; width:80px">Close</button>
                </div>
            </div>
        </div>
    </div>

@push('scripts')
    <script>
        let receiptModal = null;

        $(document).ready(function() {
            const receiptModalElement = document.getElementById('receiptModal');
            receiptModal = receiptModalElement && window.bootstrap
                ? bootstrap.Modal.getOrCreateInstance(receiptModalElement)
                : null;

            $('#broadcastTable').DataTable({
                "lengthMenu": [ [20, 50, 100, -1], [20, 50, 100, "All"] ], // Customized lengths
                "pageLength": 20, 
                "ordering": true,
                "language": {
                    "search": "Search:",
                    "lengthMenu": "Show _MENU_ entries"
                },
                // Callback function to conditionally hide "Previous" and "Next" controls
                "drawCallback": function(settings) {
                    var api = this.api();
                    var displayLength = api.page.len();
                    var totalRecords = api.rows({ search: 'applied' }).count();

                    // If selected pagination size is larger than filtered dataset count, hide pagination controls
                    if (displayLength >= totalRecords) {
                        $('.dataTables_paginate').hide();
                    } else {
                        $('.dataTables_paginate').show();
                    }
                }
            });

            // Simple script adjustment to match the layout's custom text-area heights
            $('#message').on('input', function () {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });
        });

        $(document).on('click', '.btn-view-report', function() {
            let id = $(this).data('id');
            $('#receiptsTableBody').html('<tr><td colspan="3" class="text-center py-3"><i class="fas fa-spinner fa-spin mr-2"></i>Loading...</td></tr>');

            if (!receiptModal) {
                const receiptModalElement = document.getElementById('receiptModal');
                receiptModal = receiptModalElement && window.bootstrap
                    ? bootstrap.Modal.getOrCreateInstance(receiptModalElement)
                    : null;
            }

            if (receiptModal) {
                receiptModal.show();
            }

            $.ajax({
                url: `{{ url('/broadcasts') }}/${id}/recipients`,
                method: 'GET',
                success: function(data) {
                    let rows = '';
                    if(data.length === 0) {
                        rows = '<tr><td colspan="3" class="text-center text-muted py-3">No recipients have read this announcement yet.</td></tr>';
                    } else {
                        data.forEach((user, index) => {
                            rows += `
                                <tr style="height: 60px; background-color: #ffffff !important;">
                                    <td class="px-4" style="vertical-align: middle; height: 60px; line-height: 44px;">${index + 1}</td>
                                    <td class="font-weight-bold text-dark" style="vertical-align: middle; height: 60px; line-height: 44px;">${user.name}</td>
                                    <td style="vertical-align: middle; height: 60px; line-height: 44px;">
                                        <small class="text-muted"><i class="far fa-clock me-1"></i>${user.time_ago}</small>
                                    </td>
                                </tr>`;
                        });
                    }
                    $('#receiptsTableBody').html(rows);
                },
                error: function() {
                    $('#receiptsTableBody').html('<tr><td colspan="3" class="text-center text-danger py-3">Unable to load read receipts right now.</td></tr>');
                }
            });
        });
    </script>
@endpush
@endsection
