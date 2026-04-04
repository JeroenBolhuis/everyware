# Mailing Service En Privacy Architectuur

## Doel

Deze handleiding beschrijft hoe je in dit project een aparte mailingservice kunt ontwerpen voor de user story:

> Als student wil ik na het invullen van de enquete een bevestigingsmail ontvangen wanneer ik mijn e-mail heb achtergelaten, zodat ik weet dat mijn feedback succesvol is verzonden.

Acceptatiecriteria:

1. Na het verzenden van de enquete wordt een bevestigingspagina getoond.
2. Wanneer een student een e-mailadres heeft ingevuld, wordt automatisch een bevestigingsmail verstuurd.

Deze handleiding bevat bewust **geen code**. Het document is bedoeld als stap-voor-stap implementatieplan en architectuurbesluit.

## Vastgelegde Keuzes

Voor de eerste implementatie zijn de volgende keuzes nu expliciet vastgelegd:

1. De bestaande architectuur blijft bestaan en wordt niet verwijderd.
2. De mailingservice komt **in deze repository**.
3. De mailingservice gebruikt **MySQL**.
4. Er komen **2 nieuwe tabellen** en **geen aparte databases** in deze fase.
5. `naam` en `email` worden opgeslagen omdat ze functioneel nodig zijn voor de bevestigingsmail.
6. De bevestigingsmail wordt verstuurd **op het moment dat de student op submit klikt**.
7. Daarna navigeert de student naar de bevestigingspagina.
8. Voor de eerste versie wordt een praktische mailprovider gekozen die snel integreert.

## Uitgangspunt Voor Deze Handleiding

Deze handleiding gaat uit van een **uitbreiding** van de huidige architectuur.

Dat betekent:

- bestaande surveyflow blijft bestaan;
- bestaande tabellen en routes hoeven niet direct verwijderd te worden;
- de mailingservice wordt **naast** de huidige architectuur gebouwd;
- bestaande opslag kan voorlopig blijven bestaan voor compatibiliteit, zolang nieuwe functionaliteit op de nieuwe service wordt aangesloten;
- eventuele opschoning of uitfasering is een **latere** stap en geen voorwaarde voor de eerste implementatie.

## Belangrijkste Conclusie

Voor dit project is een **aparte mailingservice** verdedigbaar als privacy een belangrijk doel is.

Maar:

- `3 complete databases + een aparte microservice` is voor alleen deze user story waarschijnlijk **meer dan nodig**.
- `2 tabellen in dezelfde MySQL-database` is voor een MVP **werkbaar**, maar privacy-technisch minder sterk dan een latere opsplitsing.

### Aanbevolen MVP

Gebruik:

1. De bestaande hoofdapp voor enquetes en responses.
2. Een **aparte mailingservice** als los deploybare service.
3. **Een MySQL-database** binnen de bestaande omgeving.
4. **Twee nieuwe tabellen** voor de mailingservice:
   - `mail_recipients`
   - `mail_delivery_requests`

Waarom dit nu de beste balans is:

- surveydata blijft gescheiden van identificeerbare gegevens;
- de mailinglogica komt apart van de bestaande surveylogica te staan;
- de complexiteit blijft beheersbaar;
- deze opzet kan later nog naar extra tabellen of aparte databases worden opgesplitst.

Belangrijke nuance:

- deze MVP is sneller te bouwen;
- deze MVP is **minder strikt** dan een model met aparte alias-, link- en PII-opslag;
- als privacy-eisen later strenger worden, kun je de 2-tabellen-opzet migreren naar een verder gesplitst model.

## Wat Er Nu In Dit Project Gebeurt

De huidige situatie is belangrijk, want die bepaalt wat moet veranderen:

- de enquete-response wordt opgeslagen in `survey_responses`;
- op de bedankpagina kan de student optioneel contactgegevens invullen;
- die contactgegevens worden nu **gehasht** opgeslagen in `contact_information_submissions`;
- gehashte e-mailadressen kun je **niet** gebruiken om later een bevestigingsmail te sturen.

Dat betekent:

- hashing is prima voor opslag zonder hergebruik;
- hashing is **niet geschikt** voor een mailingflow;
- voor mailen heb je tijdelijk of opgeslagen toegang nodig tot de echte e-mail in plaintext of versleutelde vorm.

## Privacy-Uitgangspunt

Pseudonimisering is **geen anonimisering**. Gepseudonimiseerde data blijft persoonsgegevensdata als de koppeling nog ergens bestaat.

Daarom moet je ontwerpen volgens dit principe:

1. De hoofdapp mag geen echte naam en e-mail blijvend opslaan als dat niet nodig is.
2. De mailingservice mag alleen toegang hebben tot de minimale gegevens die nodig zijn om een mail te sturen.
3. De pseudonieme referentie moet los bruikbaar zijn zonder dat overal direct naam en e-mail zichtbaar zijn.
4. Tabellen met echte naam en e-mail moeten versleuteld en streng beperkt toegankelijk zijn.

## Aanbevolen Architectuur

### Hoofdapp

De huidige Laravel-app blijft verantwoordelijk voor:

- tonen van de enquete;
- opslaan van survey responses;
- versturen van de submit;
- tonen van de bevestigingspagina;
- aanroepen van de mailingservice.

De hoofdapp bewaart voor de **nieuwe mailingflow** bij voorkeur alleen:

- `survey_response_id`
- `survey_id`
- een `student_pseudonym` of `mail_recipient_alias`
- statusinformatie zoals `mail_requested_at`, `mail_status`

Bestaande opslag in de hoofdapp hoeft in deze fase **niet direct verwijderd** te worden. Het advies is vooral dat nieuwe mailfunctionaliteit in de nieuwe service wordt ondergebracht.

### Aparte Mailingservice

De mailingservice wordt een los deploybare interne service binnen dezelfde organisatie, maar buiten de hoofdapp.

De mailingservice is verantwoordelijk voor:

- ontvangen van een intern mailverzoek tijdens de submit-flow;
- genereren of accepteren van een pseudoniem;
- opslaan van identiteit en verzendstatus;
- versturen van de bevestigingsmail;
- loggen van verzendstatus;
- verwerken van verwijder- of intrekverzoeken.

### Datamodellen

#### Tabel 1: `mail_recipients`

Doel:
- de ontvanger en zijn pseudonieme referentie opslaan.

Aanbevolen velden:
- `id`
- `pseudonym_uuid`
- `survey_response_id`
- `survey_id`
- `full_name_encrypted`
- `email_encrypted`
- `email_hash`
- `consent_source`
- `created_at`
- `updated_at`

Belangrijk:

- naam en e-mail worden encrypted opgeslagen;
- `email_hash` kan gebruikt worden voor deduplicatie of technische checks;
- deze tabel combineert in de MVP pseudonieme en identiteitsgegevens die later nog verder opgesplitst kunnen worden.

#### Tabel 2: `mail_delivery_requests`

Doel:
- de mailverzoeken en verzendstatus opslaan.

Aanbevolen velden:
- `id`
- `mail_recipient_id`
- `pseudonym_uuid`
- `survey_response_id`
- `survey_id`
- `mail_template`
- `mail_status`
- `provider`
- `provider_message_id`
- `mail_requested_at`
- `mail_sent_at`
- `mail_failed_at`
- `failure_reason`
- `created_at`
- `updated_at`

Deze tabel bevat geen plaintext PII, maar wel workflow- en providerinformatie.

## Waarom Deze 2-Tabellen-MVP Verdedigbaar Is

Deze keuze is eenvoudiger en sneller te bouwen:

- minder migraties;
- minder databaseverbindingen;
- minder deployment-complexiteit;
- sneller te koppelen aan de bestaande flow.

Maar privacy-technisch is dit wel zwakker dan verdere scheiding:

- een enkele databasecompromis legt meteen de hele keten bloot;
- back-ups bevatten pseudoniem en identiteit dichter bij elkaar;
- toegangsrechten moeten extra zorgvuldig worden ingericht;
- verdere opsplitsing blijft later wenselijk als privacy-eisen toenemen.

## Servicegrens En Communicatie

Laat de hoofdapp en mailingservice **niet** dezelfde tabellen delen.

Aanbevolen patroon:

1. Student verstuurt enquete.
2. Hoofdapp valideert de submit inclusief naam en e-mail.
3. Hoofdapp slaat enquete en antwoorden op.
4. Hoofdapp doet tijdens dezelfde flow een **interne API-call** naar de mailingservice.
5. De mailingservice slaat de ontvanger op in `mail_recipients`.
6. De mailingservice maakt een record in `mail_delivery_requests`.
7. De mailingservice verstuurt direct de bevestigingsmail.
8. Daarna navigeert de student naar de bevestigingspagina.

Belangrijke nuance:

- dit volgt jouw gewenste gedrag precies;
- het maakt de submit-flow wel gevoeliger voor timeouts van de mailprovider;
- later kun je dit desgewenst omzetten naar async verzending met dezelfde tabellen.

## Aanbevolen Productbeslissing

Hier zit een belangrijk functioneel punt.

Op dit moment vult de student contactgegevens pas in op de bevestigingspagina. Dat past niet meer bij jouw gekozen flow.

Als de mail meteen na submit moet worden verzonden, dan moet het e-mailadres al **in de enquete zelf** worden gevraagd of als laatste stap van hetzelfde submitproces.

### Mijn advies

Hou voor nu de flow simpel:

1. Student vult enquete in.
2. Student vult naam en e-mail in als onderdeel van dezelfde submit-flow.
3. Student klikt op submit.
4. Hoofdapp slaat surveydata op.
5. Hoofdapp roept de mailingservice aan.
6. Mailingservice slaat de gegevens op en verstuurt direct de mail.
7. Daarna wordt de bevestigingspagina getoond.

## Implementatiestappen

### Stap 1: Maak eerst een privacybesluit

Leg vast:

- welke gegevens echt nodig zijn;
- waarom naam nodig is of juist niet;
- of e-mail voldoende is voor deze story;
- hoe lang je persoonsgegevens bewaart;
- hoe een student intrekking of verwijdering kan vragen.

Aanbeveling:

- in deze fase worden zowel `full_name` als `email` opgeslagen omdat dat jouw expliciete keuze is;
- als later blijkt dat naam niet nodig is, kan dit nog worden versmald.

### Stap 2: Voeg een nieuwe PII-stroom toe naast de hoofdapp

In de hoofdapp mag de bestaande surveydata en bestaande opslag voorlopig blijven staan. Voor de nieuwe mailingfunctionaliteit voeg je een **nieuwe, gescheiden verwerkingsstroom** toe.

Richtlijn:

- gebruik in de hoofdapp voor de nieuwe flow alleen een tijdelijke request of beperkte statusopslag;
- sla voor de nieuwe flow in de hoofdapp alleen alias en status op waar mogelijk;
- laat de mailingservice de nieuwe plek zijn waar mailgerelateerde identiteit wordt beheerd;
- behandel bestaande opslag als legacy of overgangslaag, niet als iets dat je meteen moet verwijderen.

### Stap 3: Kies het identity-model

Genereer voor elke contactinzending:

- een `pseudonym_uuid`

Regel:

- `pseudonym_uuid` mag in logs en in de hoofdapp voorkomen;
- naam en e-mail blijven alleen in encrypted vorm in de mailingservice-tabellen;
- als je later verder splitst, kan het identity-model alsnog worden opgesplitst naar extra tabellen.

### Stap 4: Maak de mailingservice echt apart

Binnen dit project raad ik het volgende aan:

- houd de huidige app in de projectroot;
- maak een aparte service in bijvoorbeeld `services/mailer-service/`;
- geef die service een eigen `.env`, eigen mailconfig en eigen service-secret;
- laat de service verbinden met dezelfde MySQL-server of dezelfde applicatiedatabase, maar wel met eigen tabellen;
- gebruik aparte service accounts en aparte secrets waar mogelijk.

## Stap 5: Definieer een interne API

Minimaal nodig:

- endpoint om een mailrequest aan te maken;
- endpoint om verzendstatus op te vragen;
- endpoint om recipient-data te verwijderen of te revoken;
- endpoint om een resend alleen gecontroleerd toe te staan.

Verstuur vanuit de hoofdapp alleen wat nodig is:

- `survey_response_id`
- `survey_id`
- `email`
- optioneel `full_name`
- context zoals taal/template

De hoofdapp hoeft daarna alleen de `alias_uuid` en status terug te krijgen.

## Stap 6: Gebruik encryptie, niet alleen hashing

Voor e-mailverzending moet de mailingservice de e-mail kunnen lezen.

Daarom:

- sla `email` versleuteld op;
- sla daarnaast een hash op voor controle of deduplicatie;
- sla de sleutel niet in dezelfde tabel op;
- beperk welke service de decryptiesleutel mag gebruiken.

Niet doen:

- alleen bcrypt-hashes bewaren en verwachten dat je daarmee nog kunt mailen;
- plaintext e-mails in app-logs of queue payloads laten staan;
- brede database-accounts gebruiken als een smallere set rechten voldoende is.

## Stap 7: Bouw een veilige verzendflow

Aanbevolen flow:

1. Hoofdapp ontvangt naam/e-mail van student.
2. Hoofdapp maakt een intern request naar de mailingservice via HTTPS.
3. Mailingservice valideert input.
4. Mailingservice maakt `pseudonym_uuid`.
5. Mailingservice versleutelt naam/e-mail en slaat die op in `mail_recipients`.
6. Mailingservice maakt een record aan in `mail_delivery_requests`.
7. Mailingservice verstuurt direct de bevestigingsmail via de gekozen provider.
8. Mailingservice werkt de verzendstatus bij.
9. Hoofdapp redirect daarna naar de bevestigingspagina.

## Stap 8: Koppel aan de bestaande surveyflow

In deze codebase moet je rekening houden met de huidige opzet:

- survey submit gebeurt in de `SurveyController`;
- contactgegevens worden nu op de bedankpagina verwerkt;
- contactgegevens worden nu gehasht opgeslagen.

De veiligste functionele wijziging zonder bestaande architectuur te verwijderen is:

1. Laat de survey submit en thank-you flow bestaan.
2. Verplaats naam/e-mail voor de nieuwe flow van de bedankpagina naar de submit-flow.
3. Voeg naast de huidige opslag een call naar de mailingservice toe voor nieuwe mailfunctionaliteit.
4. Laat de hoofdapp daarnaast onthouden dat er contact is gedeeld en wat de alias/status is.
5. Houd de withdraw-flow in stand door ook een delete of revoke richting mailingservice te sturen.
6. Evalueer pas later of de oude hash-opslag kan worden uitgefaseerd.

## Stap 9: Maak verwijdering en intrekking onderdeel van het ontwerp

Omdat dit studentdata is, moet verwijdering geen nagedachte zijn.

Regels:

- verwijder of revoke recipient-data wanneer de student zijn response intrekt;
- verwijder identity-data na de bewaartermijn;
- verwijder mailprovider logs waar mogelijk volgens bewaarbeleid;
- documenteer wie verwijdering mag uitvoeren.

## Stap 10: Logging En Monitoring

Log alleen:

- `pseudonym_uuid`
- verzendstatus
- template-id
- timestamps

Log nooit:

- plaintext e-mail
- volledige naam
- volledige request bodies met PII

## Stap 11: Toegang En Rechten

Gebruik strikt least privilege:

- hoofdapp mag geen brede leesrechten hebben op gevoelige mailtabellen als dat niet nodig is;
- mailingservice worker mag alleen decrypten wat nodig is voor verzending;
- admins van de hoofdapp mogen niet automatisch bij PII kunnen;
- back-uptoegang moet apart worden geregeld.

## Stap 12: Teststrategie

Test minimaal:

1. submit zonder e-mail -> bevestigingspagina, geen mailrequest.
2. submit met e-mail -> mail wordt tijdens submit verstuurd en daarna verschijnt de bevestigingspagina.
3. mailprovider down -> foutafhandeling is duidelijk en duplicaten worden voorkomen.
4. intrekking response -> recipient-data wordt volgens beleid verwijderd of gedeactiveerd.
5. logs bevatten geen plaintext PII.

## MVP Tegenover Later

### MVP die ik verantwoord vind

- aparte mailingservice;
- 1 MySQL-opslag;
- 2 tabellen;
- interne API;
- encrypted opslag van identiteit;
- directe mailverzending tijdens submit;
- revoke/delete-flow;
- minimale logging.

### Waarschijnlijk te veel voor nu

- event bus met Kafka of RabbitMQ;
- aparte databases voor een eerste versie;
- meerdere microservices naast alleen de mailservice;
- complexe distributed transactions;
- centrale identity graph of data mesh.

## Concrete Aanbeveling Voor Dit Project

Als je dit in dit project "het beste" wilt doen, zou ik kiezen voor:

1. **Behoud de huidige Laravel survey-app als monoliet voor de enquete.**
2. **Voeg een aparte interne mailingservice toe als los deploybare service.**
3. **Gebruik 2 nieuwe tabellen in MySQL voor de eerste versie, zodat dit later nog kan worden opgesplitst.**
4. **Sla echte naam en e-mail encrypted op in de mailingservice-tabellen.**
5. **Gebruik in de hoofdapp voor de nieuwe flow vooral alias en status, zonder bestaande structuur direct te hoeven verwijderen.**
6. **Verstuur de mail direct tijdens submit en redirect daarna naar de bevestigingspagina.**
7. **Koppel de bestaande withdraw-flow aan revoke/delete in de mailingservice.**

## Aanbevolen Mailprovider

Voor deze eerste implementatie raad ik **Resend via SMTP** aan.

Waarom:

- snel te koppelen aan een Laravel/PHP-mailflow;
- weinig infrastructuur nodig;
- eenvoudig te begrijpen credentials;
- later vervangbaar door bijvoorbeeld SES als jullie verder opschalen.

Wat Resend volgens de officiële documentatie nodig heeft:

- een API key;
- een geverifieerd domein;
- SMTP host `smtp.resend.com`;
- gebruikersnaam `resend`;
- wachtwoord = je API key;
- poorten zoals `465` of `587`.

Voor deze implementatie zou ik voor de MVP `465` gebruiken.

## Wat Jij Nog Moet Regelen Voor De Provider

Voor Resend heb ik van jou of van de organisatie nog het volgende nodig:

1. Een Resend-account.
2. Een geverifieerd afzenderdomein of subdomein, bijvoorbeeld `mail.jouwdomein.nl`.
3. Toegang tot DNS om de verificatierecords te zetten.
4. Een API key met send-rechten.
5. Een gewenste afzendernaam, bijvoorbeeld `Everyware`.
6. Een gewenst afzenderadres, bijvoorbeeld `noreply@mail.jouwdomein.nl`.
7. Eventueel een reply-to-adres als reacties niet naar `noreply` moeten gaan.
8. Een testadres waarmee ik de flow veilig kan controleren.

## Wat Ik Hierna Nog Van Jou Nodig Heb

Voordat ik deze service echt kan bouwen, heb ik nog deze concrete gegevens nodig:

1. De naam van het afzenderdomein of subdomein.
2. De uiteindelijke `from name`.
3. De uiteindelijke `from email`.
4. De Resend API key zodra die is aangemaakt.
5. De bewaartermijn voor opgeslagen naam en e-mail.
6. Of replies op de bevestigingsmail ergens moeten uitkomen.

Zonder die gegevens kan ik de structuur en veel code wel bouwen, maar niet de providerconfig volledig werkend opleveren.

## Implementatiestatus In Deze Repo

De eerste implementatie is nu voorbereid in deze repository.

De belangrijkste onderdelen staan op deze plekken:

- interne mailingservice: `app/Services/MailerService/SurveyConfirmationService.php`
- mailable: `app/Mail/SurveySubmissionConfirmationMail.php`
- mailtabellen:
  - `database/migrations/2026_04_03_120000_create_mail_recipients_table.php`
  - `database/migrations/2026_04_03_120100_create_mail_delivery_requests_table.php`
- modellen:
  - `app/Models/MailRecipient.php`
  - `app/Models/MailDeliveryRequest.php`
- submit-flow in de enquete:
  - `app/Http/Controllers/SurveyController.php`
  - `app/Http/Requests/Surveys/StoreSurveyResponseRequest.php`
  - `resources/views/surveys/show.blade.php`
  - `resources/js/surveys/show.js`
- bevestigingspagina:
  - `resources/views/surveys/thankyou.blade.php`
- mailconfig:
  - `config/mail.php`
  - `config/survey-mailing.php`
- voorbeeldvariabelen:
  - `.env.example`

## Laatste Onderdelen Die Jij Nog Moet Invullen

Om de provider echt werkend te maken, moet jij straks vooral deze waarden in `.env` zetten:

- `SURVEY_MAILING_MAILER`
- `SURVEY_MAILING_FROM_ADDRESS`
- `SURVEY_MAILING_FROM_NAME`
- `SURVEY_MAILING_REPLY_TO_ADDRESS`
- `SURVEY_MAILING_REPLY_TO_NAME`
- `SURVEY_MAILING_PASSWORD`
- `SURVEY_MAILING_EHLO_DOMAIN`

### Concreet Voor Resend SMTP

Voor productie of echte testmailing zet je:

- `SURVEY_MAILING_MAILER=survey_confirmations_smtp`
- `SURVEY_MAILING_FROM_ADDRESS=noreply@jouwdomein.nl`
- `SURVEY_MAILING_FROM_NAME=Everyware`
- `SURVEY_MAILING_PASSWORD=<jouw-resend-api-key>`
- `SURVEY_MAILING_EHLO_DOMAIN=jouwdomein.nl`

Optioneel:

- `SURVEY_MAILING_REPLY_TO_ADDRESS=...`
- `SURVEY_MAILING_REPLY_TO_NAME=...`

Zolang `SURVEY_MAILING_MAILER=log` blijft staan, werkt de flow technisch wel, maar worden mails alleen gelogd en niet echt verzonden.

## Wat Je Daarna Moet Doen

Na het invullen van de providergegevens zijn dit de eerstvolgende stappen:

1. Voer de nieuwe migraties uit.
2. Zet de Resend DNS-records op het afzenderdomein.
3. Vul de `.env`-waarden in.
4. Test een submit met naam en e-mail.
5. Controleer of er records komen in:
   - `mail_recipients`
   - `mail_delivery_requests`
6. Controleer of de bevestigingsmail aankomt en of de bevestigingspagina de status toont.

## Beslissingen Die Je Eerst Moet Vastleggen

Voor je bouwt, moet je team deze vragen beantwoorden:

1. Hoe lang mag identiteit worden bewaard?
2. Moet een student later opnieuw gemaild kunnen worden, of alleen eenmalig?
3. Wie mag persoonsgegevens inzien?
4. Welk domein of subdomein wordt gebruikt voor het verzenden?
5. Welk afzenderadres en welke afzendernaam moeten worden gebruikt?
6. Moeten antwoorden op de mail ergens ontvangen worden?

## Niet-Vergeten Opmerking

De huidige velden `student_name` en `student_email` in `survey_responses` hoeven voor deze uitbreiding niet direct verwijderd te worden. Ze blijven wel privacy-technisch gevoelig als ze als structurele opslagplek voor echte persoonsgegevens gebruikt blijven worden. Daarom is het verstandige pad:

- laat de nieuwe mailingflow op de aparte mailingservice landen;
- houd bestaande architectuur voorlopig intact;
- beslis in een latere fase of oude PII-opslag uitgefaseerd of opgeschoond moet worden;
- splits later desgewenst `mail_recipients` verder op als strengere scheiding nodig wordt.

## Bronnen Voor Het Ontwerp

Deze architectuurkeuzes sluiten aan op algemene privacy- en securityprincipes:

- GDPR definieert pseudonimisering als verwerking waarbij gegevens niet meer zonder aanvullende informatie aan een persoon kunnen worden gekoppeld, mits die aanvullende informatie apart wordt bewaard.
- OWASP least privilege: services en database-accounts moeten alleen de minimale rechten krijgen die ze nodig hebben.

Dit document is technisch advies, geen juridisch advies. Als het project onder streng privacybeleid van een onderwijsinstelling valt, laat bewaartermijnen en grondslag ook juridisch of privacyrechtelijk toetsen.
