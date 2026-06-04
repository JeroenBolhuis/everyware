<x-mail::message>
{!! nl2br(e($messageBody)) !!}

@if ($surveyUrl)
<x-mail::button :url="$surveyUrl">
{{ $surveyTitle ? __('Vul :title in', ['title' => $surveyTitle]) : __('Vul de enquete in') }}
</x-mail::button>
@endif

Met vriendelijke groet,<br>
{{ config('mail.from.name') }}
</x-mail::message>
