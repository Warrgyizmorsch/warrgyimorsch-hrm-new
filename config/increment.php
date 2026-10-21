<?php

return [
    // Months between increments; the employee list shows "next due" = last increment (or joining date) + this.
    'cycle_months' => (int) env('INCREMENT_CYCLE_MONTHS', 12),

    // Highlight an employee as "due soon" this many days before the next increment date.
    'due_soon_days' => (int) env('INCREMENT_DUE_SOON_DAYS', 30),
];
