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
                <div class="d-flex align-items-center gap-2 page-header-right-items-wrapper">
                    <a href="javascript:void(0)" class="btn btn-primary" onclick="openBroadcastOffcanvas()">
                        <i class="feather-plus me-2"></i>
                        <span>New Broadcast</span>
                    </a>
                </div>
            </div>
            <div class="d-md-none d-flex align-items-center">
                <a href="javascript:void(0)" class="page-header-right-open-toggle">
                    <i class="feather-align-right fs-20"></i>
                </a>
            </div>
        </div>
    </div>
    <!-- [ page-header ] end -->

    <!-- [ stat cards ] start -->
    <div class="page-header-collapse">
        <div class="accordion-body pb-2">
            <div class="row g-3">
                <div class="col-xxl col-md-6">
                    <div class="card stretch stretch-full border-start border-4 border-primary">
                        <div class="card-body p-3">
                            <div class="hstack justify-content-between">
                                <div>
                                    <span class="fs-10 fw-bold text-uppercase d-block mb-1">Total Broadcasts</span>
                                    <span class="fs-20 fw-bolder d-block">{{ $totalBroadcasts }}</span>
                                </div>
                                <div class="avatar-text avatar-md bg-soft-primary text-primary">
                                    <i class="feather-radio"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xxl col-md-6">
                    <div class="card stretch stretch-full border-start border-4 border-info">
                        <div class="card-body p-3">
                            <div class="hstack justify-content-between">
                                <div>
                                    <span class="fs-10 fw-bold text-uppercase d-block mb-1">Sent This Month</span>
                                    <span class="fs-20 fw-bolder d-block">{{ $sentThisMonth }}</span>
                                </div>
                                <div class="avatar-text avatar-md bg-soft-info text-info">
                                    <i class="feather-calendar"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xxl col-md-6">
                    <div class="card stretch stretch-full border-start border-4 border-success">
                        <div class="card-body p-3">
                            <div class="hstack justify-content-between">
                                <div>
                                    <span class="fs-10 fw-bold text-uppercase d-block mb-1">Total Reads</span>
                                    <span class="fs-20 fw-bolder d-block">{{ $totalReads }}</span>
                                </div>
                                <div class="avatar-text avatar-md bg-soft-success text-success">
                                    <i class="feather-eye"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xxl col-md-6">
                    <div class="card stretch stretch-full border-start border-4 border-warning">
                        <div class="card-body p-3">
                            <div class="hstack justify-content-between">
                                <div>
                                    <span class="fs-10 fw-bold text-uppercase d-block mb-1">Avg Reads / Broadcast</span>
                                    <span class="fs-20 fw-bolder d-block">{{ $avgReads }}</span>
                                </div>
                                <div class="avatar-text avatar-md bg-soft-warning text-warning">
                                    <i class="feather-trending-up"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- [ stat cards ] end -->

    <div class="main-content">
        <div class="row">
            <div class="col-lg-12">
                <div class="card stretch stretch-full">
                    <div class="card-body p-0">
                        <div class="px-4 py-3 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2">
                            <form method="GET" class="d-flex align-items-center gap-2">
                                <div class="zoho-people-table-search" style="min-width: 260px;">
                                    <i class="feather-search"></i>
                                    <input type="text" name="search" placeholder="Search broadcasts…" value="{{ request('search') }}" autocomplete="off">
                                </div>
                                @if(request('per_page'))
                                    <input type="hidden" name="per_page" value="{{ request('per_page') }}">
                                @endif
                                <button type="submit" class="btn btn-light-brand btn-sm">Search</button>
                                @if(request('search'))
                                    <a href="{{ route('broadcasts.index') }}" class="btn btn-sm btn-light">Reset</a>
                                @endif
                            </form>
                            <div class="d-flex align-items-center gap-2">
                                <span class="text-muted small fw-bold text-uppercase">Show</span>
                                <div class="dropdown">
                                    <button class="wghrm-custom-select-btn dropdown-toggle" type="button"
                                        data-bs-toggle="dropdown" id="showEntriesBtn"
                                        style="width: 80px; height: 44px; padding: 0 15px;">
                                        {{ $perPage ?? 20 }}
                                    </button>
                                    <div class="dropdown-menu wghrm-custom-dropdown-menu shadow-lg border-0" style="min-width: 80px; border-radius: 12px;">
                                        <a class="dropdown-item wghrm-custom-dropdown-item {{ ($perPage ?? 20) == 20 ? 'active' : '' }}" href="{{ request()->fullUrlWithQuery(['per_page' => 20, 'page' => 1]) }}">20</a>
                                        <a class="dropdown-item wghrm-custom-dropdown-item {{ ($perPage ?? 50) == 50 ? 'active' : '' }}" href="{{ request()->fullUrlWithQuery(['per_page' => 50, 'page' => 1]) }}">50</a>
                                        <a class="dropdown-item wghrm-custom-dropdown-item {{ ($perPage ?? 100) == 100 ? 'active' : '' }}" href="{{ request()->fullUrlWithQuery(['per_page' => 100, 'page' => 1]) }}">100</a>
                                    </div>
                                </div>
                                <span class="text-muted small fw-bold text-uppercase">entries</span>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 4%;">#</th>
                                        <th style="width: 46%;">Message</th>
                                        <th style="width: 13%;">Department</th>
                                        <th style="width: 13%;">Sent</th>
                                        <th style="width: 12%;">Reach</th>
                                        <th class="text-end" style="width: 12%;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($broadcasts as $index => $broadcast)
                                    <tr>
                                        <td>{{ $broadcasts->firstItem() + $index }}</td>
                                        <td>
                                            <div class="bc-message-wrap">
                                                <div class="bc-message bc-message--clamped">{{ $broadcast->message }}</div>
                                                <a href="javascript:void(0)" class="bc-message-toggle small text-primary d-none">Show more</a>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge {{ $broadcast->department === 'All' ? 'bg-primary' : 'bg-light text-dark border' }}">
                                                {{ $broadcast->department === 'All' ? 'All Employees' : $broadcast->department }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="small text-muted" title="{{ $broadcast->created_at->format('d M Y, h:i A') }}">
                                                <i class="feather-clock me-1"></i>{{ $broadcast->created_at->diffForHumans() }}
                                            </span>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-soft-success d-inline-flex align-items-center gap-1 btn-view-report" title="View read receipts" data-id="{{ $broadcast->id }}">
                                                <i class="feather-eye"></i> {{ $broadcast->read_by_users_count }}
                                            </button>
                                        </td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-sm btn-icon btn-light-brand" title="Edit"
                                                onclick="openBroadcastOffcanvas({{ $broadcast->id }}, '{{ addslashes($broadcast->department) }}', `{{ addslashes($broadcast->message) }}`)">
                                                <i class="feather-edit-2"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-5">
                                            <div class="text-muted">
                                                <i class="feather-radio d-block mb-2" style="font-size: 28px;"></i>
                                                No broadcasts yet. Click "New Broadcast" to announce something to your team.
                                            </div>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if($broadcasts->hasPages())
                            <div class="card-footer bg-white border-0 py-3 attendance-pagination">
                                {{ $broadcasts->appends(request()->query())->links('pagination::bootstrap-5') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Add / Edit Broadcast offcanvas --}}
    <div class="offcanvas offcanvas-end" tabindex="-1" id="broadcastOffcanvas" style="width:520px;">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title d-flex align-items-center gap-2">
                <i class="feather-radio text-primary"></i>
                <span id="broadcastOffcanvasTitle">New Broadcast</span>
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body">
            <form id="broadcastForm" action="{{ route('broadcasts.store') }}" method="POST">
                @csrf
                <input type="hidden" name="_method" id="broadcastFormMethod" value="">

                <div class="mb-3">
                    <label class="form-label">Department <span class="text-danger">*</span></label>
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary w-100 dropdown-toggle text-start d-flex align-items-center justify-content-between"
                                type="button" id="bcDeptBtn" data-bs-toggle="dropdown" style="height: 44px; border-radius: 12px; border: 1px solid #dcdcdc; background: #fff; color: #4b5563;">
                            <span>All Employees</span>
                        </button>
                        <input type="hidden" name="department" id="bcDeptInput" value="All" required>

                        <div class="dropdown-menu wghrm-custom-dropdown-menu w-100">
                            <div class="wghrm-items-container">
                                <a class="dropdown-item wghrm-custom-dropdown-item active" href="javascript:void(0);" onclick="setBcDept('All', 'All Employees', this)">All Employees</a>
                                @foreach($departments as $dept)
                                    <a class="dropdown-item wghrm-custom-dropdown-item" href="javascript:void(0);" onclick="setBcDept('{{ addslashes($dept->name) }}', '{{ addslashes($dept->name) }}', this)">{{ $dept->name }}</a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @error('department') <small class="text-danger">{{ $message }}</small> @enderror
                </div>

                <div class="mb-2">
                    <label class="form-label d-flex justify-content-between">
                        <span>Message <span class="text-danger">*</span></span>
                        <span class="small text-muted"><span id="bcMessageCount">0</span>/5000</span>
                    </label>
                    <textarea name="message" id="bcMessage" rows="6" class="form-control" placeholder="Write your announcement…" maxlength="5000" required style="height: 160px; min-height: 160px; resize: vertical;"></textarea>
                    @error('message') <small class="text-danger">{{ $message }}</small> @enderror
                </div>

                <button type="submit" class="btn btn-primary w-100 mt-3">
                    <i class="feather-send me-2"></i>
                    <span id="bcSubmitLabel">Post Broadcast</span>
                </button>
            </form>
        </div>
    </div>

    {{-- Read Receipts modal --}}
    <div class="modal fade" id="receiptModal" tabindex="-1" aria-labelledby="receiptModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header receipt-modal-header text-white border-0">
                    <div class="d-flex align-items-center">
                        <span class="receipt-icon me-3">
                            <i class="feather-eye"></i>
                        </span>
                        <div>
                            <h5 class="modal-title fw-bold text-white mb-1" id="receiptModalLabel" style="font-size:16px;">Read Receipts</h5>
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
                        <i class="feather-check-circle me-1"></i> Viewed
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
                    <button type="button" class="btn btn-primary px-4 fw-bold" data-bs-dismiss="modal" style="font-size: 13px;">Close</button>
                </div>
            </div>
        </div>
    </div>

    <style>
        .wghrm-custom-select-btn {
            background-color: #fff !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 12px !important;
            color: #1e293b !important;
            padding: 10px 16px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            width: 100% !important;
            height: 48px !important;
            font-size: 14px !important;
            font-weight: 600 !important;
            transition: all 0.2s !important;
            text-align: left !important;
        }
        .wghrm-custom-dropdown-menu {
            border-radius: 12px !important;
            padding: 4px !important;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05) !important;
            border: 1px solid #e4e6ef !important;
            min-width: 220px;
        }
        .wghrm-items-container {
            max-height: 240px;
            overflow-y: auto;
        }
        .wghrm-custom-dropdown-item {
            border-radius: 8px !important;
            padding: 8px 12px !important;
            font-size: 14px;
            color: #4b5563 !important;
            margin-bottom: 2px;
        }
        .wghrm-custom-dropdown-item.active, .wghrm-custom-dropdown-item:hover {
            background-color: #f1f3f9 !important;
            color: #4e73df !important;
        }

        .bc-message {
            white-space: pre-line;
            line-height: 1.5;
        }
        .bc-message--clamped {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .bc-message-toggle {
            cursor: pointer;
        }

        .zoho-people-table-search {
            position: relative;
            display: flex;
            align-items: center;
        }
        .zoho-people-table-search i {
            position: absolute;
            left: 12px;
            color: #94a3b8;
            font-size: 14px;
        }
        .zoho-people-table-search input {
            width: 100%;
            height: 40px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 0 12px 0 34px;
            font-size: 14px;
            outline: none;
        }
        .zoho-people-table-search input:focus {
            border-color: #3858f9;
        }

        #receiptModal .modal-content { border-radius: 8px; overflow: hidden; }
        #receiptModal .receipt-modal-header { background: #0d6efd; padding: 18px 24px; }
        #receiptModal .receipt-icon {
            align-items: center; background: rgba(255, 255, 255, 0.18); border-radius: 8px;
            display: inline-flex; height: 42px; justify-content: center; width: 42px;
        }
        #receiptModal .receipt-summary { background: #f8fafc; border-bottom: 1px solid #e9ecef; padding: 14px 24px; }
        #receiptModal .receipt-count { color: #0d6efd; font-size: 22px; font-weight: 700; line-height: 1; }
        #receiptModal .receipt-table-wrap { max-height: 420px; overflow-y: auto; }
        #receiptModal .receipt-table { font-size: 14px; }
        #receiptModal .receipt-table thead th {
            background: #ffffff; border-bottom: 1px solid #edf0f4; color: #6c757d; font-size: 12px;
            font-weight: 700; padding: 14px 24px; position: sticky; text-transform: uppercase; top: 0; z-index: 1;
        }
        #receiptModal .receipt-table tbody td { border-color: #f0f2f5; padding: 16px 24px; vertical-align: middle; }
        #receiptModal .receipt-user-avatar {
            align-items: center; background: #eaf2ff; border-radius: 50%; color: #0d6efd; display: inline-flex;
            flex: 0 0 36px; font-size: 13px; font-weight: 700; height: 36px; justify-content: center; width: 36px;
        }
        #receiptModal .receipt-status {
            background: #eef8f1; border-radius: 8px; color: #198754; display: inline-flex;
            font-size: 12px; font-weight: 600; padding: 6px 10px;
        }
    </style>

@push('scripts')
    <script>
        let receiptModal = null;
        let broadcastOffcanvas = null;

        function escapeReceiptText(value) {
            return $('<div>').text(value || '').html();
        }

        function setBcDept(value, label, element) {
            document.getElementById('bcDeptInput').value = value;
            document.getElementById('bcDeptBtn').querySelector('span').innerText = label;
            const container = element.closest('.wghrm-items-container');
            container.querySelectorAll('.wghrm-custom-dropdown-item').forEach(el => el.classList.remove('active'));
            element.classList.add('active');
        }

        function updateBcMessageCount() {
            document.getElementById('bcMessageCount').innerText = document.getElementById('bcMessage').value.length;
        }

        function openBroadcastOffcanvas(id, department, message) {
            const form = document.getElementById('broadcastForm');
            form.reset();
            document.getElementById('broadcastFormMethod').value = '';

            if (id) {
                form.action = `/broadcasts/${id}`;
                document.getElementById('broadcastFormMethod').value = 'PUT';
                document.getElementById('broadcastOffcanvasTitle').innerText = 'Edit Broadcast';
                document.getElementById('bcSubmitLabel').innerText = 'Update Broadcast';
                document.getElementById('bcMessage').value = message || '';
                setBcDept(department, department === 'All' ? 'All Employees' : department, document.querySelector('#bcDeptBtn').closest('.dropdown').querySelector('.wghrm-custom-dropdown-item'));
            } else {
                form.action = '{{ route('broadcasts.store') }}';
                document.getElementById('broadcastOffcanvasTitle').innerText = 'New Broadcast';
                document.getElementById('bcSubmitLabel').innerText = 'Post Broadcast';
                setBcDept('All', 'All Employees', document.querySelector('#bcDeptBtn').closest('.dropdown').querySelector('.wghrm-custom-dropdown-item'));
            }
            updateBcMessageCount();

            if (!broadcastOffcanvas) {
                broadcastOffcanvas = bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('broadcastOffcanvas'));
            }
            broadcastOffcanvas.show();
        }

        $(document).ready(function () {
            const receiptModalElement = document.getElementById('receiptModal');
            receiptModal = receiptModalElement && window.bootstrap
                ? bootstrap.Modal.getOrCreateInstance(receiptModalElement)
                : null;

            document.getElementById('bcMessage').addEventListener('input', updateBcMessageCount);

            // Expand/collapse long messages
            document.querySelectorAll('.bc-message-wrap').forEach(function (wrap) {
                const msg = wrap.querySelector('.bc-message');
                const toggle = wrap.querySelector('.bc-message-toggle');
                if (msg.scrollHeight > msg.clientHeight + 2) {
                    toggle.classList.remove('d-none');
                    toggle.addEventListener('click', function () {
                        const collapsed = msg.classList.toggle('bc-message--clamped');
                        toggle.innerText = collapsed ? 'Show more' : 'Show less';
                    });
                }
            });
        });

        $(document).on('click', '.btn-view-report', function() {
            let id = $(this).data('id');
            $('#receiptsCount').text('0');
            $('#receiptsTableBody').html('<tr><td colspan="3" class="text-center text-muted py-5"><i class="feather-loader me-2"></i>Loading read receipts...</td></tr>');

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
                                    <i class="feather-eye-off d-block mb-2" style="font-size: 24px;"></i>
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
                                    <td class="fw-bold text-muted">${index + 1}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span class="receipt-user-avatar me-3">${initials}</span>
                                            <span class="fw-bold text-dark">${userName}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <small class="text-muted"><i class="feather-clock me-1"></i>${readTime}</small>
                                    </td>
                                </tr>`;
                        });
                    }
                    $('#receiptsTableBody').html(rows);
                },
                error: function() {
                    $('#receiptsCount').text('0');
                    $('#receiptsTableBody').html('<tr><td colspan="3" class="text-center text-danger py-5"><i class="feather-alert-circle me-2"></i>Unable to load read receipts right now.</td></tr>');
                }
            });
        });
    </script>
@endpush
@endsection
