<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Sop;
use Illuminate\Database\Seeder;

class SopSeeder extends Seeder
{
    public function run(): void
    {
        $byName = fn (string $name) => Department::where('name', $name)->value('id');

        $sops = [
            // Company-wide (no department, no role)
            [
                'department' => null,
                'role' => null,
                'title' => 'New Employee IT Setup Checklist',
                'content' => '<ol>'
                    . '<li>Create company email and add to relevant groups/mailing lists.</li>'
                    . '<li>Provision HRM login and verify employee profile details.</li>'
                    . '<li>Allocate required hardware (laptop/desktop, accessories) via Asset Management.</li>'
                    . '<li>Grant access to project tools relevant to the employee\'s department (repo, design tools, ad accounts, etc.).</li>'
                    . '<li>Share company policies and get acknowledgement on Day 1.</li>'
                    . '</ol>',
            ],
            [
                'department' => null,
                'role' => null,
                'title' => 'Data Security & Confidentiality Policy',
                'content' => '<ul>'
                    . '<li>Client data, source code, and credentials must never be shared outside the company or committed to public repositories.</li>'
                    . '<li>Use company email and approved tools only for client communication and file sharing.</li>'
                    . '<li>Lock your system when away from your desk; report lost devices to Admin immediately.</li>'
                    . '<li>Report any suspected data leak or security incident to your reporting manager the same day.</li>'
                    . '</ul>',
            ],
            [
                'department' => null,
                'role' => null,
                'title' => 'Leave Application Procedure',
                'content' => '<ol>'
                    . '<li>Apply for leave in the HRM portal at least 2 working days in advance, except for emergencies/sick leave.</li>'
                    . '<li>Ensure ongoing tasks are handed over or flagged to your team leader before the leave starts.</li>'
                    . '<li>Leave is confirmed only once approved in the portal — do not assume approval from a verbal/chat confirmation alone.</li>'
                    . '</ol>',
            ],

            // Department-specific
            [
                'department' => 'Web/App Development',
                'role' => null,
                'title' => 'Git Branching & Pull Request Workflow',
                'content' => '<ol>'
                    . '<li>Create a feature branch from <code>develop</code> named <code>feature/&lt;short-description&gt;</code>.</li>'
                    . '<li>Commit in small, descriptive chunks; never commit <code>.env</code>, credentials, or database dumps.</li>'
                    . '<li>Open a Pull Request into <code>develop</code> once the feature is testable; request at least one review before merging.</li>'
                    . '<li>Resolve merge conflicts locally, never force-push over a shared branch.</li>'
                    . '<li>Delete the feature branch after it\'s merged.</li>'
                    . '</ol>',
            ],
            [
                'department' => 'Web/App Development',
                'role' => null,
                'title' => 'Client Deployment Checklist',
                'content' => '<ol>'
                    . '<li>Confirm the change has been tested on staging and approved by the reviewer.</li>'
                    . '<li>Take a database backup before running any migration on production.</li>'
                    . '<li>Deploy during the agreed maintenance window unless it\'s a critical hotfix.</li>'
                    . '<li>Verify the live site/app after deployment (smoke test key flows) before closing the task.</li>'
                    . '<li>Notify the client/project lead once deployment is confirmed successful.</li>'
                    . '</ol>',
            ],
            [
                'department' => 'Mobile App Development',
                'role' => null,
                'title' => 'App Release & Store Submission Checklist',
                'content' => '<ol>'
                    . '<li>Bump the version/build number and update the changelog.</li>'
                    . '<li>Test the release build on at least one physical Android and iOS device.</li>'
                    . '<li>Prepare updated screenshots/description if UI has changed.</li>'
                    . '<li>Submit to Play Store/App Store and monitor the review status.</li>'
                    . '<li>Monitor crash reports for 48 hours after the release goes live.</li>'
                    . '</ol>',
            ],
            [
                'department' => 'Digital Marketing',
                'role' => null,
                'title' => 'SEO Reporting & Client Update Procedure',
                'content' => '<ol>'
                    . '<li>Track keyword rankings, traffic, and conversions weekly in the shared tracker.</li>'
                    . '<li>Prepare a monthly report covering rankings, traffic trends, and completed on/off-page work.</li>'
                    . '<li>Flag any ranking drop or Search Console issue to the client within 48 hours of noticing it.</li>'
                    . '</ol>',
            ],
            [
                'department' => 'Social Media Marketing',
                'role' => null,
                'title' => 'Content Calendar & Approval Process',
                'content' => '<ol>'
                    . '<li>Prepare the content calendar for the upcoming week/month in advance.</li>'
                    . '<li>Get client/internal approval on posts before scheduling — never post unapproved content on a client account.</li>'
                    . '<li>Track engagement metrics weekly and adjust the content mix based on performance.</li>'
                    . '</ol>',
            ],
            [
                'department' => 'Web & Graphics Design',
                'role' => null,
                'title' => 'Design Handoff & Asset Naming Convention',
                'content' => '<ol>'
                    . '<li>Name files as <code>&lt;project&gt;_&lt;screen/asset&gt;_v&lt;version&gt;</code> (e.g. <code>warrgy_homepage_v2</code>).</li>'
                    . '<li>Export assets in the format/resolution requested by the developer (SVG for icons, @2x/@3x for images, etc.).</li>'
                    . '<li>Share the final approved file via the shared drive, not personal chat, so it stays discoverable.</li>'
                    . '</ol>',
            ],
            [
                'department' => 'HR Department',
                'role' => null,
                'title' => 'Recruitment & Onboarding Process',
                'content' => '<ol>'
                    . '<li>Post the approved job requirement and screen incoming applications within 2 working days.</li>'
                    . '<li>Schedule interviews and update the candidate\'s status in the portal after each round.</li>'
                    . '<li>On offer acceptance, prepare the offer letter and hand off to IT Setup Checklist for Day 1 readiness.</li>'
                    . '</ol>',
            ],
            [
                'department' => 'Business Development',
                'role' => null,
                'title' => 'Lead Qualification & Follow-up Procedure',
                'content' => '<ol>'
                    . '<li>Log every new lead with source, requirement, and budget indication the same day it\'s received.</li>'
                    . '<li>Follow up on an open lead within 48 hours — no lead should go silent for more than 3 days without a note.</li>'
                    . '<li>Update lead status (qualified/proposal sent/won/lost) promptly so reporting stays accurate.</li>'
                    . '</ol>',
            ],

            // Role-specific (applies to anyone with that role, in any department)
            [
                'department' => null,
                'role' => 'team_leader',
                'title' => 'Team Leader: Escalation & Team Reporting SOP',
                'content' => '<ol>'
                    . '<li>Review your team\'s attendance, task status, and leave requests daily.</li>'
                    . '<li>Approve/reject leave requests and regularizations within 1 working day.</li>'
                    . '<li>Escalate any unresolved employee ticket or blocker to HR/Admin if it\'s pending more than 3 days.</li>'
                    . '<li>Complete Team Leader (1-15/16-30) assessments for your team on time each cycle.</li>'
                    . '</ol>',
            ],
            [
                'department' => null,
                'role' => 'hr_executive',
                'title' => 'HR Executive: Exit & Clearance Process',
                'content' => '<ol>'
                    . '<li>On receiving a resignation, record the last working day and notify the reporting manager and IT/Admin.</li>'
                    . '<li>Coordinate asset return (laptop, ID card, accessories) via Asset Management before the last working day.</li>'
                    . '<li>Deactivate portal/email access on the last working day, not before.</li>'
                    . '<li>Complete full & final settlement calculations within the company\'s standard notice period.</li>'
                    . '</ol>',
            ],
            [
                'department' => null,
                'role' => 'manager',
                'title' => 'Manager: Approval & Sign-off Authority SOP',
                'content' => '<ol>'
                    . '<li>Payroll runs, KPI actuals, and KRA assignments for your teams should be reviewed and approved each month before finalization.</li>'
                    . '<li>Any salary structure override or manual adjustment must be noted with a reason in the remarks field.</li>'
                    . '<li>Escalations from team leaders that remain unresolved after 3 days should be picked up directly.</li>'
                    . '</ol>',
            ],
        ];

        foreach ($sops as $sop) {
            $departmentId = $sop['department'] ? $byName($sop['department']) : null;

            Sop::firstOrCreate(
                ['title' => $sop['title']],
                [
                    'department_id' => $departmentId,
                    'role' => $sop['role'],
                    'content' => $sop['content'],
                    'version' => 1,
                    'status' => true,
                ]
            );
        }
    }
}
