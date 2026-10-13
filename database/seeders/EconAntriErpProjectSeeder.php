<?php

namespace Database\Seeders;

use App\Models\DailyTask;
use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectChecklistTemplate;
use App\Models\ProjectPhase;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * One-time setup of the Econ Antri / Jamnadas Khanchand Wires ERP project, run in agile form:
 * 7 milestones (the proposal's payment phases), each split into 2-week sprints of user stories,
 * plus a weekly SOP checklist for the team's agile ceremonies.
 *
 *   php artisan db:seed --class=EconAntriErpProjectSeeder
 *
 * Safe to run once: it stops if the project already exists. Change START_DATE or the TEAM name
 * patterns below before running if needed.
 */
class EconAntriErpProjectSeeder extends Seeder
{
    private const PROJECT_NAME = 'Econ Antri & JKW Railway Sleeper ERP';
    private const START_DATE = '2026-10-12'; // Monday — Sprint 1 day 1

    /** Name used to find each person in the employees table (matched with LIKE, must be unique). */
    private const TEAM = [
        'pm' => 'Mahipal Singh',
        'prakash' => 'Prakash',
        'manish' => 'Manish',
        'tarun' => 'Tarun',
    ];

    private const PROPOSAL_FILE = 'project-documents/Econ_Antri_JKW_ERP_Proposal_FRS_1.0.docx';

    private array $team = [];
    private int $assignedByUserId;
    private Carbon $start;

    public function run(): void
    {
        if (!Schema::hasTable('project_phases') || !Schema::hasColumn('project_phases', 'documents')) {
            throw new RuntimeException('Run "php artisan migrate" first (milestone tables are missing).');
        }

        $slug = Str::slug(self::PROJECT_NAME);
        if (Project::where('slug', $slug)->exists()) {
            $this->command->warn('Project "' . self::PROJECT_NAME . '" already exists — nothing was changed.');
            return;
        }

        foreach (self::TEAM as $key => $pattern) {
            $this->team[$key] = $this->findEmployee($pattern);
        }

        $pmUser = $this->team['pm']->user;
        if (!$pmUser) {
            throw new RuntimeException('Mahipal Singh has no login account; tasks need an "assigned by" user.');
        }
        $this->assignedByUserId = $pmUser->id;
        $this->start = Carbon::parse(self::START_DATE)->startOfDay();

        DB::transaction(function () use ($slug) {
            $project = $this->createProject($slug);
            $taskCount = $this->createPhasesAndSprints($project);
            $sopCount = $this->createSopChecklist($project);

            $this->command->info('Created project: ' . $project->name . ' (/projects/' . $project->slug . ')');
            $this->command->info('  Project manager: ' . $this->team['pm']->name);
            $this->command->info('  Team: ' . collect(['prakash', 'manish', 'tarun'])->map(fn ($k) => $this->team[$k]->name)->implode(', '));
            $this->command->info('  Milestones: ' . $project->phases()->count() . ' | Sprint stories: ' . $taskCount . ' | Weekly SOP items: ' . $sopCount);
        });
    }

    private function findEmployee(string $pattern): Employee
    {
        $matches = Employee::active()->where('name', 'like', '%' . $pattern . '%')->get(['id', 'name', 'department_id']);

        if ($matches->count() > 1) {
            // Prefer an exact name or "<pattern> <surname>" over partial matches inside other names.
            $preferred = $matches->filter(fn ($e) => strcasecmp($e->name, $pattern) === 0
                || stripos($e->name, $pattern . ' ') === 0);
            if ($preferred->count() === 1) {
                $matches = $preferred;
            }
        }

        if ($matches->count() !== 1) {
            $found = $matches->map(fn ($e) => "#{$e->id} {$e->name}")->implode(', ') ?: 'none';
            throw new RuntimeException("Could not pick a unique active employee for \"{$pattern}\" (found: {$found}). "
                . 'Edit the TEAM list at the top of EconAntriErpProjectSeeder.php to use the full name.');
        }

        return $matches->first()->load('user');
    }

    private function emp(string $key): int
    {
        return (int) $this->team[$key]->id;
    }

    // -----------------------------------------------------------------------------------------
    // Project
    // -----------------------------------------------------------------------------------------

    private function createProject(string $slug): Project
    {
        $documents = Storage::disk('public')->exists(self::PROPOSAL_FILE) ? [self::PROPOSAL_FILE] : [];

        $project = Project::create([
            'name' => self::PROJECT_NAME,
            'slug' => $slug,
            'start_date' => $this->start->toDateString(),
            // 30 build weeks + 9-week (~60 day) stabilisation = end of Phase 7
            'end_date' => $this->start->copy()->addWeeks(39)->subDay()->toDateString(),
            'status' => 'In Process',
            'technology' => 'ERP Development',
            'leaders' => [$this->emp('pm')],
            'members' => [$this->emp('prakash'), $this->emp('manish'), $this->emp('tarun')],
            'documents' => $documents,
            'description' => <<<'HTML'
<p><strong>Client:</strong> Econ Antri Ltd &amp; Jamnadas Khanchand Wires Pvt Ltd (group companies, separate company-wise workflows).</p>
<p>Custom Laravel + MySQL ERP for railway sleeper manufacturing, replacing Excel-based production, stock, dispatch, finance and HR reporting with one role-based, approval-driven web application.</p>
<p><strong>Modules:</strong> Masters, Production planning &amp; BOM, Advanced stage tracking, Tank / FG 14-day cycle, Inventory &amp; store, Purchase, Sales &amp; contracts (90/10 and 100%-after-dispatch payment terms), Multi-location dispatch, E-invoice / E-way bill data, GST reports, Tally-like accounting, HRMS &amp; payroll, QC, Maintenance, Multi-stage approvals, Notifications, Reports, AI chatbot (ERP + WhatsApp) and CRM.</p>
<p><strong>Delivery method:</strong> Agile — 2-week sprints grouped into 7 payment milestones. Sprint planning on Monday, demo + client update on Friday, retrospective every second Saturday. Detailed scope is governed by FRS 1.0 (FRS 2.0 after project initiation payment).</p>
<p><strong>Commercials:</strong> ₹5,75,000 + 18% GST, milestone-based payments. Third-party APIs, hosting, WhatsApp/SMS and AI usage billed to client at actuals. 60-day free stabilisation after go-live, then AMC.</p>
HTML,
        ]);

        $departmentIds = collect($this->team)->pluck('department_id')->filter()->unique()->values()->all();
        if ($departmentIds && method_exists($project, 'departments')) {
            $project->departments()->sync($departmentIds);
        }

        return $project;
    }

    // -----------------------------------------------------------------------------------------
    // Milestones (phases) and sprint stories (tasks)
    // -----------------------------------------------------------------------------------------

    /**
     * Each phase: weeks, sprints [ [label, weeks, stories[ [title, assignee, priority, description] ]] ].
     * Stories in a sprint run from the sprint start to the sprint end.
     */
    private function plan(): array
    {
        return [
            [
                'name' => 'Phase 1 — Discovery, FRS 2.0 & Foundation',
                'status' => 'In Progress',
                'owner' => 'pm',
                'description' => '<p><strong>Goal:</strong> Lock requirements and build the system foundation.</p>'
                    . '<ul><li>Requirement finalisation and FRS 2.0 with roadmap</li><li>UI/UX planning and dashboard wireframes</li><li>Database architecture</li><li>Core masters for both companies</li><li>Auth, roles, permissions and audit logs</li></ul>'
                    . '<p><strong>Open clarifications from client:</strong> Excel report formats; sleeper types, batch series and BOM; exact 14-day tank rule; tank capacity and naming; Shift A/B/C timings; approval hierarchy; accounting depth for Phase 1; GST / e-invoice API vs JSON; contract payment variations; Excel data migration; hosting and backup policy.</p>'
                    . '<p><strong>Exit criteria / payment milestone:</strong> FRS 2.0, wireframes and DB design signed off; masters live on staging.</p>',
                'sprints' => [
                    ['Sprint 1', 2, [
                        ['Requirement workshop with Econ Antri & JKW; collect Excel/report formats', 'pm', 'Hard', 'Run discovery sessions with both companies. Collect every current Excel and report format, and record company-wise differences.'],
                        ['Resolve client clarifications list (tank rule, shifts, approvals, GST/API scope)', 'pm', 'Hard', 'Close every open point from the proposal\'s "Clarifications Required" list and record the answers in the FRS.'],
                        ['Prepare FRS 2.0 and product roadmap', 'pm', 'Hard', 'Convert FRS 1.0 plus the workshop outcomes into FRS 2.0, with module-wise acceptance criteria and a sprint roadmap.'],
                        ['UI/UX: design system and dashboard wireframes', 'tarun', 'Medium', 'Layout, navigation and component kit. Dashboard wireframes for management, production, store and finance users.'],
                        ['Database architecture and ER diagram (multi-company)', 'prakash', 'Hard', 'High-level schema for all modules with company_id scoping, audit columns and approval history tables.'],
                        ['Project setup: Laravel repo, environments, CI and staging server', 'manish', 'Medium', 'Repository, branching strategy, .env per environment, automated deploy to staging, and a backup job.'],
                    ]],
                    ['Sprint 2', 1, [
                        ['Auth, roles and permissions (Spatie) with audit log', 'prakash', 'Hard', 'Login, password reset, role/permission UI, module-wise rights (view/create/edit/delete/approve/export/print) and an audit trail.'],
                        ['Core masters: company, plant, location, department, designation, shift', 'manish', 'Medium', 'CRUD with company-wise separation for Econ Antri and JKW.'],
                        ['Masters: customer, vendor, transporter, driver, material, unit, warehouse, tank', 'manish', 'Medium', 'Master data screens with import from Excel where the client provides data.'],
                        ['Masters: sleeper type, BOM header, machine, mould, vehicle, QC test, ledger groups', 'tarun', 'Medium', 'Product and asset masters that later modules depend on.'],
                        ['Phase 1 sign-off: FRS 2.0, wireframes and DB design approval', 'pm', 'Hard', 'Client review meeting, record sign-off, and raise the Phase 1 milestone invoice.'],
                    ]],
                ],
            ],
            [
                'name' => 'Phase 2 — Production, Inventory, Tank Cycle & HRMS',
                'status' => 'Planned',
                'owner' => 'prakash',
                'description' => '<p><strong>Goal:</strong> Digitise the plant floor and store.</p>'
                    . '<ul><li>Production planning against order, contract or target</li><li>BOM-based material requirement</li><li>Daily and shift-wise production (A/B/C)</li><li>Advanced stage tracking, from raw material approval to ready for dispatch</li><li>Raw material and FG stock ledgers: GRN, issue, return, transfer and wastage</li><li>Tank / FG storage with the 14-day cycle</li><li>HRMS: attendance, shifts, leave, payroll, overtime and bonus</li></ul>'
                    . '<p><strong>Exit criteria / payment milestone:</strong> Shift-wise production and stock reports match the client\'s current Excel output; tank cycle alerts working.</p>',
                'sprints' => [
                    ['Sprint 3', 2, [
                        ['Production plan: create against order/contract, BOM auto material requirement', 'prakash', 'Hard', 'Plan statuses draft → submitted → approved → in process → completed / stopped / cancelled. Shortage alert against available stock.'],
                        ['BOM management: sleeper-wise BOM, wastage %, BOM vs actual', 'prakash', 'Hard', 'Cement, aggregate, sand, water, admixture, prestressing wire, inserts and consumables per sleeper.'],
                        ['Raw material opening stock, GRN inward and stock ledger', 'manish', 'Hard', 'Inward, outward and balance per material and location. Low stock and reorder alerts.'],
                        ['Production planning and stock screens UI', 'tarun', 'Medium', 'Forms and list views for plans, BOM and stock with filters.'],
                    ]],
                    ['Sprint 4', 2, [
                        ['Daily and shift-wise production entry (Shift A/B/C)', 'prakash', 'Hard', 'Produced, rejected, rework, wastage and closing quantities by sleeper type, order, batch, line, mould and tank.'],
                        ['Advanced stage tracking: batching → mixing → prestressing → pouring → curing → demoulding → finishing → QC', 'prakash', 'Hard', 'Per-stage status, timing, operator, supervisor, quantity, remarks and attachments.'],
                        ['Material requisition (RS), issue note, return, transfer and wastage flow', 'manish', 'Hard', 'RS → stock check → approved issue → ledger update. Falls back to a purchase indent when stock is short.'],
                        ['Tank master and occupancy tracking with 14-day cycle and alerts', 'tarun', 'Hard', 'Fill date + 14 days = expected release. Statuses: under curing, ready, blocked, released, maintenance. Full and overdue alerts.'],
                    ]],
                    ['Sprint 5', 2, [
                        ['HRMS: employee/worker master, skill matrix, shifts, attendance and leave', 'manish', 'Medium', 'Worker categories, machine/mould handling skills, safety training renewal.'],
                        ['Payroll: salary structure, overtime, bonus, salary slip and approval', 'manish', 'Hard', 'Payroll run with approval and later accounting posting.'],
                        ['Production, stock and tank reports (monthly/yearly shift-wise, tank-wise, FG stock)', 'tarun', 'Medium', 'Match the client\'s existing Excel formats; Excel and PDF export.'],
                        ['Phase 2 UAT with production and store team and sign-off', 'pm', 'Hard', 'Run UAT scripts, log defects, get sign-off and raise the Phase 2 milestone invoice.'],
                    ]],
                ],
            ],
            [
                'name' => 'Phase 3 — Purchase, Sales, Contracts, Dispatch & Approvals',
                'status' => 'Planned',
                'owner' => 'manish',
                'description' => '<p><strong>Goal:</strong> Cover the order-to-dispatch and procure-to-pay flows.</p>'
                    . '<ul><li>RS, indent, enquiry, quotation comparison, PO, GRN, purchase bill, payment request</li><li>Customer orders and contracts: 90/10 advance and 100%-after-dispatch terms</li><li>Multi-location and tank-wise dispatch: challan, invoice, e-invoice and e-way bill data</li><li>Configurable multi-stage approvals</li><li>In-app notifications and reminders</li></ul>'
                    . '<p><strong>Exit criteria / payment milestone:</strong> An order can be traced end to end — contract → production → QC → dispatch → invoice — with approvals at each step.</p>',
                'sprints' => [
                    ['Sprint 6', 2, [
                        ['Purchase indent, vendor enquiry, quotation comparison', 'manish', 'Medium', 'Compare rate, terms and delivery time before PO.'],
                        ['Purchase order with PDF, GRN against PO, purchase bill, debit/credit note', 'manish', 'Hard', 'PO approval by value/department; GRN posts stock; bill ready for accounting posting.'],
                        ['Multi-stage approval engine (configurable levels by module/amount/role)', 'prakash', 'Hard', 'Draft → submitted → L1/L2/L3 approved / rejected / hold. Stores approver, time, remarks and reason. Records lock after final approval.'],
                    ]],
                    ['Sprint 7', 2, [
                        ['Customer order management with order-wise production and dispatch tracking', 'manish', 'Medium', 'Statuses draft → confirmed → in production → ready → partially dispatched → completed.'],
                        ['Contract system: 90/10 and 100%-after-dispatch payment terms, outstanding', 'prakash', 'Hard', 'Contract value split, receipts, dispatch-wise adjustment and follow-ups. Signed contract upload and approval.'],
                        ['QC module: cube tests (early/7/28 day), dimension, bending; pass/fail/hold blocks dispatch', 'tarun', 'Hard', 'Lab report attachments and batch-wise QC history.'],
                    ]],
                    ['Sprint 8', 2, [
                        ['Dispatch planning from multiple plants/yards/tanks; vehicle scheduling, loading slip', 'tarun', 'Hard', 'Only QC-passed, cycle-completed stock. Partial dispatch and balance tracking.'],
                        ['Delivery challan, sales invoice, e-invoice (IRN/QR) and e-way bill data/JSON', 'manish', 'Hard', 'JSON export in Phase 1; direct API later if client provides GSP credentials.'],
                        ['Notifications and reminders: stock, approvals, tank cycle, QC, dispatch, payments', 'prakash', 'Medium', 'In-app first; email, SMS and WhatsApp hooks ready.'],
                        ['Phase 3 UAT and sign-off', 'pm', 'Hard', 'UAT with purchase, sales and dispatch users. Raise the Phase 3 milestone invoice.'],
                    ]],
                ],
            ],
            [
                'name' => 'Phase 4 — Accounting (Tally-like) & GST',
                'status' => 'Planned',
                'owner' => 'prakash',
                'description' => '<p><strong>Goal:</strong> Full double-entry accounting and GST compliance.</p>'
                    . '<ul><li>Ledger groups, ledgers, voucher types, cost centres and opening balances</li><li>Vouchers: receipt, payment, contra, journal, purchase, sales, DN and CN</li><li>Day, cash and bank books; trial balance; P&amp;L; balance sheet; bill-wise outstanding and ageing</li><li>Auto-posting from purchase, sales, payroll and payments</li><li>GSTR-1, GSTR-2A/2B reconciliation, GSTR-3B summary/JSON and HSN summary</li></ul>'
                    . '<p><strong>Exit criteria / payment milestone:</strong> Trial balance ties out on test data; GST reports validated by the client\'s accountant.</p>',
                'sprints' => [
                    ['Sprint 9', 2, [
                        ['Accounting masters: ledger groups, ledgers, voucher types, cost centres, opening balances', 'prakash', 'Hard', 'Party masters linked to customer and vendor ledgers.'],
                        ['Voucher entry: receipt, payment, contra, journal, purchase, sales, DN, CN', 'manish', 'Hard', 'Bill-wise references and narration; posting locks after approval.'],
                    ]],
                    ['Sprint 10', 2, [
                        ['Auto-posting from purchase bill, sales invoice, payroll, payment request, receipts', 'prakash', 'Hard', 'Posting rules per document type, with a reversal on cancel.'],
                        ['Books and statements: day book, cash/bank book, ledger, trial balance, P&L, balance sheet', 'tarun', 'Hard', 'Date range and company filters; Excel/PDF export.'],
                        ['Receivable/payable outstanding and ageing', 'manish', 'Medium', 'Customer and vendor-wise bill-wise outstanding.'],
                    ]],
                    ['Sprint 11', 2, [
                        ['GST: GSTR-1, GSTR-2A/2B reconciliation, GSTR-3B summary + JSON, HSN summary', 'manish', 'Hard', 'Also B2B/B2C, ITC, GST payable, purchase and sales registers.'],
                        ['Inter-company transactions and consolidated MIS for both companies', 'prakash', 'Medium', 'Group-level reporting where applicable.'],
                        ['Phase 4 UAT with client accountant and sign-off', 'pm', 'Hard', 'Validate TB and GST outputs; raise the Phase 4 milestone invoice.'],
                    ]],
                ],
            ],
            [
                'name' => 'Phase 5 — AI Chatbot, Reports, Analytics & CRM',
                'status' => 'Planned',
                'owner' => 'tarun',
                'description' => '<p><strong>Goal:</strong> Intelligence and customer-facing tooling.</p>'
                    . '<ul><li>AI assistant inside the ERP for stock queries, production insights, approvals and reports</li><li>WhatsApp AI assistant for customer, vendor and internal updates</li><li>Management dashboard and full report catalogue</li><li>CRM: leads, enquiries, follow-ups, quotations, pipeline and communication history</li><li>Maintenance: moulds, machines, breakdowns and job cards</li></ul>'
                    . '<p><strong>Note:</strong> AI API and WhatsApp API costs are borne by the client.</p>'
                    . '<p><strong>Exit criteria / payment milestone:</strong> Chatbot answers the agreed query set; CRM pipeline in use by the sales team.</p>',
                'sprints' => [
                    ['Sprint 12', 2, [
                        ['CRM: leads, enquiries, follow-ups, quotations, pipeline, reminders', 'manish', 'Medium', 'Included at no extra cost — keep scope as per FRS.'],
                        ['Maintenance: mould/machine master, preventive schedule, breakdown log, job card, spares', 'tarun', 'Medium', 'Downtime tracking and cost posting to production costing.'],
                    ]],
                    ['Sprint 13', 2, [
                        ['In-ERP AI assistant: natural-language queries over stock, production, approvals', 'prakash', 'Hard', 'Role-aware data access; audit of AI queries.'],
                        ['WhatsApp AI assistant integration (client-provided API)', 'prakash', 'Hard', 'Notifications, status queries and workflow assistance.'],
                    ]],
                    ['Sprint 14', 2, [
                        ['Management dashboard KPIs and analytics (production trend, order vs dispatch, cost)', 'tarun', 'Medium', 'Charts with drill-down to transactions.'],
                        ['Complete report catalogue review against client formats', 'manish', 'Medium', 'Production, inventory, consumption, QC, purchase, sales, dispatch, GST, accounting and HR.'],
                        ['Phase 5 demo and sign-off', 'pm', 'Hard', 'Raise the Phase 5 milestone invoice.'],
                    ]],
                ],
            ],
            [
                'name' => 'Phase 6 — Testing, Deployment, Training & Go-Live',
                'status' => 'Planned',
                'owner' => 'pm',
                'description' => '<p><strong>Goal:</strong> Ship to production and hand over.</p>'
                    . '<ul><li>End-to-end regression and UAT</li><li>Performance and security review</li><li>Excel data migration</li><li>Production deployment and backups</li><li>User training for each department and technical documentation</li></ul>'
                    . '<p><strong>Exit criteria / payment milestone:</strong> Go-live. On final payment, hand over the source code, database schema and documentation.</p>',
                'sprints' => [
                    ['Sprint 15', 2, [
                        ['End-to-end regression and UAT across both companies', 'manish', 'Hard', 'Full traceability check: raw material → batch → production → QC → FG → dispatch → invoice → accounts.'],
                        ['Performance tuning, security review, backup and restore test', 'prakash', 'Hard', 'Indexes, slow queries, permissions audit, SSL and restore drill.'],
                        ['Data migration from client Excel sheets (masters, opening stock, balances)', 'tarun', 'Hard', 'Import templates, validation and reconciliation with the client.'],
                    ]],
                    ['Sprint 16', 1, [
                        ['Production deployment and go-live', 'prakash', 'Hard', 'Deploy to the client\'s server with a cut-over plan and rollback plan.'],
                        ['Department-wise user training and user manuals', 'pm', 'Medium', 'Production, store, purchase, QC, dispatch, finance and HR.'],
                        ['Handover: source code, DB schema, technical documentation; final invoice', 'pm', 'Hard', 'Handover as per the proposal on receipt of final payment.'],
                    ]],
                ],
            ],
            [
                'name' => 'Phase 7 — Post Go-Live Stabilisation (60 days)',
                'status' => 'Planned',
                'owner' => 'pm',
                'description' => '<p><strong>Goal:</strong> Keep the system stable after go-live.</p>'
                    . '<ul><li>Free bug fixes, minor adjustments and user assistance</li><li>Up to 8 development hours per month for changes that don\'t alter the DB architecture or approved workflows</li></ul>'
                    . '<p>AMC starts after this period. Major features or new modules are quoted separately.</p>',
                'weeks_override' => 9, // ~60 days
                'sprints' => [
                    ['Stabilisation', 9, [
                        ['Hypercare: monitor errors, fix production bugs, user assistance', 'prakash', 'Hard', 'Daily error log review and a priority bug queue.'],
                        ['Track minor change requests against the 8 hrs/month allowance', 'pm', 'Medium', 'Log every request with hours; anything larger goes to a change request quote.'],
                        ['Stabilisation close-out and AMC kick-off', 'pm', 'Medium', 'Close the stabilisation period formally and start the AMC.'],
                    ]],
                ],
            ],
        ];
    }

    private function createPhasesAndSprints(Project $project): int
    {
        $cursor = $this->start->copy();
        $sprintNo = 0;
        $taskCount = 0;
        $proposalOnDisk = Storage::disk('public')->exists(self::PROPOSAL_FILE);

        foreach ($this->plan() as $order => $p) {
            $phaseStart = $cursor->copy();
            $weeks = $p['weeks_override'] ?? array_sum(array_map(fn ($s) => $s[1], $p['sprints']));
            $phaseEnd = $phaseStart->copy()->addWeeks($weeks)->subDay();

            $phase = ProjectPhase::create([
                'project_id' => $project->id,
                'name' => $p['name'],
                'description' => $p['description'],
                'documents' => ($order === 0 && $proposalOnDisk) ? [[
                    'path' => self::PROPOSAL_FILE,
                    'name' => 'ERP Proposal + FRS 1.0 (Final Payment Terms).docx',
                    'size' => Storage::disk('public')->size(self::PROPOSAL_FILE),
                ]] : [],
                'start_date' => $phaseStart->toDateString(),
                'due_date' => $phaseEnd->toDateString(),
                'status' => $p['status'],
                'owner_id' => $this->emp($p['owner']),
                'sort_order' => $order + 1,
                'created_by' => $this->assignedByUserId,
            ]);

            $sprintCursor = $phaseStart->copy();
            foreach ($p['sprints'] as [$label, $sprintWeeks, $stories]) {
                $isNumbered = str_starts_with($label, 'Sprint');
                if ($isNumbered) {
                    $sprintNo++;
                }
                $sprintStart = $sprintCursor->copy();
                $sprintEnd = $sprintStart->copy()->addWeeks($sprintWeeks)->subDay();

                foreach ($stories as [$title, $assignee, $priority, $details]) {
                    DailyTask::create([
                        'project_id' => $project->id,
                        'phase_id' => $phase->id,
                        'task_title' => "[{$label}] {$title}",
                        'start_date' => $sprintStart->toDateString(),
                        'end_date' => $sprintEnd->copy()->endOfDay(),
                        'priority' => $priority,
                        'status' => 'Pending',
                        'employee_id' => $this->emp($assignee),
                        'assigned_by' => $this->assignedByUserId,
                        'description' => '<p>' . e($details) . '</p>'
                            . '<p><em>' . e($label) . ': ' . $sprintStart->format('d M Y') . ' – ' . $sprintEnd->format('d M Y') . '</em></p>',
                    ]);
                    $taskCount++;
                }

                $sprintCursor = $sprintEnd->copy()->addDay();
            }

            $cursor = $phaseEnd->copy()->addDay();
        }

        return $taskCount;
    }

    // -----------------------------------------------------------------------------------------
    // Weekly SOP checklist (agile ceremonies + engineering hygiene). Due day: 1 = Mon … 7 = Sun.
    // -----------------------------------------------------------------------------------------

    private function createSopChecklist(Project $project): int
    {
        $items = [
            ['Sprint planning / backlog grooming done; sprint stories assigned in HRM', 'pm', 1],
            ['Daily stand-up held every day; blockers noted on the task', 'pm', null],
            ['All team members updated task status and logged hours daily', null, null],
            ['Open bugs triaged and prioritised for the sprint', 'prakash', 2],
            ['Code reviewed and merged via pull request (no direct pushes to main)', 'prakash', 3],
            ['FRS change log updated; scope changes approved by client in writing', 'pm', 3],
            ['QA test cases executed for completed stories', 'manish', 4],
            ['Sprint build deployed to staging and smoke-tested', 'tarun', 4],
            ['Weekly client status update sent (progress, next week, blockers, decisions needed)', 'pm', 5],
            ['Sprint demo to client (sprint end weeks) and feedback logged as stories', 'pm', 5],
            ['DB migrations and schema documentation updated for both companies', 'manish', 5],
            ['Database and code backup verified (restore test monthly)', 'tarun', 6],
            ['Sprint retrospective held (sprint end weeks); action items recorded', 'pm', 6],
        ];

        foreach ($items as $i => [$label, $assignee, $dueDay]) {
            ProjectChecklistTemplate::create([
                'project_id' => $project->id,
                'label' => $label,
                'assigned_to' => $assignee ? $this->emp($assignee) : null,
                'due_weekday' => $dueDay,
                'sort_order' => $i + 1,
                'created_by' => $this->emp('pm'),
            ]);
        }

        return count($items);
    }
}
