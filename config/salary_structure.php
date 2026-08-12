<?php

return [

    // Percentage breakdown applied to an employee's gross salary when
    // department = "Business Development". Must sum to 1.0.
    'business_development' => [
        'basic_salary' => 0.59,
        'dearness_allowance' => 4705 / 35000,
        'hra' => 3345 / 35000,
        'conveyance_allowance' => 1600 / 35000,
        'medical_allowance' => 1200 / 35000,
        'other_allowance' => 0.10,
    ],

];
