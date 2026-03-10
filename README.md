# Everyware

**Enquetes voor iedereen — in ontwikkeling voor het Leer- en Innovatiecentrum van Avans Hogeschool.**

Everyware is een webapplicatie waarmee het **Leer- en Innovatiecentrum (LIC)** van Avans Hogeschool Den Bosch enquetes kan aanmaken en uitzetten. Studenten kunnen deze enquetes eenvoudig invullen. Het doel is om een **diversere groep** respondenten te bereiken en betrouwbare feedback voor onderwijs en beleid te verzamelen.

---

## Over het LIC en Avans

Het **Leer- en Innovatiecentrum (LIC)** van Avans Hogeschool ondersteunt onderwijs, onderzoek en leren, en adviseert het College van Bestuur. Het LIC speelt een centrale rol bij onderwijsevaluaties en studentenonderzoek — onder meer via de Avans Vragenbank en samenwerking met opleidingen.

**Avans Hogeschool** heeft vestigingen in Den Bosch, Breda en Tilburg. De locatie **Onderwijsboulevard** in Den Bosch (o.a. Onderwijsboulevard 215 en Campus300) is een belangrijke plek voor studenten en docenten. Met Everyware wil het LIC op een laagdrempelige manier meer studenten bereiken voor enquêtes en evaluaties.

*Dit project is in opdracht van het LIC van Avans Hogeschool Den Bosch.*

---

## Status van het project

⚠️ **Everyware is nog in ontwikkeling.** De functionele enquetemodule (aanmaken, beheren, invullen) wordt nog gebouwd. De basis is een Laravel 12-applicatie met Livewire en Flux UI.

---

## Technologie

- **PHP 8.4** & **Laravel 12**
- **Livewire 4** & **Flux UI** voor het interface
- **Laravel Fortify** voor authenticatie
- **MySQL 8.4** (Sail) of MySQL 8.x (lokaal)
- **Vite** voor frontend-assets (Tailwind CSS)
- **Pest** voor tests

---

## Aan de slag

### 1. Repository clonen

```bash
git clone <url-van-deze-repo> everyware
cd everyware
```

### 2. Omgevingsbestand

Kopieer het voorbeeld en bewerk later indien nodig (zie stap 3 of 4):

```bash
cp .env.example .env
```

### 3. Kies je omgeving

Je kunt het project draaien **met Docker (Laravel Sail)** of **zonder Docker** met lokale PHP, MySQL en Apache (of de ingebouwde PHP-server). Volg één van de twee routes hieronder.

---

## Optie A: Met Docker (Laravel Sail)

Geschikt als je geen PHP/MySQL lokaal wilt installeren. Vereist **Docker** en **Docker Compose**.

- **Linux:** [Docker Engine](https://docs.docker.com/engine/install/) + [Docker Compose](https://docs.docker.com/compose/install/linux/)
- **Windows:** [Docker Desktop](https://docs.docker.com/desktop/install/windows-install/) (WSL2 aanbevolen). Gebruik WSL of Git Bash voor de commando’s.
- **macOS:** [Docker Desktop](https://docs.docker.com/desktop/install/mac-install/)

In `.env` blijven **DB_HOST=mysql** en de overige Sail-standaarden staan.

#### Sail-alias (aanbevolen)

Zodat je overal `sail` kunt typen in plaats van `./vendor/bin/sail`:

**Linux / macOS / WSL / Git Bash** — voeg toe aan `~/.bashrc` of `~/.zshrc`:

```bash
alias sail='[ -f sail ] && bash sail || bash vendor/bin/sail'
```

Daarna: `source ~/.bashrc` (of `source ~/.zshrc`). Voer alle commando’s uit **vanuit de projectmap**.

> **PowerShell:** Sail is een bash-script. Gebruik WSL of Git Bash, of roep steeds `bash vendor/bin/sail` aan.

#### Stappen met Sail

1. **PHP-afhankelijkheden** (eenmalig; kan via Docker als je geen PHP lokaal hebt):

   ```bash
   docker run --rm -v "$(pwd):/app" -w /app composer:2 composer install
   ```

   > **Windows PowerShell:** `docker run --rm -v "${PWD}:/app" -w /app composer:2 composer install`

2. **Containers starten, sleutel en database:**

   ```bash
   sail up -d
   sail artisan key:generate
   sail artisan migrate
   ```

3. **Frontend:**

   ```bash
   sail npm install
   sail npm run build
   ```

4. **Applicatie:** open **http://localhost** (poort 80). Voor live assets in een aparte terminal:

   ```bash
   sail npm run dev
   ```

---

## Optie B: Zonder Docker (lokaal)

Je kunt Everyware ook draaien met **lokale PHP, MySQL en een webserver** (bijv. Apache) of met de ingebouwde PHP-server. Geen Docker of Sail nodig.

#### Vereisten

- **PHP 8.2+** (8.4 aanbevolen), met o.a. extensies: bcmath, ctype, fileinfo, json, mbstring, openssl, pdo, pdo_mysql, tokenizer, xml
- **Composer**
- **Node.js** en **npm**
- **MySQL 8.x** (of MariaDB 10.3+)

#### .env voor lokaal

Zet in je `.env` (na `cp .env.example .env`):

- **DB_HOST=127.0.0.1** (of `localhost`) — *niet* `mysql`
- **DB_DATABASE**, **DB_USERNAME**, **DB_PASSWORD** naar jouw lokale MySQL-instellingen

De overige Laravel-instellingen in `.env.example` kun je zo laten of aanpassen (APP_URL, APP_PORT is niet van toepassing zonder Sail).

#### Stappen zonder Docker

1. **PHP-afhankelijkheden:**

   ```bash
   composer install
   ```

2. **Applicatiesleutel:**

   ```bash
   php artisan key:generate
   ```

3. **Database:** maak in MySQL een lege database aan (bijv. `everyware`) en zorg dat **DB_DATABASE**, **DB_USERNAME** en **DB_PASSWORD** in `.env` kloppen. Daarna:

   ```bash
   php artisan migrate
   ```

4. **Frontend:**

   ```bash
   npm install
   npm run build
   ```

5. **Applicatie starten:**

   - **Met ingebouwde server:**  
     `php artisan serve`  
     → open http://localhost:8000

   - **Met Apache:**  
     Zet de **document root** van je virtual host op de map **`public`** van dit project (bijv. `/pad/naar/everyware/public`). Zorg dat `mod_rewrite` aanstaat en dat `AllowOverride All` voor die map staat. Open dan de URL van je virtual host.

   Voor live frontend-wijzigingen in een aparte terminal: `npm run dev` (en bij Apache: zorg dat Vite’s dev-server bereikbaar is of bouw met `npm run build` na wijzigingen).

---

## Overzicht commando’s

| Taak | Met Sail | Zonder Docker |
|------|----------|----------------|
| App starten | `sail up -d` | `php artisan serve` of Apache |
| Stoppen | `sail down` | Stop PHP-server of Apache |
| Migraties | `sail artisan migrate` | `php artisan migrate` |
| Tests | `sail artisan test --compact` | `php artisan test --compact` |
| Composer | `sail composer install` | `composer install` |
| Vite dev | `sail npm run dev` | `npm run dev` |
| Code style | `sail pint` | `./vendor/bin/pint` |

---

## Tests en codekwaliteit

**Tests (Pest):**

```bash
# Met Sail:
sail artisan test --compact

# Zonder Docker:
php artisan test --compact
```

**Linting (Laravel Pint):**

```bash
# Met Sail:
sail pint

# Zonder Docker:
./vendor/bin/pint
```

De tests gebruiken standaard **SQLite in-memory** (`DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:` in `phpunit.xml`), dus je hoeft geen aparte MySQL-testdatabase aan te maken — dat werkt zowel met als zonder Sail.

---

*Everyware — Enquetes voor het LIC van Avans Hogeschool Den Bosch.*
