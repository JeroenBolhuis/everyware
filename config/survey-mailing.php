<?php

return [
    'mailer' => env('SURVEY_MAILING_MAILER', 'log'),
    'provider' => env('SURVEY_MAILING_PROVIDER', 'resend_smtp'),
    'subject' => env('SURVEY_MAILING_SUBJECT', 'Bevestiging van je enquete'),
    'from' => [
        'address' => env('SURVEY_MAILING_FROM_ADDRESS', env('MAIL_FROM_ADDRESS', 'hello@example.com')),
        'name' => env('SURVEY_MAILING_FROM_NAME', env('MAIL_FROM_NAME', 'Everyware')),
    ],
    'reply_to' => [
        'address' => env('SURVEY_MAILING_REPLY_TO_ADDRESS'),
        'name' => env('SURVEY_MAILING_REPLY_TO_NAME'),
    ],
    'retention_days' => (int) env('SURVEY_MAILING_RETENTION_DAYS', 365),
];
