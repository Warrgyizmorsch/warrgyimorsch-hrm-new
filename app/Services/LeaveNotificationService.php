<?php

namespace App\Services;

use App\Mail\LeaveWorkflowMail;
use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * E-mails for the two-level leave approval.
 *
 *   Applied          → Level 1 TL(s) (or managers when there is no TL): action required
 *                    → employee: request received
 *   TL approved      → managers: action required;  employee: progress update
 *   TL rejected      → employee: rejected (with TL remarks)
 *   Manager decision → employee: final outcome (with remarks); TL copy: FYI
 *   HR (config leave.hr_cc) gets ONE copy per request — the final outcome (approved / rejected /
 *   on hold / unpaid / unauthorised, or a TL rejection). See config leave.hr_cc_mode.
 *
 * Every e-mail for one request uses the same subject + threading headers, so each inbox shows
 * the whole request as a single conversation.
 *
 * Mails are sent after the HTTP response so the user never waits on SMTP, and a mail
 * failure is logged instead of breaking the leave action.
 */
class LeaveNotificationService
{
    private const STATUS_TEXT = [
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'on_hold' => 'On Hold',
        'unpaid' => 'Marked as Unpaid Leave',
        'unauthorised' => 'Marked as Unauthorised',
        'pending' => 'Pending',
    ];

    private const ACCENT = [
        'action' => '#1d4ed8',
        'info' => '#0f766e',
        'approved' => '#15803d',
        'rejected' => '#b91c1c',
        'on_hold' => '#b45309',
        'unpaid' => '#9a3412',
        'unauthorised' => '#b91c1c',
    ];

    public function __construct(
        private LeaveApprovalService $approval,
        private LeaveBalanceService $balance,
    ) {
    }

    // ------------------------------------------------------------------ events

    public function applied(LeaveApplication $leave): void
    {
        $leave->loadMissing('employee.departmentRef', 'employee.user');
        $employee = $leave->employee;
        if (!$employee) {
            return;
        }

        $actor = auth()->user();
        $toTl = $leave->approval_stage === 'tl';
        $approvers = $toTl ? $this->teamLeaderRecipients($employee) : $this->managerRecipients($employee);
        $level = $toTl ? 'Level 1 — Team Leader' : ($this->approval->applicantRole($employee) === 'manager' ? 'Level 2 — HR / Super Admin' : 'Level 2 — Manager');

        foreach ($approvers as $email => $name) {
            if ($this->isActor($email, $actor)) {
                continue;
            }
            $this->send($email, $this->build($leave, [
                'subject' => "[Action required] Leave request from {$employee->name} – " . $this->dateRange($leave),
                'tone' => 'action',
                'badge' => 'Awaiting your approval',
                'greeting' => "Hello {$name},",
                'intro' => "{$employee->name} has applied for leave and it is waiting for your approval ({$level}). "
                    . 'Please review the details below and approve or reject it with your remarks.',
                'cta' => ['label' => 'Review & take action', 'url' => route('leave.history', ['leave' => $leave->id])],
                'show_balance' => true,
            ]));
        }

        $employeeEmail = $this->employeeEmail($employee);
        if ($employeeEmail) {
            $next = $toTl
                ? 'It has been sent to your Team Leader for approval. You will get an e-mail at each step.'
                : 'It has been sent to the manager for approval. You will get an e-mail once a decision is made.';
            $appliedBySomeoneElse = $actor && (int) $actor->employee_id !== (int) $employee->id;

            $this->send($employeeEmail, $this->build($leave, [
                'subject' => 'Leave request submitted – ' . $this->dateRange($leave),
                'tone' => 'info',
                'badge' => 'Submitted',
                'greeting' => "Hello {$employee->name},",
                'intro' => ($appliedBySomeoneElse ? "{$actor->name} has submitted a leave request on your behalf. " : 'Your leave request has been received. ')
                    . $next,
                'cta' => ['label' => 'View this leave request', 'url' => route('profile.leave-history', ['leave' => $leave->id])],
                'show_balance' => true,
            ]));
        }
    }

    public function teamLeaderDecided(LeaveApplication $leave): void
    {
        $leave->refresh()->loadMissing('employee.departmentRef', 'employee.user', 'tlActionUser');
        $employee = $leave->employee;
        if (!$employee) {
            return;
        }
        $tlName = $leave->tlActionUser?->name ?? 'Team Leader';
        $employeeEmail = $this->employeeEmail($employee);

        if ($leave->tl_status === 'approved') {
            foreach ($this->managerRecipients($employee) as $email => $name) {
                $this->send($email, $this->build($leave, [
                    'subject' => "[Action required] Leave request from {$employee->name} approved by TL – " . $this->dateRange($leave),
                    'tone' => 'action',
                    'badge' => 'Awaiting final approval',
                    'greeting' => "Hello {$name},",
                    'intro' => "{$tlName} (Team Leader) has approved {$employee->name}'s leave request at Level 1. "
                        . 'It now needs your final approval (Level 2).',
                    'cta' => ['label' => 'Review & take action', 'url' => route('leave.history', ['leave' => $leave->id])],
                    'show_balance' => true,
                ]));
            }

            if ($employeeEmail) {
                $this->send($employeeEmail, $this->build($leave, [
                    'subject' => 'Leave request approved by your Team Leader – ' . $this->dateRange($leave),
                    'tone' => 'info',
                    'badge' => 'Approved by TL · Awaiting manager',
                    'greeting' => "Hello {$employee->name},",
                    'intro' => "Good news — {$tlName} has approved your leave request. It has now been sent to the manager for final approval.",
                    'cta' => ['label' => 'View this leave request', 'url' => route('profile.leave-history', ['leave' => $leave->id])],
                ]));
            }

            return;
        }

        // Rejected at Level 1 — the request is closed, so this is the final outcome HR gets.
        $this->sendFinal($employeeEmail, $leave, [
            'subject' => 'Leave request rejected – ' . $this->dateRange($leave),
            'tone' => 'rejected',
            'badge' => 'Rejected by Team Leader',
            'greeting' => "Hello {$employee->name},",
            'intro' => "Your leave request has been rejected by {$tlName} (Team Leader). Their remarks are below. "
                . 'Please speak to your Team Leader if you have any questions.',
            'cta' => ['label' => 'View this leave request', 'url' => route('profile.leave-history', ['leave' => $leave->id])],
        ], "{$employee->name}'s leave request has been rejected by {$tlName} (Team Leader) at Level 1. The request is closed.");
    }

    public function managerDecided(LeaveApplication $leave, string $oldStatus): void
    {
        $leave->refresh()->loadMissing('employee.departmentRef', 'employee.user', 'tlActionUser', 'managerActionUser');
        $employee = $leave->employee;
        $status = strtolower($leave->status);
        if (!$employee || $status === 'pending') {
            return; // moving back to pending is an internal correction — no e-mail
        }

        $statusText = self::STATUS_TEXT[$status] ?? ucfirst(str_replace('_', ' ', $status));
        $managerName = $leave->managerActionUser?->name ?? 'the manager';
        $isChange = $oldStatus !== 'pending' && $oldStatus !== $status;
        $outcomeLine = match ($status) {
            'approved' => 'Your leave has been approved. Please make sure your work is handed over before you go.',
            'rejected' => 'Your leave request has been rejected. The manager\'s remarks are below.',
            'on_hold' => 'Your leave request has been put on hold. The manager\'s remarks are below.',
            'unpaid' => 'Your leave has been recorded as unpaid leave. This may affect your salary for the month.',
            'unauthorised' => 'Your leave has been marked as unauthorised. Please contact HR.',
            default => "Your leave request status is now {$statusText}.",
        };

        $employeeEmail = $this->employeeEmail($employee);
        $this->sendFinal($employeeEmail, $leave, [
            'subject' => "Leave request {$statusText} – " . $this->dateRange($leave),
            'tone' => $status,
            'badge' => $statusText,
            'greeting' => "Hello {$employee->name},",
            'intro' => ($isChange ? "{$managerName} has updated your leave request. " : "{$managerName} has reviewed your leave request. ")
                . $outcomeLine,
            'cta' => ['label' => 'View this leave request', 'url' => route('profile.leave-history', ['leave' => $leave->id])],
        ], "{$managerName} has made the final decision on {$employee->name}'s leave request — {$statusText}.");

        // FYI copy for the Team Leader who handled Level 1 (no HR copy — HR got the final outcome above).
        $fyi = [];
        if ($leave->tlActionUser && $leave->tlActionUser->email) {
            $fyi[strtolower($leave->tlActionUser->email)] = $leave->tlActionUser->name;
        }
        unset($fyi[strtolower((string) $employeeEmail)]);

        foreach ($fyi as $email => $name) {
            if ($this->isActor($email, auth()->user())) {
                continue;
            }
            $this->send($email, $this->build($leave, [
                'subject' => "FYI: {$employee->name}'s leave {$statusText} – " . $this->dateRange($leave),
                'tone' => $status,
                'badge' => $statusText,
                'greeting' => "Hello {$name},",
                'intro' => "For your information: {$managerName} has made the final decision on {$employee->name}'s leave request — {$statusText}.",
                'cta' => ['label' => 'Open leave applications', 'url' => route('leave.history', ['leave' => $leave->id])],
            ]));
        }
    }

    /**
     * Final outcome: to the employee with HR in CC. Without an employee e-mail, HR gets it directly
     * so the outcome still reaches HR exactly once.
     */
    private function sendFinal(?string $employeeEmail, LeaveApplication $leave, array $opts, string $hrIntro): void
    {
        if ($employeeEmail) {
            $this->send($employeeEmail, $this->build($leave, $opts), ccHr: true);

            return;
        }

        $hr = $this->hrEmail();
        if ($hr) {
            $this->send($hr, $this->build($leave, array_merge($opts, [
                'greeting' => 'Hello HR,',
                'intro' => $hrIntro,
                'cta' => ['label' => 'Open leave applications', 'url' => route('leave.history', ['leave' => $leave->id])],
            ])));
        }
    }

    /** HR inbox for final-outcome copies, or null when switched off (config leave.hr_cc_mode). */
    private function hrEmail(): ?string
    {
        $hr = trim((string) config('leave.hr_cc'));
        if (config('leave.hr_cc_mode', 'final') === 'off' || !filter_var($hr, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return $hr;
    }

    // --------------------------------------------------------------- recipients

    /** @return array<string,string> email => name */
    private function teamLeaderRecipients(Employee $employee): array
    {
        return $this->approval->teamLeadersFor($employee)
            ->filter(fn (User $u) => filter_var($u->email, FILTER_VALIDATE_EMAIL))
            ->mapWithKeys(fn (User $u) => [strtolower($u->email) => $u->name])
            ->all();
    }

    /** @return array<string,string> email => name */
    private function managerRecipients(?Employee $applicant = null): array
    {
        // A manager's own leave goes to HR / Super Admin, never back to managers.
        if ($applicant && $this->approval->applicantRole($applicant) === 'manager') {
            $selfEmail = strtolower((string) ($applicant->email ?: $applicant->user?->email));

            return $this->activeUsersWithRoles(LeaveApprovalService::SENIOR_APPROVER_ROLES)
                ->filter(fn (User $u) => filter_var($u->email, FILTER_VALIDATE_EMAIL) && strtolower($u->email) !== $selfEmail)
                ->mapWithKeys(fn (User $u) => [strtolower($u->email) => $u->name])
                ->all();
        }

        $configured = config('leave.manager_emails', []);
        if ($configured) {
            $names = User::whereIn('email', $configured)->pluck('name', 'email');

            return collect($configured)
                ->filter(fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL))
                ->mapWithKeys(fn ($e) => [strtolower($e) => $names[$e] ?? 'Manager'])
                ->all();
        }

        $roles = config('leave.manager_roles', ['manager']);
        $users = $this->activeUsersWithRoles($roles);
        if ($users->isEmpty()) {
            $users = $this->activeUsersWithRoles(['super_admin']);
        }

        return $users->filter(fn (User $u) => filter_var($u->email, FILTER_VALIDATE_EMAIL))
            ->mapWithKeys(fn (User $u) => [strtolower($u->email) => $u->name])
            ->all();
    }

    private function activeUsersWithRoles(array $roles): Collection
    {
        $roles = array_map([LeaveApprovalService::class, 'role'], $roles);

        return User::where('account_status', 'active')
            ->get(['id', 'name', 'email', 'role', 'account_status'])
            ->filter(fn (User $u) => in_array(LeaveApprovalService::role($u->role), $roles, true))
            ->values();
    }

    private function employeeEmail(Employee $employee): ?string
    {
        $email = $employee->email ?: $employee->user?->email;

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    private function isActor(string $email, ?User $actor): bool
    {
        return $actor && strcasecmp($email, (string) $actor->email) === 0;
    }

    // ------------------------------------------------------------------ content

    private function build(LeaveApplication $leave, array $opts): array
    {
        $employee = $leave->employee;
        $status = strtolower($leave->status);

        $details = [
            'Application ID' => 'LA-' . str_pad((string) $leave->id, 4, '0', STR_PAD_LEFT),
            'Employee' => $employee->name . ($employee->employee_code ? " ({$employee->employee_code})" : ''),
            'Department' => $employee->departmentRef?->name ?? '—',
            'Leave category' => $this->categoryLabel($leave),
            'Leave type' => $this->typeLabel($leave),
            'Dates' => $this->dateRange($leave, true),
            'Duration' => $this->durationLabel($leave),
            'Applied on' => $leave->created_at?->format('d M Y, h:i A') ?? '—',
        ];

        if (!empty($opts['show_balance']) && in_array($leave->leave_category, ['Paid Leave', 'Sick Leave'], true)) {
            try {
                $summary = $this->balance->getEmployeeBalanceSummary((int) $employee->id, Carbon::parse($leave->start_date));
                $details['Paid/Sick balance'] = rtrim(rtrim(number_format((float) $summary['balance'], 1), '0'), '.') . ' day(s) available';
            } catch (\Throwable $e) {
                // balance is a nice-to-have in the e-mail
            }
        }

        // Full timeline with timestamps: applied → TL → manager (every change) → still waiting.
        $leave->unsetRelation('logs'); // re-read so the step just recorded is included
        $trail = collect($this->approval->timeline($leave))->map(fn ($ev) => [
            'level' => $ev['title'],
            'status' => $ev['gap'] ?? (!empty($ev['pending']) ? 'Waiting' : ($ev['state'] === 'applied' ? 'Start' : '')),
            'state' => $ev['state'],
            'by' => $ev['by'],
            'at' => $ev['at'],
            'remarks' => $ev['remarks'],
        ])->all();

        // WFH is not leave: say "WFH request" everywhere the employee / approver reads it.
        $isWfh = $this->isWfh($leave);
        if ($isWfh) {
            foreach (['subject', 'badge', 'intro'] as $key) {
                $opts[$key] = $this->wfhWording($opts[$key] ?? '');
            }
            if (!empty($opts['cta']['label'])) {
                $opts['cta']['label'] = $this->wfhWording($opts['cta']['label']);
            }
            $details = collect($details)->mapWithKeys(fn ($v, $k) => [
                match ($k) { 'Leave category' => 'Request type', 'Leave type' => 'Day type', default => $k } => $v,
            ])->all();
        }

        // One subject for the whole request (Gmail only threads mails whose subjects match); the
        // step-specific line goes in the badge / intro / push title instead.
        $threadSubject = 'Leave request ' . $details['Application ID'] . ' – ' . $employee->name . ' – ' . $this->dateRange($leave);
        if ($isWfh) {
            $threadSubject = $this->wfhWording($threadSubject);
        }
        $host = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'hrm.local';

        return [
            'is_wfh' => $isWfh,
            'subject' => Str::limit($threadSubject, 150, '…'),
            'headline' => $opts['subject'],
            'thread_root' => "leave-{$leave->id}@{$host}",
            'brand' => config('leave.mail_brand', config('app.name')),
            'accent' => self::ACCENT[$opts['tone']] ?? self::ACCENT['info'],
            'badge' => $opts['badge'],
            'greeting' => $opts['greeting'],
            'intro' => $opts['intro'],
            'details' => $details,
            'reason' => $leave->reason,
            'message' => $leave->message,
            'trail' => $trail,
            'cta' => $opts['cta'] ?? null,
        ];
    }

    public function isWfh(LeaveApplication $leave): bool
    {
        return str_contains(strtolower((string) $leave->leave_category), 'wfh')
            || str_contains(strtolower((string) $leave->leave_category), 'work from home');
    }

    /** Rewrite leave wording for a Work From Home request (subject, intro, buttons). */
    private function wfhWording(string $text): string
    {
        return strtr($text, [
            'has applied for leave' => 'has requested Work From Home',
            'applied for leave' => 'requested Work From Home',
            'Leave request' => 'WFH request',
            'leave request' => 'WFH request',
            'Your leave has been approved. Please make sure your work is handed over before you go.' => 'Your WFH request has been approved. Please stay reachable during working hours.',
            'Your leave has been' => 'Your WFH request has been',
            "'s leave " => "'s WFH request ",
            'Open leave applications' => 'Open WFH request',
        ]);
    }

    private function categoryLabel(LeaveApplication $leave): string
    {
        $cat = strtolower($leave->leave_category ?? '');

        return match (true) {
            str_contains($cat, 'gatepass') => 'Early Leave (Gatepass)',
            str_contains($cat, 'wfh') => 'Work From Home',
            default => $leave->leave_category ?: '—',
        };
    }

    private function typeLabel(LeaveApplication $leave): string
    {
        return $leave->leave_type ?: '—';
    }

    private function durationLabel(LeaveApplication $leave): string
    {
        if (str_contains(strtolower($leave->leave_category ?? ''), 'gatepass')) {
            return '1 hour';
        }
        if (str_contains(strtolower($leave->leave_type ?? ''), 'half')) {
            return 'Half day (0.5)';
        }
        $days = (float) $leave->total_days;
        $text = rtrim(rtrim(number_format($days, 2), '0'), '.');

        return $text . ' working day' . ($days == 1.0 ? '' : 's');
    }

    private function dateRange(LeaveApplication $leave, bool $long = false): string
    {
        $start = Carbon::parse($leave->start_date);
        $end = $leave->end_date ? Carbon::parse($leave->end_date) : $start;
        $fmt = $long ? 'D, d M Y' : 'd M Y';

        if (str_contains(strtolower($leave->leave_category ?? ''), 'gatepass') && $leave->start_time) {
            $from = Carbon::parse($leave->start_time)->format('h:i A');
            $to = $leave->end_time ? Carbon::parse($leave->end_time)->format('h:i A') : null;

            return $start->format($fmt) . ', ' . $from . ($to ? " – {$to}" : '');
        }

        return $end->gt($start) ? $start->format($fmt) . ' to ' . $end->format($fmt) : $start->format($fmt);
    }

    // ------------------------------------------------------------------ sending

    private function send(string $email, array $mail, bool $ccHr = false): void
    {
        // Browser push to the same person (if they turned on desktop alerts) — independent of e-mail.
        try {
            $sentence = preg_split('/(?<=[.!?])\s+/', trim($mail['intro']))[0] ?? '';
            app(\App\Services\WebPush\WebPushService::class)->notifyEmail(
                $email,
                preg_replace('/\s+–\s+.*$/u', '', $mail['headline']),  // drop the date part, keep it short
                trim(($mail['details']['Dates'] ?? '') . ' · ' . $sentence, ' ·'),
                $mail['cta']['url'] ?? null,
                'leave-' . ($mail['details']['Application ID'] ?? 'x')       // later updates replace earlier ones
            );
        } catch (\Throwable $e) {
            Log::warning('Leave push failed', ['to' => $email, 'error' => $e->getMessage()]);
        }

        if (!config('leave.mail_enabled', true)) {
            return;
        }

        // After the response is sent: no waiting on SMTP, no queue worker needed.
        // HR is copied only on the final outcome (or on everything in the old 'all' mode).
        $hrCc = ($ccHr || config('leave.hr_cc_mode', 'final') === 'all') ? $this->hrEmail() : null;

        app()->terminating(function () use ($email, $mail, $hrCc) {
            try {
                $message = Mail::to($email);
                if ($hrCc && strcasecmp($hrCc, $email) !== 0) {
                    $message->cc($hrCc);
                }
                $message->send(new LeaveWorkflowMail($mail));
            } catch (\Throwable $e) {
                Log::warning('Leave e-mail failed', ['to' => $email, 'subject' => $mail['subject'], 'error' => $e->getMessage()]);
            }
        });
    }
}
