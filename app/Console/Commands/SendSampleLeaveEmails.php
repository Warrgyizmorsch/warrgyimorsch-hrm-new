<?php

namespace App\Console\Commands;

use App\Mail\LeaveWorkflowMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Sends one sample of every leave e-mail (filled with sample data, nothing is saved) so the
 * templates can be checked in a real inbox:
 *
 *   php artisan leave:sample-emails prakash.warrgyizmorsch@gmail.com
 *   php artisan leave:sample-emails you@company.com --only=3
 */
class SendSampleLeaveEmails extends Command
{
    protected $signature = 'leave:sample-emails
        {email : Where to send the samples}
        {--only= : Send just one sample (1-8)}';

    protected $description = 'Send a sample of every leave approval e-mail to one address';

    public function handle(): int
    {
        $email = $this->argument('email');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error("\"{$email}\" is not a valid e-mail address.");

            return self::FAILURE;
        }

        $mailer = config('mail.default');
        if (in_array($mailer, ['log', 'array'], true)) {
            $this->warn("MAIL_MAILER is \"{$mailer}\" — the samples will be written to storage/logs/laravel.log, not delivered. Set your SMTP details in .env to receive them.");
        }

        $samples = $this->samples();
        $only = $this->option('only');
        $sent = 0;

        foreach ($samples as $i => $mail) {
            $no = $i + 1;
            if ($only && (int) $only !== $no) {
                continue;
            }
            $mail['subject'] = "[Sample {$no}/" . count($samples) . '] ' . $mail['subject'];

            try {
                Mail::to($email)->send(new LeaveWorkflowMail($mail));
                $this->info("✓ {$no}. {$mail['subject']}");
                $sent++;
            } catch (\Throwable $e) {
                $this->error("✗ {$no}. Failed: " . $e->getMessage());

                return self::FAILURE;
            }
        }

        $this->newLine();
        $this->info("{$sent} sample e-mail(s) sent to {$email} via \"{$mailer}\".");

        return self::SUCCESS;
    }

    /** Same structure LeaveNotificationService builds for real applications. */
    private function samples(): array
    {
        $brand = config('leave.mail_brand', config('app.name'));
        $myLeaves = url('/profile/leave-history');
        $review = url('/leave/history?awaiting=1');

        $details = [
            'Application ID' => 'LA-0142',
            'Employee' => 'Ravi Kumar (WG-0231)',
            'Department' => 'Development',
            'Leave category' => 'Paid Leave',
            'Leave type' => 'Full Day',
            'Dates' => 'Tue, 20 Oct 2026 to Thu, 22 Oct 2026',
            'Duration' => '3 working days',
            'Applied on' => '12 Oct 2026, 10:42 AM',
        ];
        $withBalance = $details + ['Paid/Sick balance' => '4.5 day(s) available'];

        $applied = ['level' => 'Leave applied', 'status' => 'Start', 'state' => 'applied', 'by' => 'Ravi Kumar', 'at' => '12 Oct 2026, 10:42 AM', 'remarks' => null];
        $tlApproved = ['level' => 'Approved by Team Leader (Level 1)', 'status' => '13m later', 'state' => 'approved', 'by' => 'Tarun Sharma', 'at' => '12 Oct 2026, 10:55 AM', 'remarks' => 'Work handed over to Manish. No client release that week, okay to approve.'];
        $tlRejected = ['level' => 'Rejected by Team Leader (Level 1)', 'status' => '1h 5m later', 'state' => 'rejected', 'by' => 'Tarun Sharma', 'at' => '12 Oct 2026, 11:47 AM', 'remarks' => 'Sprint 3 demo is on 21 Oct — please apply after the release.'];
        $mgrApproved = ['level' => 'Approved by Manager (Level 2)', 'status' => '2h 5m later', 'state' => 'approved', 'by' => 'Mahipal Singh', 'at' => '12 Oct 2026, 1:00 PM', 'remarks' => 'Approved. Enjoy the wedding — keep your phone reachable for emergencies.'];
        $mgrRejected = ['level' => 'Rejected by Manager (Level 2)', 'status' => '2h 5m later', 'state' => 'rejected', 'by' => 'Mahipal Singh', 'at' => '12 Oct 2026, 1:00 PM', 'remarks' => 'Client go-live is on 21 Oct; please take leave from 26 Oct instead.'];
        $waitTl = ['level' => 'Waiting for Team Leader', 'status' => 'Waiting', 'state' => 'pending', 'by' => 'Tarun Sharma', 'at' => 'Pending for less than a minute', 'remarks' => null];
        $waitMgr = ['level' => 'Waiting for Manager', 'status' => 'Waiting', 'state' => 'pending', 'by' => null, 'at' => 'Pending for less than a minute', 'remarks' => null];

        $base = fn (array $m) => array_merge([
            'brand' => $brand,
            'details' => $details,
            'reason' => "Sister's wedding in Jaipur.",
            'message' => 'I will be reachable on phone for urgent issues. Sprint 2 tasks handed over to Manish.',
        ], $m);

        return [
            $base([
                'subject' => '[Action required] Leave request from Ravi Kumar – 20 Oct 2026 to 22 Oct 2026',
                'accent' => '#1d4ed8', 'badge' => 'Awaiting your approval', 'greeting' => 'Hello Tarun Sharma,',
                'intro' => 'Ravi Kumar has applied for leave and it is waiting for your approval (Level 1 — Team Leader). Please review the details below and approve or reject it with your remarks.',
                'details' => $withBalance, 'trail' => [$applied, $waitTl],
                'cta' => ['label' => 'Review & take action', 'url' => $review],
            ]),
            $base([
                'subject' => 'Leave request submitted – 20 Oct 2026 to 22 Oct 2026',
                'accent' => '#0f766e', 'badge' => 'Submitted', 'greeting' => 'Hello Ravi Kumar,',
                'intro' => 'Your leave request has been received. It has been sent to your Team Leader for approval. You will get an e-mail at each step.',
                'details' => $withBalance, 'trail' => [$applied, $waitTl],
                'cta' => ['label' => 'View my leave requests', 'url' => $myLeaves],
            ]),
            $base([
                'subject' => '[Action required] Leave request from Ravi Kumar approved by TL – 20 Oct 2026 to 22 Oct 2026',
                'accent' => '#1d4ed8', 'badge' => 'Awaiting final approval', 'greeting' => 'Hello Mahipal Singh,',
                'intro' => "Tarun Sharma (Team Leader) has approved Ravi Kumar's leave request at Level 1. It now needs your final approval (Level 2).",
                'details' => $withBalance, 'trail' => [$applied, $tlApproved, $waitMgr],
                'cta' => ['label' => 'Review & take action', 'url' => $review],
            ]),
            $base([
                'subject' => 'Leave request approved by your Team Leader – 20 Oct 2026 to 22 Oct 2026',
                'accent' => '#0f766e', 'badge' => 'Approved by TL · Awaiting manager', 'greeting' => 'Hello Ravi Kumar,',
                'intro' => 'Good news — Tarun Sharma has approved your leave request. It has now been sent to the manager for final approval.',
                'trail' => [$applied, $tlApproved, $waitMgr],
                'cta' => ['label' => 'View my leave requests', 'url' => $myLeaves],
            ]),
            $base([
                'subject' => 'Leave request rejected – 20 Oct 2026 to 22 Oct 2026',
                'accent' => '#b91c1c', 'badge' => 'Rejected by Team Leader', 'greeting' => 'Hello Ravi Kumar,',
                'intro' => 'Your leave request has been rejected by Tarun Sharma (Team Leader). Their remarks are below. Please speak to your Team Leader if you have any questions.',
                'trail' => [$applied, $tlRejected],
                'cta' => ['label' => 'View my leave requests', 'url' => $myLeaves],
            ]),
            $base([
                'subject' => 'Leave request Approved – 20 Oct 2026 to 22 Oct 2026',
                'accent' => '#15803d', 'badge' => 'Approved', 'greeting' => 'Hello Ravi Kumar,',
                'intro' => 'Mahipal Singh has reviewed your leave request. Your leave has been approved. Please make sure your work is handed over before you go.',
                'trail' => [$applied, $tlApproved, $mgrApproved],
                'cta' => ['label' => 'View my leave requests', 'url' => $myLeaves],
            ]),
            $base([
                'subject' => 'Leave request Rejected – 20 Oct 2026 to 22 Oct 2026',
                'accent' => '#b91c1c', 'badge' => 'Rejected', 'greeting' => 'Hello Ravi Kumar,',
                'intro' => "Mahipal Singh has reviewed your leave request. Your leave request has been rejected. The manager's remarks are below.",
                'trail' => [$applied, $tlApproved, $mgrRejected],
                'cta' => ['label' => 'View my leave requests', 'url' => $myLeaves],
            ]),
            $base([
                'subject' => "FYI: Ravi Kumar's leave Approved – 20 Oct 2026 to 22 Oct 2026",
                'accent' => '#15803d', 'badge' => 'Approved', 'greeting' => 'Hello Tarun Sharma,',
                'intro' => "For your information: Mahipal Singh has made the final decision on Ravi Kumar's leave request — Approved.",
                'trail' => [$applied, $tlApproved, $mgrApproved],
                'cta' => ['label' => 'Open leave applications', 'url' => url('/leave/history')],
            ]),
        ];
    }
}
