{{-- Two-level approval: where the request is, and the latest approver remark. --}}
@php
    $approvalRemark = $item->manager_remarks
        ?: ($item->tl_status === 'rejected' || $item->approval_stage === 'manager' ? $item->tl_remarks : null);
    $remarkBy = $item->manager_remarks ? 'Manager' : 'Team Leader';
@endphp
@if($item->stage_label)
    <div class="leave-approval-stage">{{ $item->stage_label }}</div>
@elseif($item->tl_status === 'rejected' && strtolower($item->status) === 'rejected' && !$item->manager_remarks)
    <div class="leave-approval-stage">Rejected by Team Leader</div>
@endif
@if($approvalRemark)
    <div class="leave-approval-remark" title="{{ $approvalRemark }}">
        <strong>{{ $remarkBy }}:</strong> {{ \Illuminate\Support\Str::limit($approvalRemark, 90) }}
    </div>
@endif
<div class="leave-approval-times">
    <span>Applied {{ $item->created_at?->format('d M Y, h:i A') }}</span>
    @if($item->tl_action_at)
        <span>· TL {{ $item->tl_status }} {{ $item->tl_action_at->format('d M, h:i A') }}</span>
    @endif
    @if($item->manager_action_at)
        <span>· Manager {{ str_replace('_', ' ', strtolower($item->status)) }} {{ $item->manager_action_at->format('d M, h:i A') }}</span>
    @endif
</div>
