<x-mail::message>
# Log in bij de enquête

Klik op de knop hieronder om veilig in te loggen. Deze link is één uur geldig.

<x-mail::button :url="$signedUrl">
Naar de enquête
</x-mail::button>

Met vriendelijke groet,<br>
{{ config('mail.from.name') }}
</x-mail::message>
