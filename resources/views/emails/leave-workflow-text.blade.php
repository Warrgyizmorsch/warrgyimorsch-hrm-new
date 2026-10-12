{!! $m['brand'] !!} — Leave Management
[{!! $m['badge'] !!}]

{!! $m['greeting'] !!}

{!! $m['intro'] !!}

LEAVE DETAILS
@foreach($m['details'] as $label => $value)
- {!! $label !!}: {!! $value !!}
@endforeach
@if($m['reason'])

Reason: {!! $m['reason'] !!}
@endif
@if($m['message'])
Message from employee: {!! $m['message'] !!}
@endif
@if(!empty($m['trail']))

TIMELINE
@foreach($m['trail'] as $step)
- {!! $step['level'] !!}: {!! $step['status'] !!}{!! $step['by'] ? ' — ' . $step['by'] : '' !!}{!! $step['at'] ? ' (' . $step['at'] . ')' : '' !!}
@if($step['remarks'])
  Remarks: {!! $step['remarks'] !!}
@endif
@endforeach
@endif
@if(!empty($m['cta']))

{!! $m['cta']['label'] !!}: {!! $m['cta']['url'] !!}
@endif

This is an automated message from {!! $m['brand'] !!}. Please do not reply.
