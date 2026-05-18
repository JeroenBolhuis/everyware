<?php

namespace App\Support;

final class ParticipantSurveyRedirect
{
    public static function sanitize(mixed $value): string
    {
        if (! is_string($value) || $value === '') {
            return '/surveys';
        }

        if (! str_starts_with($value, '/') || str_contains($value, '://')) {
            return '/surveys';
        }

        if (
            str_starts_with($value, '/survey')
            || str_starts_with($value, '/s/')
            || $value === '/surveys'
        ) {
            return $value;
        }

        return '/surveys';
    }
}
