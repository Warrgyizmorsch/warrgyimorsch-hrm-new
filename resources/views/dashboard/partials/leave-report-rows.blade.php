@forelse($leaveReport as $emp)
    <tr>
        <td>
            <div class="d-flex align-items-center gap-3">
                <div class="avatar-text avatar-md bg-soft-primary text-primary">
                    {{ substr($emp->name, 0, 1) }}
                </div>
                <div>
                    <span class="d-block">{{ $emp->name }}</span>
                    <span class="fs-12 text-muted">{{ $emp->designation }}</span>
                </div>
            </div>
        </td>
        <td>
            <span class="badge bg-soft-danger text-danger">
                {{ $emp->leave_count }} Days
            </span>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="2" class="text-center text-muted py-4">
            No leave data found.
        </td>
    </tr>
@endforelse
