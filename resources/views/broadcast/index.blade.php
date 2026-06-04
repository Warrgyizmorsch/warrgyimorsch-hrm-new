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

    <style>
        #receiptModal .modal-content {
            border-radius: 8px;
            overflow: hidden;
        }

        #receiptModal .receipt-modal-header {
            background: #0d6efd;
            padding: 18px 24px;
        }

        #receiptModal .receipt-icon {
            align-items: center;
            background: rgba(255, 255, 255, 0.18);
            border-radius: 8px;
            display: inline-flex;
            height: 42px;
            justify-content: center;
            width: 42px;
        }

        #receiptModal .receipt-summary {
            background: #f8fafc;
            border-bottom: 1px solid #e9ecef;
            padding: 14px 24px;
        }

        #receiptModal .receipt-count {
            color: #0d6efd;
            font-size: 22px;
            font-weight: 700;
            line-height: 1;
        }

        #receiptModal .receipt-table-wrap {
            max-height: 420px;
            overflow-y: auto;
        }

        #receiptModal .receipt-table {
            font-size: 14px;
        }

        #receiptModal .receipt-table thead th {
            background: #ffffff;
            border-bottom: 1px solid #edf0f4;
            color: #6c757d;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0;
            padding: 14px 24px;
            position: sticky;
            text-transform: uppercase;
            top: 0;
            z-index: 1;
        }

        #receiptModal .receipt-table tbody td {
            border-color: #f0f2f5;
            padding: 16px 24px;
            vertical-align: middle;
        }

        #receiptModal .receipt-user-avatar {
            align-items: center;
            background: #eaf2ff;
            border-radius: 50%;
            color: #0d6efd;
            display: inline-flex;
            flex: 0 0 36px;
            font-size: 13px;
            font-weight: 700;
            height: 36px;
            justify-content: center;
            width: 36px;
        }

        #receiptModal .receipt-status {
            background: #eef8f1;
            border-radius: 8px;
            color: #198754;
            display: inline-flex;
            font-size: 12px;
            font-weight: 600;
            padding: 6px 10px;
        }
    </style>

    <div class="modal fade" id="receiptModal" tabindex="-1" aria-labelledby="receiptModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header receipt-modal-header text-white border-0">
                    <div class="d-flex align-items-center">
                        <span class="receipt-icon me-3">
                            <i class="fas fa-eye"></i>
                        </span>
                        <div>
                            <h5 class="modal-title font-weight-bold text-white mb-1" id="receiptModalLabel" style="font-size:16px;">Read Receipts Report</h5>
                            <small class="text-white-50">Employees who have viewed this broadcast</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="receipt-summary d-flex align-items-center justify-content-between">
                    <div>
                        <div class="receipt-count" id="receiptsCount">0</div>
                        <small class="text-muted">Total reads</small>
                    </div>
                    <span class="receipt-status">
                        <i class="fas fa-check-circle me-1"></i> Viewed
                    </span>
                </div>
                <div class="modal-body p-0">
                    <div class="receipt-table-wrap">
                        <table class="table receipt-table mb-0 text-secondary">
                            <thead>
                                <tr>
                                    <th style="width: 12%">#</th>
                                    <th style="width: 56%">User</th>
                                    <th style="width: 32%">Read Time</th>
                                </tr>
                            </thead>
                            <tbody id="receiptsTableBody"></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top px-4 py-3">
                    <button type="button" class="btn btn-primary px-4 font-weight-bold" data-bs-dismiss="modal" style="font-size: 13px;">Close</button>
                </div>
            </div>
        </div>
    </div>

@push('scripts')
    <script>
        let receiptModal = null;

        function escapeReceiptText(value) {
            return $('<div>').text(value || '').html();
        }

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
            $('#receiptsCount').text('0');
            $('#receiptsTableBody').html('<tr><td colspan="3" class="text-center text-muted py-5"><i class="fas fa-spinner fa-spin me-2"></i>Loading read receipts...</td></tr>');

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
                    $('#receiptsCount').text(data.length);

                    if(data.length === 0) {
                        rows = `
                            <tr>
                                <td colspan="3" class="text-center text-muted py-5">
                                    <i class="far fa-eye-slash d-block mb-2" style="font-size: 24px;"></i>
                                    No recipients have read this announcement yet.
                                </td>
                            </tr>`;
                    } else {
                        data.forEach((user, index) => {
                            const userName = escapeReceiptText(user.name);
                            const readTime = escapeReceiptText(user.time_ago);
                            const initials = user.name
                                ? escapeReceiptText(user.name.split(' ').map((part) => part.charAt(0)).join('').substring(0, 2).toUpperCase())
                                : 'U';

                            rows += `
                                <tr>
                                    <td class="font-weight-bold text-muted">${index + 1}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span class="receipt-user-avatar me-3">${initials}</span>
                                            <span class="font-weight-bold text-dark">${userName}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <small class="text-muted"><i class="far fa-clock me-1"></i>${readTime}</small>
                                    </td>
                                </tr>`;
                        });
                    }
                    $('#receiptsTableBody').html(rows);
                },
                error: function() {
                    $('#receiptsCount').text('0');
                    $('#receiptsTableBody').html('<tr><td colspan="3" class="text-center text-danger py-5"><i class="fas fa-exclamation-circle me-2"></i>Unable to load read receipts right now.</td></tr>');
                }
            });
        });
    </script>
@endpush
@endsection
