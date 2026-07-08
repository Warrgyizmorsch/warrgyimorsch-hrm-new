@extends('layouts.app')

@section('content')
    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Candidates</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('vacancy.show') }}">Job Vacancy</a></li>
                <li class="breadcrumb-item">Candidates</li>
            </ul>
        </div>
        <div class="page-header-right ms-auto">
            <div class="page-header-right-items">
                <a href="{{ route('vacancy.show') }}" class="btn btn-light-brand">
                    <i class="feather-arrow-left me-2"></i>
                    <span>Back to Job Vacancy</span>
                </a>
            </div>
        </div>
    </div>

    <div class="main-content">
        <div class="row">
            <div class="col-lg-12">
                <div class="card stretch stretch-full">
                    <div class="card-body p-0">
                        <div class="px-4 py-3 border-bottom">
                            <form method="GET" class="row g-2 align-items-end">
                                <div class="col-md-4">
                                    <input type="text" name="search" class="form-control" placeholder="Search by name, email, phone..." value="{{ request('search') }}">
                                </div>
                                <div class="col-md-auto">
                                    <button type="submit" class="btn btn-primary">Search</button>
                                    <a href="{{ route('candidates.index') }}" class="btn btn-light">Reset</a>
                                </div>
                            </form>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Candidate</th>
                                        <th>Qualification</th>
                                        <th>Experience</th>
                                        <th>Applications</th>
                                        <th>Resume</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($candidates as $candidate)
                                        <tr>
                                            <td>
                                                <div class="fw-semibold">{{ $candidate->name }}</div>
                                                <div class="small text-muted"><i class="feather-mail me-1"></i>{{ $candidate->email }}</div>
                                                <div class="small text-muted"><i class="feather-phone me-1"></i>{{ $candidate->phone ?: '—' }}</div>
                                            </td>
                                            <td>{{ $candidate->qualification ?: '—' }}</td>
                                            <td>{{ $candidate->experience ?: '—' }}</td>
                                            <td>
                                                <span class="badge bg-info text-white">{{ $candidate->applications_count }}</span>
                                            </td>
                                            <td>
                                                @if($candidate->resume)
                                                    <a href="{{ asset('storage/' . $candidate->resume) }}" target="_blank"
                                                    class="btn btn-sm btn-outline-primary d-inline-flex align-items-center justify-content-center"
                                                    style="width:32px; height:32px; border-radius:8px;" title="View Resume">
                                                        <i class="feather-file-text" style="font-size:14px;"></i>
                                                    </a>
                                                @else
                                                    <span class="text-muted small">—</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <a href="{{ route('candidates.show', $candidate->id) }}" class="btn btn-sm btn-light-brand">
                                                    View Profile
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center py-5 text-muted">No candidates found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if($candidates->hasPages())
                            <div class="card-footer bg-white border-0 py-3 attendance-pagination">
                                {{ $candidates->appends(request()->query())->links('pagination::bootstrap-5') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
