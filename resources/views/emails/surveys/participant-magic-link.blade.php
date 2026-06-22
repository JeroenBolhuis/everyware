<x-mail::message>
# Log in bij de enquête

Je ontvangt deze mail omdat jouw e-mailadres is ingevuld om in te loggen bij Everyware. Met deze veilige loginlink kun je zonder wachtwoord verder naar de enquêtes of je puntenoverzicht.

Klik op de knop hieronder om in te loggen. Deze link is één uur geldig en werkt alleen vanuit deze mail.

<x-mail::button :url="$signedUrl">
Naar de enquête
</x-mail::button>

Heb je deze loginlink niet aangevraagd? Dan kun je deze mail negeren.

Met vriendelijke groet,<br>
{{ config('mail.from.name') }}
</x-mail::message>
