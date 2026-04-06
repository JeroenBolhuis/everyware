<x-mail::message>
# Bedankt voor je reactie

Je feedback voor
`{{ $response->survey->title }}`
is succesvol ontvangen.

@if ($recipientName)
Hallo {{ $recipientName }},
@endif

Fijn dat je de tijd hebt genomen om de enquete in te vullen. Je hoeft nu niets meer te doen.

Als je jouw reactie later wilt intrekken, gebruik dan deze link:

<x-mail::button :url="route('survey.withdraw.show', $response->withdrawal_token)">
Toestemming intrekken
</x-mail::button>

Met vriendelijke groet,<br>
{{ config('mail.from.name') }}
</x-mail::message>
