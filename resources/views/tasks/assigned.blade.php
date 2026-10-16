@extends('layouts.app')

@section('title', 'My Tasks')

@section('content')
@php
    $statusOptions = ['Pending', 'In Process', 'Review', 'On Hold', 'Rework', 'Completed'];
    $viewIcons = ['to' => 'feather-inbox', 'by' => 'feather-send', 'notes' => 'feather-edit-3'];
    $subtitles = [
        'to' => 'Tasks other people gave you, grouped by who assigned them',
        'by' => 'Tasks you gave others, grouped by person',
        'notes' => 'Your personal reminders and meeting notes',
    ];
@endphp

<div class="at-page">
    <header class="at-head">
        <div>
            <h1 class="at-title">My Tasks</h1>
            <p class="at-sub">{{ $subtitles[$view] }}</p>
        </div>
        <button type="button" class="at-add" data-bs-toggle="modal" data-bs-target="#qnAddModal" data-allow-repeat title="Add a note or assign a task" aria-label="Add a note or assign a task">
            <i class="feather-plus"></i>
        </button>
    </header>

    {{-- Main switch: to me / by me / notes --}}
    <nav class="at-views" aria-label="Task views">
        @foreach($tabs as $key => [$label, $count])
            <a href="{{ route('tasks.assigned', ['view' => $key]) }}" class="at-view {{ $view === $key ? 'is-active' : '' }}">
                <i class="{{ $viewIcons[$key] }}"></i>
                <span>{{ $label }}</span>
                <span class="at-view-count">{{ $count }} {{ $key === 'notes' ? 'active' : 'open' }}</span>
            </a>
        @endforeach
    </nav>

    <div id="qnAssignFeedback" class="at-feedback d-none"></div>

@if($view === 'notes')
    @forelse($notes as $note)
        <div class="at-note qn-item {{ $note->is_completed ? 'qn-item--done' : '' }}" data-note-id="{{ $note->id }}" @if($note->remind_at) data-remind-iso="{{ $note->remind_at->toIso8601String() }}" @endif>
            <input type="checkbox" class="qn-check" {{ $note->is_completed ? 'checked' : '' }} onchange="qnToggle({{ $note->id }}, this)" aria-label="Mark done">
            <div class="at-note-body">
                <div class="at-note-title qn-item-title">{{ $note->title }}</div>
                <div class="at-note-meta">
                    <span class="at-note-type at-note-type--{{ $note->type }}">{{ $note->type }}</span>
                    @if($note->remind_at)<span><i class="feather-clock"></i> {{ $note->remind_at->format('d M, h:i A') }}</span>@endif
                    <span>Added {{ $note->created_at->format('d M') }}</span>
                </div>
            </div>
            <button type="button" class="at-note-del" data-allow-repeat onclick="qnDelete({{ $note->id }}, this)" title="Delete note" aria-label="Delete note"><i class="feather-trash-2"></i></button>
        </div>
    @empty
        <div class="at-empty">
            <i class="feather-edit-3"></i>
            <p>No notes yet. Tap + to add a reminder or a meeting.</p>
        </div>
    @endforelse
@else
    @php
        $filterTabs = [
            'open' => ['Open', $summary['open']],
            'overdue' => ['Overdue', $summary['overdue']],
            'completed' => ['Done', $summary['completed']],
            'all' => ['All', $summary['open'] + $summary['completed']],
        ];
        $openByDefault = $groups->count() <= 4;
    @endphp

    <section class="at-stats" aria-label="Summary">
        <div class="at-stat"><span class="at-stat-num">{{ $summary['open'] }}</span><span class="at-stat-label">Open</span></div>
        <div class="at-stat at-stat--alert"><span class="at-stat-num">{{ $summary['overdue'] }}</span><span class="at-stat-label">Overdue</span></div>
        <div class="at-stat at-stat--ok"><span class="at-stat-num">{{ $summary['completed'] }}</span><span class="at-stat-label">Done</span></div>
        <div class="at-stat"><span class="at-stat-num">{{ $summary['people'] }}</span><span class="at-stat-label">People</span></div>
    </section>

    <nav class="at-tabs" aria-label="Filter tasks">
        @foreach($filterTabs as $key => [$label, $count])
            <a href="{{ route('tasks.assigned', ['view' => $view, 'show' => $key]) }}" class="at-tab {{ $filter === $key ? 'is-active' : '' }} {{ $key === 'overdue' && $count ? 'at-tab--alert' : '' }}">
                {{ $label }} <span class="at-tab-count">{{ $count }}</span>
            </a>
        @endforeach
    </nav>

    <div class="at-search">
        <i class="feather-search"></i>
        <input type="search" id="atSearch" placeholder="Search person or task…" autocomplete="off" aria-label="Search person or task">
    </div>

    <div id="atGroups">
        @forelse($groups as $group)
            @php
                $emp = $group['employee'];
                $name = $group['name'];
                $pct = $group['total'] ? round($group['done'] / $group['total'] * 100) : 0;
            @endphp
            <details class="at-person" data-search="{{ strtolower($name . ' ' . $group['tasks']->pluck('task_title')->implode(' ')) }}" @if($openByDefault || $group['overdue']) open @endif>
                <summary class="at-person-head">
                    <span class="at-avatar-wrap">
                        <span class="at-avatar at-avatar--initial">{{ strtoupper(mb_substr($name, 0, 1)) }}</span>
                        @if($emp && $emp->photo)
                            {{-- Falls back to the initial if the photo file is missing. --}}
                            <img src="{{ asset('storage/' . $emp->photo) }}" alt="" loading="lazy" onerror="this.remove()">
                        @endif
                    </span>
                    <span class="at-person-info">
                        <span class="at-role">{{ $view === 'to' ? 'Assigned by' : 'Assigned to' }}</span>
                        <span class="at-person-name">{{ $name }}</span>
                        <span class="at-person-meta">
                            {{ $emp->departmentRef->name ?? '' }}
                            @if($group['open']) <span class="at-dot"></span>{{ $group['open'] }} open @endif
                            @if($group['overdue']) <span class="at-dot"></span><b class="at-red">{{ $group['overdue'] }} overdue</b> @endif
                        </span>
                        <span class="at-progress" title="{{ $group['done'] }} of {{ $group['total'] }} done"><span style="width: {{ $pct }}%"></span></span>
                    </span>
                    <span class="at-count">{{ $group['tasks']->count() }}</span>
                    <i class="feather-chevron-down at-chevron"></i>
                </summary>

                <ul class="at-tasks">
                    @foreach($group['tasks'] as $task)
                        @php
                            $done = $task->status === 'Completed';
                            $overdue = !$done && $task->end_date && $task->end_date->lt($today);
                            $daysLate = $overdue ? (int) $task->end_date->diffInDays($today) : 0;
                            $last = $task->latestStatusHistory;
                        @endphp
                        <li class="at-task {{ $overdue ? 'is-overdue' : '' }} {{ $done ? 'is-done' : '' }}">
                            <div class="at-task-top">
                                <span class="at-prio at-prio--{{ strtolower($task->priority ?? 'normal') }}" title="Priority: {{ $task->priority ?? 'Normal' }}"></span>
                                <span class="at-task-title">{{ $task->task_title }}</span>
                            </div>
                            <div class="at-task-meta">
                                <span class="at-status at-status--{{ \Illuminate\Support\Str::slug($task->status) }}">{{ $task->status }}</span>
                                @if($task->priority)<span>{{ $task->priority }}</span>@endif
                                @if($overdue)
                                    <span class="at-due at-due--late"><i class="feather-alert-circle"></i> {{ $daysLate === 0 ? 'Due today' : $daysLate . 'd overdue' }}</span>
                                @elseif($task->end_date && !$done)
                                    <span class="at-due"><i class="feather-calendar"></i> Due {{ $task->end_date->isToday() ? 'today' : $task->end_date->format('d M') }}</span>
                                @endif
                                @if($task->project)
                                    <span class="at-proj"><i class="feather-folder"></i> {{ \Illuminate\Support\Str::limit($task->project->name, 22) }}</span>
                                @endif
                            </div>
                            <div class="at-times">
                                <span title="{{ $task->created_at->format('d M Y, h:i A') }}">
                                    <i class="feather-send"></i> Assigned {{ $task->created_at->format('d M, h:i A') }}
                                    @if($view === 'to' && $task->creator) by {{ $task->creator->name }} @endif
                                </span>
                                @if($last)
                                    <span title="{{ $last->created_at->format('d M Y, h:i A') }}">
                                        <i class="feather-refresh-cw"></i>
                                        {{ $last->old_status && $last->old_status !== $last->new_status ? $last->old_status . ' → ' : '' }}<b>{{ $last->new_status }}</b>
                                        {{ $last->created_at->format('d M, h:i A') }}
                                        @if($last->user) by {{ $last->user->name }} @endif
                                    </span>
                                @elseif($task->status_changed_at)
                                    <span><i class="feather-refresh-cw"></i> Status changed {{ $task->status_changed_at->format('d M, h:i A') }}</span>
                                @else
                                    <span class="at-times-muted"><i class="feather-clock"></i> No status update yet</span>
                                @endif
                            </div>
                            @if($task->description)
                                <div class="at-update" style="background:#fff;border:1px dashed #e2e8f0;"><span>{{ \Illuminate\Support\Str::limit(strip_tags($task->description), 160) }}</span></div>
                            @endif
                            @if($last && $last->comment)
                                <div class="at-update">
                                    <i class="feather-message-circle"></i>
                                    <span><b>{{ $last->user->name ?? 'Update' }}:</b> {{ \Illuminate\Support\Str::limit($last->comment, 140) }} <em>· {{ $last->created_at->diffForHumans(null, true) }} ago</em></span>
                                </div>
                            @endif
                            <ol class="at-history d-none" id="atHistory{{ $task->id }}" aria-live="polite"></ol>
                            <div class="at-actions">
                                <button type="button" class="at-btn at-btn--primary" data-allow-repeat
                                    onclick="atOpenSheet({{ $task->id }}, @js($task->task_title), @js($task->status))">
                                    <i class="feather-edit-3"></i> Update
                                </button>
                                <button type="button" class="at-btn" data-allow-repeat onclick="atToggleHistory({{ $task->id }}, this, @js($task->created_at->format('d M Y, h:i A')), @js($view === 'to' ? ($task->creator->name ?? null) : null))"><i class="feather-clock"></i> History</button>
                                <a href="{{ route('daily-tasks.index', ['task' => $task->id]) }}" class="at-btn"><i class="feather-external-link"></i> Open</a>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </details>
        @empty
            <div class="at-empty">
                <i class="feather-check-circle"></i>
                <p>
                    @if($filter === 'overdue') Nothing overdue. Everything is on track.
                    @elseif($filter === 'completed') No completed tasks in the last {{ $completedDays }} days.
                    @elseif($view === 'to') Nobody has assigned you an open task.
                    @else You haven't assigned any open tasks. Tap + to assign one.
                    @endif
                </p>
            </div>
        @endforelse
        <div class="at-empty d-none" id="atNoMatch"><i class="feather-search"></i><p>No person or task matches your search.</p></div>
    </div>

    @if($filter !== 'open')
        <p class="at-foot">Completed tasks from the last {{ $completedDays }} days are shown.</p>
    @endif
@endif
</div>

{{-- Status bottom sheet (full-width on phones, centred card on desktop) --}}
<div class="at-sheet-backdrop" id="atSheetBackdrop" onclick="atCloseSheet()"></div>
<div class="at-sheet" id="atSheet" role="dialog" aria-modal="true" aria-labelledby="atSheetTitle" aria-hidden="true">
    <div class="at-sheet-grip"></div>
    <h2 class="at-sheet-title" id="atSheetTitle">Update task</h2>
    <p class="at-sheet-task" id="atSheetTask"></p>
    <div class="at-sheet-statuses" id="atSheetStatuses">
        @foreach($statusOptions as $s)
            <button type="button" class="at-chip at-status--{{ \Illuminate\Support\Str::slug($s) }}" data-status="{{ $s }}" data-allow-repeat onclick="atPickStatus(this)">{{ $s }}</button>
        @endforeach
    </div>
    <textarea id="atSheetComment" class="at-sheet-comment" rows="3" maxlength="1000" placeholder="Add a note (optional)"></textarea>
    <div class="at-sheet-error d-none" id="atSheetError"></div>
    <div class="at-sheet-actions">
        <button type="button" class="at-btn" data-allow-repeat onclick="atCloseSheet()">Cancel</button>
        <button type="button" class="at-btn at-btn--primary" id="atSheetSave" onclick="atSaveStatus()">Save</button>
    </div>
</div>

{{-- The same "Add Quick Note or Task" popup as the dashboard (no card, just the popups + scripts). --}}
@include('partials.quick-notes-card', ['qnModalOnly' => true, 'myNotes' => collect(), 'myAdhocTasks' => collect(), 'tasksIAssigned' => collect()])
@endsection

@push('styles')
<style>
    .at-page *, .at-sheet, .at-sheet * { box-sizing: border-box; }
    .at-page { max-width: 760px; margin: 0 auto; padding: 16px 16px 96px; font-family: 'Inter', sans-serif; color: #0f172a; }
    .at-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; margin-bottom: 16px; }
    .at-title { font-size: 22px; font-weight: 800; margin: 0; letter-spacing: -.3px; color: #0f172a; }
    .at-sub { margin: 2px 0 0; font-size: 13px; color: #64748b; }
    .at-add { flex-shrink: 0; width: 44px; height: 44px; border-radius: 12px; border: 0; background: #3858f9; color: #fff; font-size: 20px; display: grid; place-items: center; box-shadow: 0 6px 16px rgba(56, 88, 249, .3); }

    .at-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin-bottom: 14px; }
    .at-stat { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 12px 6px; text-align: center; }
    .at-stat-num { display: block; font-size: 22px; font-weight: 800; line-height: 1.1; color: #0f172a; font-variant-numeric: tabular-nums; }
    .at-stat-label { font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: .4px; }
    .at-stat--alert .at-stat-num { color: #dc2626; }
    .at-stat--ok .at-stat-num { color: #16a34a; }

    .at-tabs { display: flex; gap: 6px; overflow-x: auto; padding: 4px; background: #f1f5f9; border-radius: 12px; margin-bottom: 12px; scrollbar-width: none; }
    .at-tabs::-webkit-scrollbar { display: none; }
    .at-tab { flex: 1; min-width: max-content; text-align: center; padding: 9px 12px; border-radius: 9px; font-size: 13px; font-weight: 600; color: #475569; text-decoration: none; white-space: nowrap; }
    .at-tab.is-active { background: #fff; color: #3858f9; box-shadow: 0 1px 3px rgba(15, 23, 42, .1); }
    .at-tab-count { display: inline-block; min-width: 20px; padding: 0 6px; margin-left: 2px; border-radius: 10px; background: #e2e8f0; font-size: 11px; line-height: 18px; color: #475569; }
    .at-tab.is-active .at-tab-count { background: #eef1ff; color: #3858f9; }
    .at-tab--alert .at-tab-count { background: #fee2e2; color: #dc2626; }

    .at-search { position: relative; margin-bottom: 14px; }
    .at-search i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; }
    .at-search input { width: 100%; height: 46px; padding: 0 14px 0 40px; border: 1px solid #e2e8f0; border-radius: 12px; font-size: 16px; background: #fff; }
    .at-search input:focus { outline: none; border-color: #3858f9; box-shadow: 0 0 0 3px rgba(56, 88, 249, .12); }

    .at-person { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; margin-bottom: 10px; overflow: hidden; }
    .at-person-head { list-style: none; display: flex; align-items: center; gap: 12px; padding: 14px; cursor: pointer; -webkit-tap-highlight-color: transparent; }
    .at-person-head::-webkit-details-marker { display: none; }
    .at-avatar { width: 42px; height: 42px; border-radius: 50%; object-fit: cover; flex-shrink: 0; }
    .at-avatar--initial { display: grid; place-items: center; background: linear-gradient(135deg, #3858f9, #8b5cf6); color: #fff; font-weight: 700; font-size: 16px; }
    .at-person-info { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 3px; }
    .at-person-name { font-size: 16px; font-weight: 700; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .at-person-meta { font-size: 12px; color: #64748b; display: flex; align-items: center; flex-wrap: wrap; gap: 4px; }
    .at-dot { width: 3px; height: 3px; border-radius: 50%; background: #cbd5e1; display: inline-block; }
    .at-red { color: #dc2626; font-weight: 700; }
    .at-progress { height: 4px; background: #eef2f7; border-radius: 4px; overflow: hidden; margin-top: 3px; max-width: 220px; }
    .at-progress span { display: block; height: 100%; background: #22c55e; border-radius: 4px; }
    .at-count { min-width: 28px; height: 28px; padding: 0 8px; border-radius: 14px; background: #eef1ff; color: #3858f9; font-weight: 700; font-size: 13px; display: grid; place-items: center; }
    .at-chevron { color: #94a3b8; transition: transform .2s; }
    .at-person[open] .at-chevron { transform: rotate(180deg); }

    .at-tasks { list-style: none; margin: 0; padding: 0 14px 6px; border-top: 1px solid #f1f5f9; }
    .at-task { padding: 14px 0; border-bottom: 1px solid #f1f5f9; }
    .at-task:last-child { border-bottom: 0; }
    .at-task.is-overdue { border-left: 3px solid #ef4444; padding-left: 10px; margin-left: -14px; padding-right: 0; }
    .at-task.is-done .at-task-title { color: #94a3b8; text-decoration: line-through; }
    .at-task-top { display: flex; align-items: flex-start; gap: 8px; }
    .at-prio { width: 8px; height: 8px; border-radius: 50%; margin-top: 7px; flex-shrink: 0; background: #94a3b8; }
    .at-prio--hard { background: #ef4444; }
    .at-prio--medium { background: #f59e0b; }
    .at-prio--low { background: #22c55e; }
    .at-task-title { font-size: 15px; font-weight: 600; line-height: 1.4; color: #0f172a; word-break: break-word; }
    .at-task-meta { display: flex; flex-wrap: wrap; align-items: center; gap: 6px 10px; margin: 8px 0 0 16px; font-size: 12px; color: #64748b; }
    .at-task-meta i { font-size: 12px; }
    .at-due--late { color: #dc2626; font-weight: 700; }
    .at-update { display: flex; gap: 6px; margin: 8px 0 0 16px; padding: 8px 10px; background: #f8fafc; border-radius: 10px; font-size: 12.5px; color: #334155; line-height: 1.45; }
    .at-update i { color: #3858f9; margin-top: 2px; }
    .at-update em { color: #94a3b8; font-style: normal; }
    .at-actions { display: flex; gap: 8px; margin: 10px 0 0 16px; }

    .at-status, .at-chip { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 11.5px; font-weight: 700; background: #f1f5f9; color: #475569; }
    .at-status--pending { background: #fef3c7; color: #b45309; }
    .at-status--in-process { background: #dbeafe; color: #1d4ed8; }
    .at-status--review { background: #ede9fe; color: #6d28d9; }
    .at-status--on-hold { background: #e2e8f0; color: #475569; }
    .at-status--rework { background: #ffedd5; color: #c2410c; }
    .at-status--completed { background: #dcfce7; color: #15803d; }

    .at-btn { display: inline-flex; align-items: center; justify-content: center; gap: 6px; min-height: 40px; padding: 0 16px; border-radius: 10px; border: 1px solid #e2e8f0; background: #fff; color: #334155; font-size: 13.5px; font-weight: 600; text-decoration: none; }
    .at-btn--primary { background: #3858f9; border-color: #3858f9; color: #fff; }
    .at-btn:disabled { opacity: .6; }

    .at-empty { text-align: center; padding: 48px 16px; color: #64748b; background: #fff; border: 1px dashed #cbd5e1; border-radius: 16px; }
    .at-empty i { font-size: 32px; color: #22c55e; }
    .at-empty p { margin: 10px 0 0; font-size: 14px; }
    .at-foot { text-align: center; font-size: 12px; color: #94a3b8; margin-top: 12px; }

    .at-sheet-backdrop { position: fixed; inset: 0; background: rgba(15, 23, 42, .45); opacity: 0; pointer-events: none; transition: opacity .2s; z-index: 2040; }
    .at-sheet { position: fixed; left: 0; right: 0; bottom: 0; z-index: 2050; background: #fff; border-radius: 20px 20px 0 0; padding: 10px 18px calc(18px + env(safe-area-inset-bottom)); transform: translateY(105%); transition: transform .25s ease; box-shadow: 0 -10px 40px rgba(15, 23, 42, .2); }
    .at-sheet.is-open { transform: translateY(0); }
    .at-sheet.is-open + .at-sheet-backdrop, .at-sheet-backdrop.is-open { opacity: 1; pointer-events: auto; }
    .at-sheet-grip { width: 40px; height: 4px; border-radius: 4px; background: #cbd5e1; margin: 0 auto 12px; }
    .at-sheet-title { font-size: 17px; font-weight: 800; margin: 0; }
    .at-sheet-task { margin: 4px 0 14px; font-size: 13.5px; color: #64748b; }
    .at-sheet-statuses { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-bottom: 12px; }
    .at-chip { border: 2px solid transparent; padding: 10px 6px; font-size: 13px; text-align: center; }
    .at-chip.is-picked { border-color: #0f172a; }
    .at-sheet-comment { width: 100%; border: 1px solid #e2e8f0; border-radius: 12px; padding: 10px 12px; font-size: 16px; resize: vertical; }
    .at-sheet-comment:focus { outline: none; border-color: #3858f9; }
    .at-sheet-error { margin-top: 8px; font-size: 13px; color: #dc2626; }
    .at-sheet-actions { display: grid; grid-template-columns: 1fr 2fr; gap: 10px; margin-top: 14px; }
    .at-sheet-actions .at-btn { min-height: 48px; font-size: 15px; }

    @media (min-width: 768px) {
        .at-page { padding-top: 24px; }
        .at-sheet { left: 50%; right: auto; bottom: auto; top: 50%; width: 440px; border-radius: 20px; transform: translate(-50%, -40%); opacity: 0; pointer-events: none; transition: opacity .2s, transform .2s; }
        .at-sheet.is-open { transform: translate(-50%, -50%); opacity: 1; pointer-events: auto; }
        .at-sheet-grip { display: none; }
    }

    .at-views { display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px; margin-bottom: 16px; }
    .at-view { display: flex; flex-direction: column; align-items: center; gap: 2px; padding: 10px 6px; border-radius: 14px; border: 1px solid #e2e8f0; background: #fff; color: #475569; text-decoration: none; font-size: 13px; font-weight: 700; text-align: center; line-height: 1.25; }
    .at-view i { font-size: 18px; }
    .at-view-count { font-size: 11px; font-weight: 600; color: #94a3b8; }
    .at-view.is-active { background: #3858f9; border-color: #3858f9; color: #fff; box-shadow: 0 6px 16px rgba(56, 88, 249, .25); }
    .at-view.is-active .at-view-count { color: rgba(255, 255, 255, .85); }
    .at-avatar-wrap { position: relative; width: 42px; height: 42px; flex-shrink: 0; }
    .at-avatar-wrap img { position: absolute; inset: 0; width: 42px; height: 42px; border-radius: 50%; object-fit: cover; }
    .at-role { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; color: #94a3b8; }
    .at-feedback { margin-bottom: 12px; padding: 10px 12px; border-radius: 12px; font-size: 13.5px; font-weight: 600; }
    .at-times { display: flex; flex-direction: column; gap: 3px; margin: 8px 0 0 16px; font-size: 12px; color: #475569; }
    .at-times i { font-size: 11px; color: #94a3b8; margin-right: 2px; }
    .at-times b { color: #0f172a; }
    .at-times-muted { color: #94a3b8; }
    .at-actions { flex-wrap: wrap; }
    .at-history { list-style: none; margin: 10px 0 0 16px; padding: 4px 0 0 16px; border-left: 2px solid #e2e8f0; }
    .at-history li { position: relative; padding: 0 0 12px; font-size: 12.5px; color: #334155; line-height: 1.45; }
    .at-history li::before { content: ''; position: absolute; left: -22px; top: 4px; width: 10px; height: 10px; border-radius: 50%; background: #3858f9; border: 2px solid #fff; box-shadow: 0 0 0 1px #c7d2fe; }
    .at-history li:last-child { padding-bottom: 2px; }
    .at-history-when { display: block; font-size: 11.5px; color: #94a3b8; }
    .at-history-note { display: block; margin-top: 2px; color: #475569; }
    .at-note { display: flex; align-items: flex-start; gap: 12px; background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 14px; margin-bottom: 8px; }
    .at-note .qn-check { width: 22px; height: 22px; margin-top: 1px; flex-shrink: 0; accent-color: #3858f9; }
    .at-note-body { flex: 1; min-width: 0; }
    .at-note-title { font-size: 15px; font-weight: 600; color: #0f172a; word-break: break-word; }
    .at-note.qn-item--done .at-note-title { color: #94a3b8; text-decoration: line-through; }
    .at-note-meta { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 6px; font-size: 12px; color: #64748b; align-items: center; }
    .at-note-type { padding: 2px 9px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: capitalize; background: #fee2e2; color: #b91c1c; }
    .at-note-type--meeting { background: #e0e7ff; color: #4338ca; }
    .at-note-del { border: 0; background: transparent; color: #94a3b8; width: 36px; height: 36px; border-radius: 10px; flex-shrink: 0; font-size: 16px; }
    .at-note-del:hover { background: #fef2f2; color: #dc2626; }
</style>
@endpush

@push('scripts')
<script>
    // After adding a note or assigning a task from the + popup, show it in the right view.
    window.qnOnSaved = function (kind) {
        const url = new URL(window.location.href);
        url.searchParams.set('view', kind === 'note' ? 'notes' : 'by');
        url.searchParams.delete('show');
        window.location.href = url.toString();
    };

    (function () {
        const search = document.getElementById('atSearch');
        search?.addEventListener('input', function () {
            const term = this.value.toLowerCase().trim();
            let shown = 0;
            document.querySelectorAll('#atGroups .at-person').forEach(card => {
                const personMatch = !term || card.dataset.search.includes(term);
                card.style.display = personMatch ? '' : 'none';
                if (personMatch) {
                    shown++;
                    if (term) card.open = true;
                }
            });
            document.getElementById('atNoMatch')?.classList.toggle('d-none', shown > 0 || !term);
        });
    })();

    let atTaskId = null;
    let atStatus = null;

    // Full status timeline for one task (oldest first), fetched once when first opened.
    function atToggleHistory(taskId, btn, assignedAt, assignedBy) {
        const list = document.getElementById('atHistory' + taskId);
        if (!list) return;
        if (list.dataset.loaded === '1') {
            list.classList.toggle('d-none');
            return;
        }
        btn.disabled = true;
        const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
        const fmt = iso => new Date(iso).toLocaleString('en-IN', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit', hour12: true });

        fetch('{{ url('daily-task-history') }}/' + taskId, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(res => res.ok ? res.json() : Promise.reject(res.status))
            .then(rows => {
                const items = [`<li><b>Assigned</b>${assignedBy ? ' by ' + esc(assignedBy) : ''}<span class="at-history-when">${esc(assignedAt)}</span></li>`];
                (Array.isArray(rows) ? rows : []).slice().reverse().forEach(r => {
                    const change = r.old_status && r.old_status !== r.new_status ? esc(r.old_status) + ' → ' : '';
                    items.push(`<li>${change}<b>${esc(r.new_status)}</b>${r.user ? ' by ' + esc(r.user.name) : ''}`
                        + `<span class="at-history-when">${esc(fmt(r.created_at))}</span>`
                        + (r.comment ? `<span class="at-history-note">“${esc(r.comment)}”</span>` : '') + '</li>');
                });
                if (items.length === 1) items.push('<li class="at-times-muted">No status updates yet</li>');
                list.innerHTML = items.join('');
                list.dataset.loaded = '1';
                list.classList.remove('d-none');
            })
            .catch(() => {
                list.innerHTML = '<li class="at-red">Could not load the history. Please try again.</li>';
                list.classList.remove('d-none');
            })
            .finally(() => { btn.disabled = false; });
    }

    function atOpenSheet(taskId, title, status) {
        atTaskId = taskId;
        atStatus = status;
        document.getElementById('atSheetTask').textContent = title;
        document.getElementById('atSheetComment').value = '';
        document.getElementById('atSheetError').classList.add('d-none');
        document.querySelectorAll('#atSheetStatuses .at-chip').forEach(c => c.classList.toggle('is-picked', c.dataset.status === status));
        document.getElementById('atSheet').classList.add('is-open');
        document.getElementById('atSheet').setAttribute('aria-hidden', 'false');
        document.getElementById('atSheetBackdrop').classList.add('is-open');
    }

    function atCloseSheet() {
        document.getElementById('atSheet').classList.remove('is-open');
        document.getElementById('atSheet').setAttribute('aria-hidden', 'true');
        document.getElementById('atSheetBackdrop').classList.remove('is-open');
    }

    function atPickStatus(btn) {
        atStatus = btn.dataset.status;
        document.querySelectorAll('#atSheetStatuses .at-chip').forEach(c => c.classList.toggle('is-picked', c === btn));
    }

    document.addEventListener('keydown', e => { if (e.key === 'Escape') atCloseSheet(); });

    function atSaveStatus() {
        const saveBtn = document.getElementById('atSheetSave');
        const errorEl = document.getElementById('atSheetError');
        saveBtn.disabled = true;
        saveBtn.textContent = 'Saving…';
        errorEl.classList.add('d-none');

        fetch('{{ url('daily-tasks') }}/' + atTaskId + '/status', {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ status: atStatus, comment: document.getElementById('atSheetComment').value.trim() || null })
        })
            .then(async res => {
                const data = await res.json().catch(() => ({}));
                if (!res.ok || data.error) {
                    throw new Error(data.error || data.message || (res.status === 419 ? 'Your session expired. Please refresh the page.' : 'Could not update the task. Please try again.'));
                }
                window.location.reload();
            })
            .catch(err => {
                errorEl.textContent = err.message;
                errorEl.classList.remove('d-none');
                saveBtn.disabled = false;
                saveBtn.textContent = 'Save';
            });
    }
</script>
@endpush
