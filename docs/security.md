# Bezpečnost

## Zavedená opatření

### Přístup a vlastnictví dat
- Celé `/api` vyžaduje přihlášení (`security.yaml`); veřejné jsou jen login, refresh/invalidate tokenu
  a přihlášení přes Google/Apple.
- Záznam smí upravit nebo smazat jen jeho vlastník (`DietDiaryVoter`). Cizí záznam vrací **404**, ne 403,
  aby nešlo zjistit jeho existenci. Úprava nemění vlastníka.
- Data pro souhrny (`/api/days`, detail dne) se vždy filtrují podle přihlášeného uživatele.
- Při odhlášení se na frontendu maže session i veškerá data v paměti (cache dotazů, nastavení),
  aby nezůstala dalšímu uživateli na stejném zařízení. Téma zůstává (patří zařízení).

### Přihlášení
- **Omezení pokusů o přihlášení heslem** (`LoginThrottler`): max. 5 neúspěšných pokusů na e-mail + IP
  a 30 z jedné IP za 15 minut, pak 429. Úspěšné přihlášení počítadlo účtu vynuluje.
- **Google/Apple** (`OidcIdTokenVerifier`): ověřuje podpis (klíč podle `kid`, JWKS v cache 1 h), expiraci,
  `iss` a `aud` (víc client ID oddělených čárkou). Účet se páruje podle e-mailu **jen při `email_verified`**
  – jinak by šlo převzít cizí účet. Bez nastaveného client ID je endpoint vypnutý (404).
- Session na frontendu se ukončí při jakékoli odmítavé odpovědi serveru na refresh (kromě 502–504);
  síťová chyba uživatele neodhlásí.

### Náklady na AI a vstupy
- **Denní limit volání OpenAI na uživatele** (`AiUsageLimiter`, env `AI_DAILY_LIMIT`, výchozí 50) pro
  `/api/calculate` i `/api/upload`; po vyčerpání 429 se srozumitelnou hláškou.
- Text záznamu max. 500 znaků (ukládá se 255).
- Fotka max. 10 MB, jen JPEG/PNG/WebP/GIF – typ podle obsahu souboru (fileinfo), ne podle přípony.
- Chyby z OpenAI jdou jen do logu, klient dostane obecnou hlášku.
- Prompt injection: text uživatele jde přímo do LLM; dopad je nízký (striktní JSON schéma odpovědi).
  **Komentář od AI vždy vykreslovat jen jako text, nikdy přes `v-html`.**

### HTTP hlavičky (`backend/www-root/.htaccess`)
- CSP (skripty, styly a požadavky jen z vlastní domény, `frame-ancestors 'none'`), `X-Content-Type-Options`,
  `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`, HSTS jen přes HTTPS.
- `script-src` obsahuje `'unsafe-inline'` kvůli inline skriptům z buildu Nuxtu (mění se s každým buildem).
- **Až přibude přihlášení přes Google/Apple, je potřeba do CSP doplnit jejich domény.**
- Hlavičky jsou v `<IfModule mod_headers.c>` – bez modulu se tiše nepoužijí.

### Tajné údaje
- V gitu jsou jen šablony bez hodnot (`backend/.env`, `backend/.env.docker.dist`). Skutečné hodnoty jsou
  v `*.local` souborech (`.env.local`, `.env.dev.local`, `.env.docker.local`) a v `config/jwt/*.pem` – vše
  ignorované gitem i `.dockerignore`.
- Produkce a lokální vývoj mají mít **různé JWT klíče** – se stejným párem by lokálně vydaný token prošel
  i na produkci.
- Heslo admina ve fixtures se bere z `FIXTURE_ADMIN_PASSWORD`.
- Provozní endpoint `/ops/cache-clear` je chráněný tokenem `OPS_TOKEN` (min. 32 znaků), bez něj je vypnutý
  a navenek vrací 404.

## Známá otevřená rizika
- Access i refresh token jsou v `localStorage` – při XSS by šly ukrást. CSP riziko výrazně snižuje.
  Přesun refresh tokenu do httpOnly cookie se rozhodne spolu s nativní aplikací (cookies se v Capacitoru
  chovají jinak).
- Před otevřením registrace veřejnosti: GDPR (zdravotní data – smazání účtu, export dat, zásady ochrany
  osobních údajů).
