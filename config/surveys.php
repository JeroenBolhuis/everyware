<?php

return [
    'retention_years' => (int) env('SURVEYS_RETENTION_YEARS', 5),
    'upcoming_warning_days' => (int) env('SURVEYS_UPCOMING_WARNING_DAYS', 7),
];
