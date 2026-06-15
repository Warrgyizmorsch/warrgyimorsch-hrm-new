ALTER TABLE `projects` ADD `slug` VARCHAR(150) NOT NULL AFTER `name`;
ALTER TABLE attendances 
MODIFY status ENUM(
    'present',
    'half_day',
    'absent',
    'missing_punch',
    'leave',
    'wfh',
    'half_day_leave',
    'early_leave',
    'unpaid_leave',
    'unauthorised'
) NOT NULL DEFAULT 'absent';

ALTER TABLE `leave_applications` ADD `approved_by` INT(6) NOT NULL AFTER `total_days`, ADD `approved_at` DATETIME NULL DEFAULT NULL AFTER `approved_by`, ADD `cancelled_at` DATETIME NULL DEFAULT NULL AFTER `approved_at`;