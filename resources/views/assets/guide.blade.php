@extends('layouts.app')

@section('content')
    @php
        $isAdmin = \App\Support\AccessControl::isAdmin();
        $isTeamLeader = \App\Support\AccessControl::isTeamLeader();
        $sections = array_filter([
            'overview' => 'Overview',
            'statuses' => 'Statuses & lifecycle',
            'employees' => 'Employees',
            'team-leaders' => $isAdmin || $isTeamLeader ? 'Team leaders' : null,
            'setup' => $isAdmin ? 'Getting started' : null,
            'hr' => $isAdmin ? 'HR / Admin tasks' : null,
            'damage' => 'Damage & loss',
            'audits' => $isAdmin ? 'Audits' : null,
            'routine' => $isAdmin ? 'Dashboard & routine' : null,
            'faq' => 'FAQ',
        ]);
    @endphp
    @include('assets.partials.styles')
    <style>
        .ag-toc { position: sticky; top: 90px; }
        .ag-toc a { display: block; padding: 7px 12px; border-radius: 8px; color: #475569; font-size: 13px; font-weight: 600; text-decoration: none; }
        .ag-toc a:hover { background: #f1f5f9; color: #3858f9; }
        .ag-sec { scroll-margin-top: 90px; }
        .ag-sec h4 { font-size: 18px; font-weight: 700; color: #0f172a; margin-bottom: 6px; }
        .ag-sec h6 { font-size: 14px; font-weight: 700; color: #0f172a; margin: 18px 0 6px; }
        .ag-lead { color: #334155; font-size: 14px; }
        .ag-sec p, .ag-sec li { font-size: 13.5px; color: #334155; line-height: 1.6; }
        .ag-sec ol, .ag-sec ul { padding-left: 20px; margin-bottom: 8px; }
        .ag-key { display: inline-block; padding: 1px 8px; border-radius: 6px; background: #eef2ff; color: #3858f9; font-weight: 600; font-size: 12.5px; }
        .ag-sec .am-table td, .ag-sec .am-table th { white-space: normal; }
    </style>

    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title"><h5 class="m-b-10" style="color:#3858f9;font-weight:700;">How to use Asset Management</h5></div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                <li class="breadcrumb-item active">Asset guide</li>
            </ul>
        </div>
        <div class="page-header-right ms-auto">
            <a href="{{ route('assets.employee') }}" class="btn btn-primary" style="border-radius:10px;background:#3858f9;border:none;"><i class="feather-user-check me-1"></i> Go to My Assets</a>
        </div>
    </div>

    <div class="main-content pt-4 am-wrap">
        @if($isAdmin)
            @include('assets.partials.subnav', ['current' => 'assets.guide'])
        @endif

        <div class="row g-4">
            <div class="col-lg-3 d-none d-lg-block">
                <nav class="am-card p-2 ag-toc" aria-label="Guide sections">
                    @foreach($sections as $id => $label)
                        <a href="#{{ $id }}">{{ $label }}</a>
                    @endforeach
                </nav>
            </div>

            <div class="col-lg-9">
                {{-- Overview --}}
                <section class="am-card am-card-body mb-4 ag-sec" id="overview">
                    <h4>Overview</h4>
                    <p class="ag-lead">Asset Management records every company device, who has it, and everything that happened to it, from purchase to retirement. Every action is logged with who did it and when.</p>
                    <div class="table-responsive">
                        <table class="am-table">
                            <thead><tr><th>Role</th><th>What they do</th></tr></thead>
                            <tbody>
                                <tr><td class="fw-semibold">HR / Admin</td><td>Add assets, allocate, transfer, take back, handle damage and loss, run audits, see the dashboard</td></tr>
                                <tr><td class="fw-semibold">Team Leader</td><td>See team members' devices, move a device between members of their own team, add a device a team member already has</td></tr>
                                <tr><td class="fw-semibold">Employee</td><td>Confirm receipt of devices, report a problem, report damage or loss, request a device, declare a device they already have</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <h6>Key terms</h6>
                    <ul>
                        <li><b>Asset tag</b> - the company ID on every asset, created automatically: <span class="ag-key">WM-LT-0001</span> (laptop), <span class="ag-key">WM-PC-0003</span> (PC), <span class="ag-key">WM-MS-0002</span> (mouse). It should be stuck on the device.</li>
                        <li><b>Status</b> - where the asset is in its life: Available, Allocated, Maintenance, Faulty, Lost or Retired.</li>
                        <li><b>Condition</b> - its physical state: New, Good, Fair or Damaged.</li>
                        <li><b>Handover</b> - one period of one employee holding one asset.</li>
                        <li><b>Receipt confirmation</b> - the employee clicks <em>Confirm receipt</em> to accept a handover, or <em>Report a problem</em>.</li>
                        <li><b>Case</b> - a damage or loss record (<span class="ag-key">INC-0001</span>) with its cost and any amount recovered from the employee.</li>
                    </ul>
                </section>

                {{-- Statuses --}}
                <section class="am-card am-card-body mb-4 ag-sec" id="statuses">
                    <h4>Statuses &amp; lifecycle</h4>
                    <p class="ag-lead">An asset is always in exactly one of six statuses, and only an action changes it, so the history explains every change.</p>
                    <div class="my-3" style="overflow-x:auto;">
                        <svg viewBox="0 0 760 364" role="img" aria-label="An asset moves through six statuses; only Allocated has a holder" style="width:100%;min-width:560px;height:auto;" font-family="Inter, sans-serif" font-size="13">
                            <defs><marker id="ag-arrow" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="6" markerHeight="6" orient="auto-start-reverse"><path d="M0 0L10 5L0 10z" fill="#94a3b8"/></marker></defs>
                            <g fill="none" stroke="#94a3b8" stroke-width="1.25">
                                <path d="M195 110H325" marker-end="url(#ag-arrow)"/><path d="M325 130H195" marker-end="url(#ag-arrow)"/>
                                <path d="M370 96V70H430V96" marker-end="url(#ag-arrow)"/><path d="M475 120H575" marker-end="url(#ag-arrow)"/>
                                <path d="M345 144V200H200V266" marker-end="url(#ag-arrow)"/><path d="M440 144V266" marker-end="url(#ag-arrow)"/>
                                <path d="M160 266V144" marker-end="url(#ag-arrow)"/><path d="M420 314V340H90V144" marker-end="url(#ag-arrow)"/>
                                <path d="M650 96V56H100V96" marker-end="url(#ag-arrow)"/><path d="M650 144V266" marker-end="url(#ag-arrow)"/>
                                <path d="M495 290H575" marker-end="url(#ag-arrow)"/>
                            </g>
                            @foreach([[120,120,'Available','In stock',false],[400,120,'Allocated','With an employee',true],[650,120,'Lost','Open loss case',false],[200,290,'Maintenance','Out for repair',false],[420,290,'Faulty','Broken, in store',false],[650,290,'Retired','Out of service',false]] as [$cx,$cy,$name,$sub,$main])
                                <rect x="{{ $cx - 75 }}" y="{{ $cy - 24 }}" width="150" height="48" rx="8" fill="{{ $main ? 'rgba(56,88,249,0.12)' : '#fff' }}" stroke="{{ $main ? '#3858f9' : '#94a3b8' }}" stroke-width="{{ $main ? 2 : 1.25 }}"/>
                                <text x="{{ $cx }}" y="{{ $cy - 3 }}" text-anchor="middle" font-weight="600" fill="#0f172a">{{ $name }}</text>
                                <text x="{{ $cx }}" y="{{ $cy + 13 }}" text-anchor="middle" font-size="11.5" fill="#64748b">{{ $sub }}</text>
                            @endforeach
                            <g font-size="11.5" fill="#64748b">
                                <text x="260" y="103" text-anchor="middle">Allocate</text><text x="260" y="149" text-anchor="middle">Return (good)</text>
                                <text x="400" y="88" text-anchor="middle">Transfer</text><text x="525" y="112" text-anchor="middle">Report lost</text>
                                <text x="272" y="193" text-anchor="middle">Return: needs repair</text><text x="272" y="216" text-anchor="middle">or Send for repair</text>
                                <text x="152" y="214" text-anchor="end">Repaired</text><text x="448" y="236">Return: broken</text>
                                <text x="642" y="200" text-anchor="end">Not found: write off</text><text x="535" y="282" text-anchor="middle">Retire</text>
                                <text x="255" y="333" text-anchor="middle">Fixed: Mark Available</text><text x="375" y="50" text-anchor="middle">Found</text>
                            </g>
                        </svg>
                    </div>
                    <p>Allocated is the only status with a holder. Any asset without a holder can be retired, a damage case closed as <em>Beyond repair</em> also retires it, and <b>Back in service</b> returns a retired asset to Available. Lost and Retired can't be set from the Edit form - only through these actions.</p>
                </section>

                {{-- Employees --}}
                <section class="am-card am-card-body mb-4 ag-sec" id="employees">
                    <h4>Employees</h4>
                    <p class="ag-lead">Everything is on <a href="{{ route('assets.employee') }}">My Assets</a>. The one thing never to skip: confirm or dispute every new device.</p>
                    <ul>
                        <li><b>Confirm receipt</b> - when HR gives you a device, a yellow box appears at the top. Check the asset tag and serial number on the device, then click <b>Confirm receipt</b>.</li>
                        <li><b>Report a problem</b> - didn't get it, different serial, or already damaged? Click <b>Report a problem</b> and explain. HR sees it immediately.</li>
                        <li><b>Report damage / Report lost</b> - on each device card. Report the same day with the date, what happened and a photo (or police report for theft). A lost device is removed from your name straight away.</li>
                        <li><b>Request device</b> - ask for a device type with a reason; HR allocates or rejects it.</li>
                        <li><b>Declare an asset I have</b> - for a company device you already use that isn't in the system. It shows as yours once HR or your team leader verifies it.</li>
                        <li>Your <em>Damage &amp; Loss Reports</em> table shows each case's status and any amount being recovered.</li>
                    </ul>
                </section>

                @if($isAdmin || $isTeamLeader)
                <section class="am-card am-card-body mb-4 ag-sec" id="team-leaders">
                    <h4>Team leaders</h4>
                    <p class="ag-lead">Use <a href="{{ route('assets.index') }}">Team Assets</a> to see and move your team's devices; everything else stays with HR.</p>
                    <ul>
                        <li>See every device held by members of the departments you lead. Click the eye icon to open its full history.</li>
                        <li>Open a device and click <b>Transfer</b> to move it to another member of your team, with a reason. Moves outside your team go through HR.</li>
                        <li><b>Add for team member</b> - declare a device a team member already has; verify or reject your team's declarations (not ones you entered yourself).</li>
                        <li>Status changes, returns, damage cases and audits are done by HR.</li>
                    </ul>
                </section>
                @endif

                @if($isAdmin)
                <section class="am-card am-card-body mb-4 ag-sec" id="setup">
                    <h4>Getting started</h4>
                    <p class="ag-lead">Setup is done once on the server; after that everything happens in the browser.</p>
                    <ol>
                        <li>Back up the database.</li>
                        <li>Run <code>php artisan migrate</code>. Every existing asset gets an asset tag; existing handovers count as already confirmed.</li>
                        <li>Run <code>php artisan db:seed --class=AssetsIssuedSeeder</code> to load the opening stock. Names it can't match are listed - allocate those from the asset page.</li>
                        <li>Print and stick asset tags on the devices.</li>
                    </ol>
                    <p>Open any asset with the <b>eye icon</b> (or its name) in the inventory - the asset page holds almost every action and the full history.</p>
                </section>

                <section class="am-card am-card-body mb-4 ag-sec" id="hr">
                    <h4>HR / Admin tasks</h4>
                    <p class="ag-lead">Start from the asset page for a single device; use the inventory list to add stock and find things.</p>

                    <h6>Add new assets</h6>
                    <ol>
                        <li><em>Manage Assets</em> &rarr; <b>Add Asset</b>.</li>
                        <li>Fill name, type, serial number and configuration. Open <em>Make, purchase &amp; warranty details</em> and add brand, model, purchase date, cost, vendor, invoice no., warranty expiry and location - this feeds the dashboard's value and warranty alerts.</li>
                        <li>Several devices for one person? Use <b>Add another asset row</b> and pick the employee at the top.</li>
                        <li>Save. Tags are created automatically.</li>
                    </ol>

                    <h6>Allocate a device</h6>
                    <ol>
                        <li>Open the asset (it must be <em>Available</em>) &rarr; <b>Allocate</b>.</li>
                        <li>Pick the employee, the condition at handover and a note (e.g. <em>New joiner kit</em>).</li>
                        <li>The employee is asked to confirm receipt on My Assets.</li>
                    </ol>

                    <h6>Requests and declarations</h6>
                    <ul>
                        <li>A <b>request</b> is an employee asking for a device type: <b>Allocate</b> an available device of that type, or <b>Reject</b>.</li>
                        <li>A <b>declaration</b> is "I already have this company device": <b>Verify</b> (it becomes allocated, no confirmation needed) or <b>Reject</b> with a reason.</li>
                    </ul>

                    <h6>Transfer between employees</h6>
                    <ol>
                        <li>Open the asset &rarr; <b>Transfer</b>.</li>
                        <li>Pick the new holder, condition and a reason (required).</li>
                        <li>The old handover closes and a new one opens. Use Transfer, not Return + Allocate, so the chain stays in one step.</li>
                    </ol>

                    <h6>Take a device back</h6>
                    <ol>
                        <li>Open the asset &rarr; <b>Return</b> (or <em>Mark Returned</em> in the allocated list).</li>
                        <li>Condition: <em>Good</em> &rarr; Available, <em>Needs repair</em> &rarr; Maintenance, <em>Broken</em> &rarr; Faulty. Add a note.</li>
                        <li>Employee caused damage and you want to track cost? Use <b>Report damage</b> instead.</li>
                    </ol>

                    <h6>Other actions (asset page &rarr; More)</h6>
                    <ul>
                        <li><b>Edit details</b> - every change is logged with old and new value.</li>
                        <li><b>Retire / dispose</b> - end of life, sold, e-waste. Return it first; history is kept.</li>
                        <li><b>Back in service</b> - brings a retired asset (or a lost one with no open case) back as Available.</li>
                        <li><b>Mark Available</b> - for Maintenance or Faulty devices once fixed.</li>
                        <li><b>Delete record</b> - only for entries made by mistake; prefer Retire.</li>
                        <li><b>Export</b> - downloads the full register as a CSV for Excel.</li>
                    </ul>
                </section>
                @endif

                {{-- Damage & loss --}}
                <section class="am-card am-card-body mb-4 ag-sec" id="damage">
                    <h4>Damage &amp; loss</h4>
                    <p class="ag-lead">Every damaged or lost device gets a case that stays open until it is closed with an outcome. Cost and recovery are recorded on the case, not in payroll.</p>
                    <p><b>Report it</b> - employees use <em>Report damage</em> / <em>Report lost</em> on My Assets.
                        @if($isAdmin) HR uses the asset page (<em>More</em>) or <em>Damage &amp; Loss</em> &rarr; <b>Report damage / loss</b>. @endif
                        Add the date, what happened, and a photo or police report.</p>
                    <div class="table-responsive">
                        <table class="am-table">
                            <thead><tr><th></th><th>Damage</th><th>Loss</th></tr></thead>
                            <tbody>
                                <tr><td class="fw-semibold">When reported</td><td>Condition becomes <em>Damaged</em>; the employee keeps it unless HR sends it for repair</td><td>Status becomes <em>Lost</em> and it is removed from the employee</td></tr>
                                <tr><td class="fw-semibold">Next step</td><td><b>Send for repair</b> - taken back, status <em>Maintenance</em></td><td>Search for it</td></tr>
                                <tr><td class="fw-semibold">Ways to close</td><td><em>Repaired</em> · <em>Minor, no repair</em> · <em>Beyond repair - write off</em> (Retired)</td><td><em>Found</em> (back to Available) · <em>Not found - write off</em> (Retired)</td></tr>
                            </tbody>
                        </table>
                    </div>
                    @if($isAdmin)
                        <h6>Cost and recovery</h6>
                        <ul>
                            <li><b>Close</b> a case from <em>Damage &amp; Loss</em> or the asset page; fill cost and recovery in the same dialog.</li>
                            <li>The <b>₹</b> button updates cost and recovery any time, even after closing.</li>
                            <li><b>Recover from employee</b>: <em>No recovery</em>, <em>Recovery pending</em> (amount + paid so far), <em>Recovered</em> (fully settled) or <em>Waived</em>.</li>
                            <li>Deduct through payroll manually if company policy says so. For ordinary wear found at return, a plain <b>Return</b> with <em>Needs repair</em> is enough.</li>
                        </ul>
                    @endif
                </section>

                @if($isAdmin)
                <section class="am-card am-card-body mb-4 ag-sec" id="audits">
                    <h4>Audits</h4>
                    <p class="ag-lead">An audit checks every asset is physically where the system says. Run one every quarter and after big moves or exits.</p>
                    <ol>
                        <li><a href="{{ route('assets.audits.index') }}">Asset Audits</a> &rarr; <b>Start audit</b>. Optionally limit to some asset types. Lost and retired assets are skipped.</li>
                        <li>Walk the office with the checklist open. Click <b>Mark unchecked as found</b>, then change only the exceptions: <em>Found with someone else</em>, <em>Found damaged</em>, <em>Missing</em> - with a note.</li>
                        <li><b>Save progress</b> often; you can continue another day.</li>
                        <li><b>Complete</b> with <em>Open a case for each missing and damaged asset</em> ticked: missing items become loss cases, damaged ones damage cases.</li>
                        <li>Fix <em>Found with someone else</em> items with <b>Transfer</b> on each asset page.</li>
                    </ol>
                </section>

                <section class="am-card am-card-body mb-4 ag-sec" id="routine">
                    <h4>Dashboard &amp; routine</h4>
                    <p class="ag-lead">The <a href="{{ route('assets.dashboard') }}">dashboard</a>'s <em>Needs attention</em> list is the HR to-do list: when it is empty, assets are in order.</p>
                    <div class="table-responsive">
                        <table class="am-table">
                            <thead><tr><th>When</th><th>Do this</th></tr></thead>
                            <tbody>
                                <tr><td class="fw-semibold">Daily</td><td>Clear <em>Disputed</em> items: speak to the employee, then Transfer or Return.</td></tr>
                                <tr><td class="fw-semibold">Weekly</td><td>Chase unconfirmed handovers, follow up repairs, update recovery amounts.</td></tr>
                                <tr><td class="fw-semibold">Monthly</td><td>Review warranties ending soon; export the register for accounts.</td></tr>
                                <tr><td class="fw-semibold">Quarterly</td><td>Run a full audit.</td></tr>
                                <tr><td class="fw-semibold">On exit</td><td>Return every device before the last day. The <em>Deactivated Employees</em> tab shows devices still held by inactive accounts.</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <h6>Company rules</h6>
                    <ul>
                        <li>Every handover goes through the system - no informal swaps between colleagues.</li>
                        <li>Employees confirm within 2 working days of receiving a device.</li>
                        <li>Damage or loss is reported within 24 hours.</li>
                        <li>Purchase details and warranty are entered when a device is bought.</li>
                    </ul>
                </section>
                @endif

                {{-- FAQ --}}
                <section class="am-card am-card-body mb-4 ag-sec" id="faq">
                    <h4>FAQ</h4>
                    <div class="table-responsive">
                        <table class="am-table">
                            <thead><tr><th style="width:42%;">Question or message</th><th>Answer</th></tr></thead>
                            <tbody>
                                <tr><td>A device on My Assets isn't mine</td><td>Click <b>Report a problem</b>; HR sees it as <em>Disputed</em> and corrects it.</td></tr>
                                <tr><td>I swapped a mouse with a colleague</td><td>Tell HR or your team leader so they can <b>Transfer</b> it. Until then it's still recorded against you.</td></tr>
                                <tr><td>I damaged or lost a device</td><td>Report it the same day from My Assets. Whether any cost is recovered is decided by HR and shown on your report.</td></tr>
                                @if($isAdmin)
                                <tr><td><em>This asset is no longer available.</em></td><td>Someone else allocated it, or it's in Maintenance / Faulty. Pick another device or mark it Available first.</td></tr>
                                <tr><td><em>Assets can only be given to active employees.</em></td><td>The employee's login is inactive. Reactivate it or choose someone else.</td></tr>
                                <tr><td><em>Lost and Retired are changed with their own actions</em></td><td>Close the loss case as <em>Found</em>, or use <b>Back in service</b>.</td></tr>
                                <tr><td><em>Still with an employee. Mark it returned first.</em></td><td>Return it before you retire or delete it.</td></tr>
                                <tr><td><em>There is already an open damage (or loss) case</em></td><td>Update the existing case instead of opening a new one.</td></tr>
                                <tr><td><em>Recovered amount cannot be more than the amount to recover.</em></td><td>Check the figures, or raise <em>Amount to recover</em>.</td></tr>
                                <tr><td>Deleting an employee is blocked</td><td>They still hold devices - return them first.</td></tr>
                                <tr><td>The Edit pencil is missing</td><td>The asset is Lost or Retired - open the asset page instead.</td></tr>
                                <tr><td>Who had a device last year?</td><td>Asset page &rarr; <b>Holders</b> (every handover) or <b>Timeline</b> (every action).</td></tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </div>
@endsection
