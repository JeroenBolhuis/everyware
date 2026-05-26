# DevOps binnen Everyware

Dit document beschrijft welke DevOps-onderdelen binnen dit project worden gebruikt en waarom ze belangrijk zijn. DevOps gaat hier niet alleen over het online zetten van de applicatie, maar ook over versiebeheer, kwaliteit, testen, monitoring, omgevingsbeheer en het betrouwbaar samenwerken in een team.

## Git en GitHub

Git wordt gebruikt voor versiebeheer. Elke wijziging in de code wordt opgeslagen als commit, waardoor het team altijd kan terugzien wat er is aangepast, door wie en waarom. Dit maakt samenwerken veiliger, omdat fouten teruggedraaid kunnen worden en teamleden tegelijk aan verschillende onderdelen kunnen werken.

GitHub wordt gebruikt als centrale plek waar de repository staat. Teamleden pushen hun wijzigingen naar GitHub en kunnen via branches gescheiden werken aan nieuwe functionaliteiten, bugfixes of experimenten. Een gebruikelijke werkwijze is dat nieuwe code eerst op een aparte branch wordt ontwikkeld en pas na controle wordt samengevoegd met `develop` of `main`.

Deze aanpak sluit aan op de 12 factor app principes. Code, configuratie en deployment worden van elkaar gescheiden. Gevoelige instellingen, zoals databasegegevens, mailgegevens en tokens, staan niet hardcoded in de applicatie maar worden via environment variables ingesteld. Daardoor kan dezelfde codebase worden gebruikt voor lokaal ontwikkelen, testen, preview deployments en productie.

## Branches en omgevingen

Het project maakt onderscheid tussen meerdere omgevingen:

- Lokaal: voor ontwikkelen op de eigen computer.
- Testing: voor automatische checks in GitHub Actions.
- Preview: voor het testen van een gedeployde versie voordat deze naar productie gaat.
- Production: de live omgeving voor echte gebruikers.

Branches helpen om deze omgevingen gescheiden te houden. Een feature branch kan bijvoorbeeld automatisch of handmatig naar een preview omgeving worden gedeployed. De productieomgeving wordt alleen bijgewerkt wanneer de versie voldoende getest is.

## Vercel deployment

Vercel wordt gebruikt om de Laravel-applicatie online te zetten. In `vercel.json` staat hoe Vercel de applicatie moet behandelen. De PHP-entrypoint loopt via `api/lambda.php` met `vercel-php`, en alle normale routes worden via rewrites naar deze PHP runtime gestuurd.

Voor deployment kunnen de volgende commando's worden gebruikt:

```bash
vercel
```

Dit maakt een preview deployment. Een preview deployment is handig om een wijziging online te testen zonder de productieomgeving aan te passen.

```bash
vercel --prod
```

Dit zet de applicatie naar productie. Dit commando hoort pas gebruikt te worden wanneer de code getest is en klaar is voor echte gebruikers.

Vercel biedt ook rollback- en inspectiemogelijkheden. Als een productie deployment problemen geeft, kan een eerdere werkende deployment opnieuw actief worden gemaakt. Daarnaast kunnen logs bekeken worden om fouten in serverless functions te onderzoeken.

## Buildproces

Voor de frontend wordt Vite gebruikt. De assets worden gebouwd met:

```bash
npm run build
```

In de Vercel-configuratie wordt ook een build uitgevoerd. Hierdoor worden CSS, JavaScript en andere frontend assets klaargezet voor de online omgeving. Dit voorkomt dat alleen de PHP-code wordt gedeployed terwijl de frontend-bestanden ontbreken of verouderd zijn.

## Supabase database

Supabase wordt gebruikt als online database voor gedeelde en gedeployde omgevingen. In de praktijk betekent dit dat teamleden en preview/productieomgevingen niet afhankelijk zijn van een lokale database op iemands laptop.

De applicatie gebruikt database-instellingen via environment variables, zoals `DB_CONNECTION`, `DB_HOST`, `DB_DATABASE`, `DB_USERNAME` en `DB_PASSWORD`. Hierdoor kan lokaal bijvoorbeeld een andere database gebruikt worden dan online.

Supabase helpt ook bij monitoring van databasegebruik. Denk aan inzicht in:

- hoeveel queries er worden uitgevoerd;
- welke tabellen veel gebruikt worden;
- of er fouten optreden bij databaseverbindingen;
- hoeveel opslagruimte de database gebruikt;
- of queries traag worden.

Deze inzichten zijn belangrijk omdat databaseproblemen vaak pas zichtbaar worden wanneer meerdere gebruikers tegelijk met de applicatie werken.

## Vercel Blob

Vercel Blob wordt gebruikt voor het opslaan van geuploade afbeeldingen in de online omgeving. In dit project is dat vooral relevant voor afbeeldingen die bij enquete-opties worden toegevoegd.

Lokaal kan bestandsopslag via de normale Laravel storage werken, maar in een serverless omgeving zoals Vercel is lokale opslag niet betrouwbaar blijvend. Een serverless function kan namelijk tijdelijk zijn. Daarom worden uploads online naar Vercel Blob gestuurd.

In het project is hiervoor een aparte endpoint aanwezig:

```text
/api/blob-upload
```

Deze route wordt in `vercel.json` doorgestuurd naar `api/blob-upload.js`. De frontend gebruikt deze uploadroute wanneer de applicatie op Vercel draait en `BLOB_READ_WRITE_TOKEN` beschikbaar is. De upload levert daarna een publieke Blob URL op die in de enquete-opties kan worden opgeslagen.

Hiermee wordt voorkomen dat afbeeldingen verdwijnen na een nieuwe deployment of serverless herstart.

## Mailtrap

Mailtrap wordt gebruikt voor het testen en versturen van e-mails. In `.env.example` staat dat SMTP via Mailtrap gebruikt kan worden met instellingen zoals `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME` en `MAIL_PASSWORD`.

Mailtrap is nuttig omdat e-mails eerst veilig getest kunnen worden zonder dat ze per ongeluk bij echte gebruikers terechtkomen. In dit project zijn e-mails bijvoorbeeld relevant voor:

- magic links voor deelnemers;
- bevestigingsmails na het invullen van een enquete;
- mogelijke toekomstige notificaties.

Daarnaast geeft Mailtrap inzicht in verzonden berichten. Er kan gecontroleerd worden of:

- de mail daadwerkelijk verstuurd is;
- de onderwerpregel klopt;
- de juiste ontvanger wordt gebruikt;
- de inhoud correct wordt weergegeven;
- er fouten optreden bij aflevering.

Dit maakt Mailtrap niet alleen een testtool, maar ook een lichte vorm van monitoring voor mailstromen.

## Geautomatiseerde tests met Pest en PHPUnit

Pest en PHPUnit worden gebruikt voor automatische tests. Pest is de testlaag waarmee de tests geschreven worden; PHPUnit is de onderliggende test runner waarop Pest draait.

De tests controleren of belangrijke onderdelen van de applicatie blijven werken na wijzigingen. Denk aan:

- modellen en relaties;
- policies en rechten;
- form requests en validatie;
- survey flows;
- exports;
- mailables;
- acties zoals verwijderen, punten toekennen en wachtwoorden resetten.

De tests gebruiken in `phpunit.xml` een SQLite in-memory database. Daardoor zijn tests snel en hebben ze geen aparte MySQL- of Supabase-testdatabase nodig.

Een test-run kan lokaal worden uitgevoerd met:

```bash
php artisan test --compact
```

Voor unit tests met coverage:

```bash
php artisan test --compact --coverage --testsuite=Unit
```

Coverage laat zien welk percentage van de applicatiecode door tests wordt geraakt. Dat is geen perfecte kwaliteitsmeter, maar het helpt wel om ongeteste gebieden zichtbaar te maken.

## GitHub Actions

GitHub Actions voert automatische controles uit wanneer code naar GitHub wordt gepusht of wanneer er een pull request wordt gemaakt.

In dit project zijn er twee belangrijke workflows:

- `.github/workflows/lint.yml`
- `.github/workflows/tests.yml`

De test workflow installeert PHP, Node en Composer dependencies, bouwt de frontend assets en draait daarna Pest. De workflow draait met meerdere PHP-versies, zodat sneller zichtbaar wordt of de applicatie alleen op een specifieke PHP-versie werkt.

De lint workflow draait de code style controle. Daardoor hoeft het team niet handmatig te controleren of iedereen dezelfde code-opmaak gebruikt.

GitHub Actions fungeert hiermee als quality gate. Code die lokaal lijkt te werken, moet ook in een schone CI-omgeving door de checks komen.

## Linting

Linting is toegevoegd om de code consistent en leesbaar te houden. In dit project wordt voor PHP Laravel Pint gebruikt.

Pint controleert en corrigeert onder andere:

- consistente inspringing;
- spaties rond operators;
- correcte plaatsing van accolades;
- consistente array formatting;
- volgorde en opschoning van imports;
- Laravel/PHP code style conventies;
- overbodige witregels;
- consistente formatting van methodes, classes en closures.

Linting controleert dus vooral de vorm van de code. Het controleert niet of de functionaliteit inhoudelijk klopt. Daarvoor zijn tests nodig.

De lint command staat in `composer.json`:

```bash
composer lint
```

Deze voert Pint uit:

```bash
pint --parallel
```

Voor CI is er ook:

```bash
composer lint:check
```

Deze controleert of de code goed geformatteerd is. In een team voorkomt dit discussies over code-opmaak, omdat de formatter bepaalt wat de standaard is.

## Vercel Analytics

Vercel Analytics is toegevoegd om inzicht te krijgen in het gebruik van belangrijke pagina's en onderdelen van de applicatie. In dit project wordt de analytics script partial ingeladen via:

```text
resources/views/partials/vercel-analytics.blade.php
```

Deze partial wordt opgenomen in de head/layout van de applicatie. Daardoor kan Vercel pageviews meten zonder dat er veel extra code nodig is.

Analytics wordt gebruikt om vragen te beantwoorden zoals:

- welke pagina's worden het meest bezocht;
- komen gebruikers op de enquete-overzichtspagina;
- worden gedeelde enquete-links gebruikt;
- hoeveel verkeer komt op de loginpagina voor deelnemers;
- welke routes worden bijna nooit gebruikt;
- op welke pagina's haken gebruikers mogelijk af.

Voor dit project zijn vooral de volgende pagina's interessant:

- `/surveys`
- `/survey/deelnemer/inloggen`
- `/survey/{survey}`
- `/s/{token}`
- `/student/punten`
- admin- en enquetebeheerpagina's

Op basis van analytics kan het team betere keuzes maken. Als bijvoorbeeld veel deelnemers wel de loginpagina bereiken maar weinig mensen een enquete afronden, kan dat wijzen op onduidelijke uitleg, een technisch probleem of een te lang formulier.

In een uitgebreidere versie kan het project ook custom events meten, bijvoorbeeld:

- `survey_started`
- `survey_completed`
- `contact_details_allowed`
- `magic_link_requested`
- `export_downloaded`

Daarmee wordt niet alleen gemeten welke pagina's bezocht worden, maar ook welke belangrijke acties gebruikers uitvoeren.

## Monitoring en logs

Naast analytics zijn logs belangrijk. Analytics laat vooral gebruikersgedrag zien, terwijl logs technische fouten zichtbaar maken.

Voor Laravel kunnen logs lokaal met Laravel Pail worden bekeken. In het `composer dev` script draait `php artisan pail`, zodat fouten tijdens ontwikkeling meteen zichtbaar zijn.

Op Vercel kunnen runtime logs gebruikt worden om serverless fouten te onderzoeken. Denk aan:

- foutmeldingen bij uploads;
- databaseverbindingsproblemen;
- mislukte mailverzendingen;
- 500-errors;
- timeouts in serverless functions.

Een handige werkwijze is om na een productie deployment kort de logs te controleren. Als er direct errors verschijnen, kan het team snel ingrijpen voordat gebruikers er veel last van hebben.

## Docker en Laravel Sail

Docker wordt gebruikt om lokaal in een vaste ontwikkelomgeving te werken. Laravel Sail maakt dit voor Laravel eenvoudiger.

Het voordeel hiervan is dat teamleden niet allemaal handmatig dezelfde PHP-, Node-, database- en extensieversies hoeven te installeren. De omgeving wordt beschreven in Docker-configuratie, waardoor iedereen de applicatie op ongeveer dezelfde manier kan draaien.

Dit voorkomt problemen zoals:

- "bij mij werkt het wel";
- andere PHP-versies;
- ontbrekende extensies;
- verschillende databaseversies;
- afwijkende lokale instellingen.

Docker helpt ook om de lokale testomgeving dichter bij de deployment omgeving te houden. Helemaal identiek is het niet, omdat Vercel serverless werkt, maar het verkleint wel de verschillen.

## Environment variables en secrets

Gevoelige instellingen worden via environment variables beheerd. Voorbeelden zijn:

- databasegegevens;
- Mailtrap wachtwoorden;
- Vercel Blob tokens;
- applicatiesleutels;
- externe API keys;
- Flux credentials voor private dependencies.

Deze waarden horen niet in Git te staan. In GitHub Actions worden secrets gebruikt, bijvoorbeeld voor Flux credentials. In Vercel worden productie- en previewwaarden via het Vercel dashboard of de CLI ingesteld.

Dit is belangrijk voor veiligheid en flexibiliteit. Dezelfde code kan zo draaien met andere instellingen per omgeving.

## Security en toegangscontrole

DevOps raakt ook security. In dit project wordt security onder andere ondersteund door:

- gescheiden secrets per omgeving;
- GitHub Actions checks voordat code wordt samengevoegd;
- Laravel policies en rollen;
- tests voor rechten en policies;
- geen hardcoded wachtwoorden of tokens in de code;
- aparte participant guard voor deelnemers;
- signed magic links voor deelnemerstoegang.

Voor productie is het belangrijk dat debug-instellingen uit staan, secrets niet gelekt worden en database- en mailaccounts beperkte rechten hebben.

## Deployment workflow

Een gezonde workflow voor dit project is:

1. Ontwikkelaar maakt een branch.
2. Code wordt lokaal getest.
3. Linting wordt lokaal of via GitHub Actions uitgevoerd.
4. Tests draaien via GitHub Actions.
5. Er wordt een preview deployment gemaakt op Vercel.
6. De preview wordt functioneel gecontroleerd.
7. Bij akkoord wordt naar productie gedeployed.
8. Na deployment worden logs en analytics gecontroleerd.

Deze stappen verkleinen de kans dat kapotte code direct bij gebruikers terechtkomt.

## Waarom deze DevOps-aanpak waardevol is

De combinatie van GitHub, GitHub Actions, Vercel, Supabase, Mailtrap, Vercel Blob, analytics, linting, tests en Docker zorgt ervoor dat het project betrouwbaarder wordt.

Het team krijgt hierdoor:

- betere samenwerking;
- herhaalbare deployments;
- automatische kwaliteitscontroles;
- minder afhankelijkheid van lokale machines;
- inzicht in fouten;
- inzicht in gebruikersgedrag;
- veiligere omgang met secrets;
- sneller vertrouwen bij wijzigingen.

DevOps is daarmee geen los onderdeel naast de applicatie, maar een manier om de applicatie continu veilig, testbaar en onderhoudbaar te houden.
