# Projectoverzicht

Dit document helpt nieuwe ontwikkelaars snel te begrijpen waar onderdelen van het Everyware-project staan. Het is bedoeld als wegwijzer: als je iets wilt aanpassen, kun je hier zien in welke map of welk bestand je waarschijnlijk moet kijken.

## Hoofdstructuur

| Pad | Waarvoor is het? |
| --- | --- |
| `app/` | Backendcode van Laravel: controllers, modellen, policies, actions, providers en requests. |
| `resources/views/` | Blade views en Livewire single-file component views. Hier staat de HTML/UI. |
| `resources/js/` | JavaScript voor interactieve onderdelen, zoals surveybeheer en survey invullen. |
| `resources/css/` | CSS en Tailwind-gerelateerde styling. |
| `routes/` | Webroutes, adminroutes, settingsroutes en console routes. |
| `database/` | Migrations, seeders en factories. Hier staat de databasestructuur en testdata-opbouw. |
| `tests/` | Geautomatiseerde tests met Pest/PHPUnit. |
| `config/` | Laravel configuratie, zoals database, mail, auth, cache, session en survey-instellingen. |
| `api/` | Vercel serverless entrypoints en Blob upload endpoint. |
| `.github/workflows/` | GitHub Actions voor tests en linting. |
| `docs/` | Projectdocumentatie. |
| `public/` | Publieke bestanden zoals afbeeldingen en gebouwde assets. |

## Routes

De routes bepalen welke URL naar welke controller of Livewire component gaat.

### `routes/web.php`

Hier staan de meeste publieke en survey-gerelateerde routes.

Belangrijke onderdelen:

- `/` toont de welkomstpagina.
- `/dashboard` stuurt ingelogde gebruikers door naar hun juiste startpagina.
- `/enquetes` bevat het klassieke surveybeheer via `SurveyManagerController`.
- `/survey/deelnemer/inloggen` is de login flow voor deelnemers met magic links.
- `/survey/{survey}` toont en verwerkt gewone survey-inzendingen.
- `/s/{token}` toont en verwerkt gedeelde survey-links.
- `/student/punten` toont het puntenoverzicht voor deelnemers.
- `/survey-withdraw/{token}` verwerkt het intrekken van een inzending.
- `/surveys` toont het publieke overzicht van actieve surveys.

### `routes/admin.php`

Hier staan de adminroutes. Deze routes zitten onder `/admin` en zijn beschermd met login, verificatie en rollen.

Belangrijke onderdelen:

- `admin/surveys` toont het adminoverzicht van surveys.
- `admin/surveys/{survey}` toont survey details.
- `admin/surveys/{survey}/export` downloadt feedback export.
- `admin/responses/{response}` toont een specifieke response.
- `admin/participants` toont deelnemers.
- `admin/users` is alleen voor admins en beheert gebruikers.

Veel adminpagina's gebruiken Livewire single-file components in `resources/views/pages/admin/`.

### `routes/settings.php`

Hier staan accountinstellingen voor ingelogde gebruikers.

Belangrijke onderdelen:

- `settings/profile`
- `settings/password`
- `settings/appearance`
- `settings/two-factor`

Deze pagina's gebruiken Livewire component views in `resources/views/pages/settings/`.

### `routes/console.php`

Hier kunnen console routes of geplande commands worden toegevoegd. De command voor het opschonen van oude survey-antwoorden staat in `app/Console/Commands/PruneSurveyAnswers.php`.

## Controllers

Controllers staan in `app/Http/Controllers/`. Ze ontvangen requests, halen data op en sturen responses of views terug.

| Bestand | Functie |
| --- | --- |
| `SurveyController.php` | Publieke surveyflow: overzicht, survey tonen, invullen, bedankpagina, contactdetails toestaan. |
| `SurveyManagerController.php` | Beheer van surveys via `/enquetes`: aanmaken, bewerken, sluiten en vragen beheren. |
| `ParticipantSurveyAuthController.php` | Login voor deelnemers via magic link, verificatie en uitloggen. |
| `StudentPointsController.php` | Puntenoverzicht voor deelnemers. |
| `SurveyWithdrawalController.php` | Inzending of toestemming intrekken via withdrawal token. |
| `Admin/SurveyFeedbackExportController.php` | Export van survey feedback als CSV/XLSX. |

## Models

Models staan in `app/Models/`. Deze classes vertegenwoordigen database-tabellen en relaties.

| Model | Betekenis |
| --- | --- |
| `User` | Normale applicatiegebruiker, zoals admin of LIC-medewerker. |
| `Participant` | Deelnemer/student die surveys invult via magic link. |
| `Survey` | Een enquete. Heeft vragen en responses. |
| `SurveyQuestion` | Een vraag binnen een survey. |
| `SurveyResponse` | Een ingevulde survey. |
| `SurveyAnswer` | Een antwoord op een specifieke vraag. |
| `ContactInformationSubmission` | Contactgegevens die bij een response gedeeld zijn. Velden zoals naam/e-mail worden versleuteld. |
| `ParticipantPointsHistory` | Historie van toegekende of gecorrigeerde punten. |
| `SurveySetting` | Instellingen voor survey retention, zoals bewaartermijn. |

## Actions

Actions staan in `app/Actions/`. Dit zijn losse stukken businesslogica die door controllers, commands of Fortify gebruikt worden.

### `app/Actions/Surveys/`

| Bestand | Functie |
| --- | --- |
| `BuildSurveyFeedbackExport.php` | Bouwt exportdata en CSV-output voor survey feedback. |
| `BuildSurveyFeedbackWorkbook.php` | Bouwt XLSX workbook-output zonder externe spreadsheet dependency. |
| `DeleteSurveySubmission.php` | Verwijdert een survey response met antwoorden, contactgegevens en puntenhistorie. |
| `SurveyRetentionSettings.php` | Leest en wijzigt bewaartermijninstellingen. |

### `app/Actions/Participants/`

| Bestand | Functie |
| --- | --- |
| `DeductParticipantPoints.php` | Trekt punten af bij deelnemers en registreert dit in de historie. |

### `app/Actions/Fortify/`

| Bestand | Functie |
| --- | --- |
| `CreateNewUser.php` | Maakt nieuwe gebruikers aan en valideert naam, e-mail en wachtwoord. |
| `ResetUserPassword.php` | Reset wachtwoorden via Laravel Fortify. |

## Form Requests

Form requests staan in `app/Http/Requests/`. Ze regelen validatie en autorisatie voor formulieren.

| Bestand | Functie |
| --- | --- |
| `Surveys/UpsertSurveyRequest.php` | Validatie voor survey aanmaken/bewerken, inclusief vragen, opties en afbeeldingen. |
| `Surveys/StoreSurveyResponseRequest.php` | Validatie voor het invullen van een survey. Houdt rekening met verplichte vragen en verlopen surveys. |
| `Surveys/RequestParticipantMagicLinkRequest.php` | Validatie voor deelnemer-login via e-mail. Normaliseert e-mailadressen. |

## Policies en rollen

Policies staan in `app/Policies/`. Ze bepalen wat gebruikers mogen doen.

| Bestand | Functie |
| --- | --- |
| `UserPolicy.php` | Bepaalt of gebruikers andere gebruikers mogen beheren. |
| `SurveyPolicy.php` | Bepaalt of gebruikers surveys mogen bekijken in admincontext. |
| `SurveyResponsePolicy.php` | Bepaalt of gebruikers responses mogen bekijken of verwijderen. |
| `ParticipantPolicy.php` | Bepaalt of gebruikers deelnemers en punten mogen bekijken/corrigeren. |

Rollen staan in:

```text
app/Enums/Role.php
```

De gebruikte rollen zijn onder andere:

- `admin`
- `LICEmployee`
- `user`

De rollen worden gekoppeld via Spatie Laravel Permission. De database-tabellen daarvoor worden aangemaakt via de permission migrations in `database/migrations/`.

## Views

Views staan in `resources/views/`.

### Publieke survey views

Pad:

```text
resources/views/surveys/
```

Belangrijke bestanden:

| Bestand | Functie |
| --- | --- |
| `index.blade.php` | Publiek overzicht van beschikbare surveys. |
| `show.blade.php` | Pagina waarop deelnemers een survey invullen. |
| `participant-login.blade.php` | Magic link loginpagina voor deelnemers. |
| `thankyou.blade.php` | Bedankpagina na invullen. |
| `already-completed.blade.php` | Melding dat een deelnemer de survey al heeft ingevuld. |
| `expired.blade.php` | Melding dat een survey verlopen is. |
| `withdraw.blade.php` | Pagina om een inzending/toestemming in te trekken. |
| `withdraw-confirmed.blade.php` | Bevestiging na intrekken. |

### Surveybeheer views

Pad:

```text
resources/views/survey-manager/
```

Belangrijke bestanden:

| Bestand | Functie |
| --- | --- |
| `index.blade.php` | Overzicht van surveys voor beheer. |
| `create.blade.php` | Survey aanmaken. |
| `edit.blade.php` | Survey bewerken. |
| `_form.blade.php` | Gedeeld formulier voor create/edit. |

### Admin Livewire views

Pad:

```text
resources/views/pages/admin/
```

Bestanden met `⚡` zijn Livewire single-file components.

Belangrijke onderdelen:

- `surveys/⚡index.blade.php`
- `surveys/⚡show.blade.php`
- `responses/⚡show.blade.php`
- `participants/⚡index.blade.php`
- `participants/⚡show.blade.php`
- `users/⚡index.blade.php`
- `users/⚡create.blade.php`
- `users/⚡edit.blade.php`

### Auth views

Pad:

```text
resources/views/pages/auth/
```

Hier staan login, registratie, wachtwoord reset, e-mailverificatie en two-factor challenge views.

### Settings views

Pad:

```text
resources/views/pages/settings/
```

Hier staan profiel, wachtwoord, appearance en two-factor instellingen.

### Components

Pad:

```text
resources/views/components/
```

Hier staan herbruikbare Blade components, zoals:

- `app-logo.blade.php`
- `layout.blade.php`
- `desktop-user-menu.blade.php`
- `surveys/question-step.blade.php`
- `surveys/radio-answer.blade.php`
- `surveys/swipe-answer.blade.php`
- `surveys/textarea-answer.blade.php`
- `surveys/progress-bar.blade.php`

Als je iets visueels meerdere keren terugziet, staat het vaak in deze map.

## JavaScript

JavaScript staat in `resources/js/`.

| Bestand | Functie |
| --- | --- |
| `app.js` | Hoofdbestand voor frontend JavaScript. |
| `surveys/show.js` | Interactie tijdens het invullen van een survey, zoals stappen en antwoordvalidatie. |
| `surveys/manager-form.js` | Interactie in het surveybeheerformulier, zoals vragen toevoegen, opties beheren en afbeeldingen uploaden. |
| `surveys/manager-index.js` | Interactie op het surveybeheer-overzicht. |

Als gedrag in de browser aangepast moet worden, kijk dan meestal eerst in `resources/js/`.

## CSS en styling

CSS staat in `resources/css/`.

| Bestand | Functie |
| --- | --- |
| `app.css` | Hoofdstyling van de applicatie. |
| `components/buttons.css` | Herbruikbare button styling. |

Het project gebruikt Tailwind CSS. Veel styling staat daarom direct als utility classes in Blade views.

## Database

Databasebestanden staan in `database/`.

### Migrations

Pad:

```text
database/migrations/
```

Migrations beschrijven de databasestructuur. Belangrijke tabellen:

- `users`
- `surveys`
- `survey_questions`
- `survey_responses`
- `survey_answers`
- `participants`
- `participant_points_history`
- `contact_information_submissions`
- `survey_settings`
- rollen en permissions van Spatie Permission

### Factories

Pad:

```text
database/factories/
```

Factories worden vooral gebruikt in tests om snel testdata te maken.

Belangrijk:

- `UserFactory.php`
- `ParticipantFactory.php`
- `SurveyFactory.php`
- `SurveyQuestionFactory.php`

### Seeders

Pad:

```text
database/seeders/
```

Seeders vullen de database met standaarddata. `RoleSeeder.php` is belangrijk omdat rollen nodig zijn voor autorisatie.

## Mail

Mail classes staan in:

```text
app/Mail/
```

| Bestand | Functie |
| --- | --- |
| `ParticipantSurveyMagicLinkMail.php` | Mail met magic link voor deelnemers. |
| `SurveySubmissionConfirmationMail.php` | Bevestigingsmail na survey-inzending/contact delen. |

De mailtemplates staan in:

```text
resources/views/emails/surveys/
```

## Providers

Providers staan in `app/Providers/`.

| Bestand | Functie |
| --- | --- |
| `AppServiceProvider.php` | Algemene appconfiguratie, zoals defaults, serverless gedrag en Livewire compiler cache. |
| `FortifyServiceProvider.php` | Configuratie van Laravel Fortify: auth views, reset password action en rate limiting. |

## Configuratie

Configuratie staat in `config/`.

Belangrijke bestanden:

| Bestand | Functie |
| --- | --- |
| `app.php` | Algemene applicatie-instellingen. |
| `auth.php` | Guards en providers, waaronder `web` en `participant`. |
| `database.php` | Databaseverbindingen. |
| `filesystems.php` | Bestandsopslag. |
| `fortify.php` | Fortify/auth instellingen. |
| `mail.php` | Mailconfiguratie. |
| `permission.php` | Spatie Permission configuratie. |
| `surveys.php` | Survey-specifieke instellingen, zoals bewaartermijn. |

Lokale waarden staan in `.env`. Voorbeelden staan in `.env.example`.

## Vercel en serverless

Belangrijke bestanden:

| Bestand | Functie |
| --- | --- |
| `vercel.json` | Vercel configuratie, rewrites en PHP runtime. |
| `api/lambda.php` | Laravel entrypoint voor Vercel PHP runtime. |
| `api/blob-upload.js` | Upload endpoint voor Vercel Blob. |
| `.vercel/project.json` | Lokale koppeling met het Vercel project. |

Wanneer iets alleen online op Vercel misgaat, kijk dan vaak naar `vercel.json`, environment variables en de bestanden in `api/`.

## GitHub Actions

Pad:

```text
.github/workflows/
```

| Bestand | Functie |
| --- | --- |
| `lint.yml` | Draait Laravel Pint om code style te controleren. |
| `tests.yml` | Installeert dependencies, bouwt assets en draait tests. |

## Tests

Tests staan in `tests/`.

| Map | Functie |
| --- | --- |
| `tests/Feature/` | Test complete applicatieflows via routes, requests en database. |
| `tests/Unit/` | Test kleinere onderdelen zoals models, actions, policies en requests. |
| `tests/Pest.php` | Globale Pest configuratie en testhelpers. |
| `tests/TestCase.php` | Basis Laravel test case. |

Handige commands:

```bash
php artisan test --compact
php artisan test --compact --testsuite=Unit
php artisan test --compact --coverage --testsuite=Unit
```

## Waar begin je met zoeken?

| Je wilt... | Kijk eerst hier |
| --- | --- |
| Een URL aanpassen | `routes/web.php`, `routes/admin.php` of `routes/settings.php` |
| Survey invulflow aanpassen | `SurveyController.php`, `resources/views/surveys/`, `resources/js/surveys/show.js` |
| Surveybeheer aanpassen | `SurveyManagerController.php`, `resources/views/survey-manager/`, `resources/js/surveys/manager-form.js` |
| Adminpagina aanpassen | `routes/admin.php`, `resources/views/pages/admin/` |
| Rechten aanpassen | `app/Policies/`, `app/Enums/Role.php`, `database/seeders/RoleSeeder.php` |
| Validatie aanpassen | `app/Http/Requests/` |
| Databasekolommen aanpassen | `database/migrations/` en het bijbehorende model in `app/Models/` |
| Mail aanpassen | `app/Mail/` en `resources/views/emails/` |
| Styling aanpassen | Blade views, `resources/css/app.css`, `resources/css/components/` |
| JavaScriptgedrag aanpassen | `resources/js/` |
| Tests aanpassen of toevoegen | `tests/Feature/` en `tests/Unit/` |
| Deployment onderzoeken | `vercel.json`, `api/`, `.github/workflows/` |
| Config aanpassen | `config/` en `.env` |

## Belangrijke domeinen in de applicatie

### Surveys

Surveys bestaan uit:

- `Survey`
- `SurveyQuestion`
- `SurveyResponse`
- `SurveyAnswer`

De publieke flow loopt vooral via `SurveyController`. Beheer loopt via `SurveyManagerController` en admin Livewire pages.

### Deelnemers

Deelnemers gebruiken geen normale gebruikerslogin. Zij loggen in via magic link en de `participant` guard.

Belangrijke bestanden:

- `Participant.php`
- `ParticipantSurveyAuthController.php`
- `resources/views/surveys/participant-login.blade.php`
- `config/auth.php`

### Punten

Punten worden opgeslagen bij deelnemers en in de puntenhistorie.

Belangrijke bestanden:

- `Participant.php`
- `ParticipantPointsHistory.php`
- `DeductParticipantPoints.php`
- `StudentPointsController.php`
- `resources/views/student/points.blade.php`

### Contactgegevens

Contactgegevens worden apart opgeslagen en versleuteld.

Belangrijke bestanden:

- `ContactInformationSubmission.php`
- `SurveyController::allowContact`
- `resources/views/surveys/thankyou.blade.php`

### Retention en opschonen

Oude surveygegevens kunnen worden opgeschoond.

Belangrijke bestanden:

- `SurveyRetentionSettings.php`
- `DeleteSurveySubmission.php`
- `PruneSurveyAnswers.php`
- `SurveySetting.php`

## Praktische start voor nieuwe ontwikkelaars

1. Lees `README.md` voor installatie en lokale setup.
2. Lees `docs/devops.md` voor deployment, testing en monitoring.
3. Gebruik dit document om bestanden te vinden.
4. Start bij routes om te begrijpen welke URL naar welke code gaat.
5. Zoek daarna de controller, view en tests die bij die route horen.
6. Draai na wijzigingen minimaal de relevante tests.

Een goede vuistregel: routes vertellen waar een request binnenkomt, controllers/actions bepalen wat er gebeurt, models bepalen welke data wordt gebruikt, views bepalen wat de gebruiker ziet, en tests bewijzen dat het blijft werken.
