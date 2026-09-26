<?php

namespace Database\Seeders;

use App\Models\LetterTemplate;
use Illuminate\Database\Seeder;

class LetterTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'type' => 'offer_letter',
                'title' => 'Offer Letter',
                'content' => '<p>Dear {{employee_name}},</p>'
                    . '<p>We are pleased to offer you the position of <strong>{{designation}}</strong> in the {{department}} department at {{company_name}}, effective from {{date_of_joining}}.</p>'
                    . '<p>Your monthly gross salary will be {{gross_salary}} ({{gross_salary_words}}), subject to applicable statutory deductions.</p>'
                    . '<p>We look forward to a long and successful association with you.</p>'
                    . '<p>Date: {{today}}</p>',
            ],
            [
                'type' => 'appointment_letter',
                'title' => 'Appointment Letter',
                'content' => '<p>Dear {{employee_name}} ({{employee_code}}),</p>'
                    . '<p>Further to your acceptance of our offer, we are confirming your appointment as <strong>{{designation}}</strong> in the {{department}} department at {{company_name}}, with effect from {{date_of_joining}}.</p>'
                    . '<p>Your monthly gross salary is {{gross_salary}} ({{gross_salary_words}}).</p>'
                    . '<p>This appointment is governed by the company\'s standard policies as communicated to you from time to time.</p>'
                    . '<p>Date: {{today}}</p>',
            ],
            [
                'type' => 'appraisal_letter',
                'title' => 'Appraisal Letter',
                'content' => '<p>Dear {{employee_name}},</p>'
                    . '<p>We are pleased to inform you that, in recognition of your performance and contribution as {{designation}} in the {{department}} department, your compensation has been revised.</p>'
                    . '<p>Your revised monthly gross salary is {{gross_salary}} ({{gross_salary_words}}), effective from {{today}}.</p>'
                    . '<p>We appreciate your continued dedication and look forward to your ongoing contribution.</p>'
                    . '<p>Date: {{today}}</p>',
            ],
            [
                'type' => 'experience_letter',
                'title' => 'Experience Letter',
                'content' => '<p>This is to certify that <strong>{{employee_name}}</strong> ({{employee_code}}) was employed with {{company_name}} as {{designation}} in the {{department}} department from {{date_of_joining}} to {{today}}.</p>'
                    . '<p>During this period, we found {{employee_name}} to be sincere, hardworking and professional in conduct.</p>'
                    . '<p>We wish {{employee_name}} success in all future endeavours.</p>'
                    . '<p>Date: {{today}}</p>',
            ],
            [
                'type' => 'relieving_letter',
                'title' => 'Relieving Letter',
                'content' => '<p>This is to certify that <strong>{{employee_name}}</strong> ({{employee_code}}), who was working as {{designation}} in the {{department}} department, has been relieved from their duties at {{company_name}} effective {{today}}.</p>'
                    . '<p>We confirm that all dues and formalities have been settled as per company policy.</p>'
                    . '<p>We wish {{employee_name}} the very best in future endeavours.</p>'
                    . '<p>Date: {{today}}</p>',
            ],
            [
                'type' => 'certificate',
                'title' => 'Employment Certificate',
                'content' => '<p>This is to certify that <strong>{{employee_name}}</strong> ({{employee_code}}) is/was associated with {{company_name}} as {{designation}} in the {{department}} department, having joined on {{date_of_joining}}.</p>'
                    . '<p>This certificate is issued upon request for whatever purpose it may serve.</p>'
                    . '<p>Date: {{today}}</p>',
            ],
        ];

        foreach ($templates as $template) {
            LetterTemplate::firstOrCreate(
                ['type' => $template['type'], 'title' => $template['title']],
                ['content' => $template['content'], 'is_active' => true]
            );
        }
    }
}
