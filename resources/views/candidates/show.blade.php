@extends('layouts.app')

@section('content')
    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">{{ $candidate->name }}</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('candidates.index') }}">Candidates</a></li>
                <li class="breadcrumb-item">{{ $candidate->name }}</li>
            </ul>
        </div>
        <div class="page-header-right ms-auto">
            <div class="page-header-right-items">
                <a href="{{ route('candidates.index') }}" class="btn btn-light-brand">
                    <i class="feather-arrow-left me-2"></i>
                    <span>Back to Candidates</span>
                </a>
            </div>
        </div>
    </div>

    <div class="main-content">
        <div class="row g-3">
            <div class="col-lg-4">
                <div class="card stretch stretch-full">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3 text-primary">Profile</h6>
                        <div class="mb-2"><i class="feather-mail me-2 text-muted"></i>{{ $candidate->email }}</div>
                        <div class="mb-2"><i class="feather-phone me-2 text-muted"></i>{{ $candidate->phone ?: '—' }}</div>
                        <div class="mb-2"><i class="feather-book me-2 text-muted"></i>{{ $candidate->qualification ?: '—' }}</div>
                        <div class="mb-2"><i class="feather-activity me-2 text-muted"></i>{{ $candidate->experience ?: '—' }} experience</div>
                        @if($candidate->resume)
                            <a href="{{ asset('storage/' . $candidate->resume) }}" target="_blank" class="btn btn-sm btn-outline-primary mt-2">
                                <i class="feather-file-text me-1"></i> View Resume
                            </a>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="card stretch stretch-full">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3 text-primary">Application History</h6>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Requirement / Role</th>
                                        <th>Department</th>
                                        <th>Interview</th>
                                        <th>Stage</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($candidate->applications as $app)
                                        <tr>
                                            <td>
                                                <div class="fw-semibold">{{ $app->designation }}</div>
                                                <div class="small text-muted">
                                                    @if($app->requirement)
                                                        Requirement — {{ \Carbon\Carbon::parse($app->requirement->date)->format('d M Y') }}
                                                    @else
                                                        Direct application
                                                    @endif
                                                </div>
                                            </td>
                                            <td>{{ $app->department->name ?? '—' }}</td>
                                            <td>
                                                <div class="small">{{ $app->interview_date ?? '—' }}</div>
                                                <div class="small text-muted">{{ $app->interviewer->name ?? '' }}</div>
                                            </td>
                                            <td>
                                                <span class="stage-badge stage-badge--{{ $app->status }}">{{ \App\Models\JobApplication::stageLabel($app->status) }}</span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center py-4 text-muted">No applications yet.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .stage-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .3px;
        }
        .stage-badge--applied { background: #eef2ff; color: #4338ca; }
        .stage-badge--shortlisted { background: #ecfeff; color: #0e7490; }
        .stage-badge--interview_scheduled { background: #fff7ed; color: #c2410c; }
        .stage-badge--interviewed { background: #fefce8; color: #a16207; }
        .stage-badge--offered { background: #f0fdf4; color: #15803d; }
        .stage-badge--hired { background: #dcfce7; color: #166534; }
        .stage-badge--rejected { background: #fef2f2; color: #b91c1c; }
    </style>
@endsection
