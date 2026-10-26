# Bezpečnost

## Zavedená opatření

### Přístup a vlastnictví dat
- Celé `/api` vyžaduje přihlášení (`security.yaml`); veřejné jsou jen login, registrace (a potvrzení e-mailu),
  refresh/invalidate tokenu a přihlášení přes Google/Apple.
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

### Osobní API tokeny (iOS Zkratky)
- Dlouhodobý klíč `cc_pat_…` pro zápis aktivních kalorií z Apple Zdraví (`POST /api/activity`). V DB je jen
  SHA-256 hash, plný token uživatel uvidí jen jednou při vytvoření. Nevyprší, dá se odvolat; max. 5 aktivních
  na uživatele, max. 120 požadavků na token za hodinu (pak 429).
- Vlastní firewall `personal_token` (`security.yaml`) se použije jen pro `/api` požadavky s hlavičkou
  `Bearer cc_pat_` – token se tak nedostane k JWT autentizaci. Přihlášený token **nemá `ROLE_USER`**, jen role
  podle scopes (`activity:write` → `ROLE_PAT_ACTIVITY_WRITE`), takže `access_control` ho pustí jen na
  `POST /api/activity`; jinde dostane 403. Tokeny spravuje jen uživatel s JWT (`/api/tokens`).
- Únik tokenu = někdo může zapsat aktivní kalorie (max. 31 dní zpět), nic víc. Uživatel token odvolá
  v Nastavení → Apple Zdraví.

### Registrace (`RegistrationController`)
- E-mail se ukládá oříznutý a malými písmeny, heslo 8–4096 znaků, povinný souhlas s podmínkami – ukládá se
  čas a verze podmínek (`terms_accepted_at`, `terms_version`), aby šel souhlas doložit (GDPR, zdravotní data).
- **Max. 5 registrací z jedné IP za hodinu** (`RegistrationThrottler`) – každý účet má vlastní denní limit AI.
- **Ověření e-mailu odkazem je připravené, ale vypnuté** (`EMAIL_VERIFICATION_ENABLED`, výchozí `0`, `EmailVerifier`):
  - odkaz je podepsaný HMAC (APP_SECRET) z id, e-mailu a expirace (48 h) – bez tokenu v DB,
  - se zapnutým ověřením neaktivovaný účet dostane při přihlášení 403 (až po ověření hesla) a registrace na
    existující e-mail odpoví stejně jako úspěšná (nejde zjistit, kdo má účet). Bez ověření vrací 409,
  - **před zapnutím** doplnit odesílání e-mailu v `EmailVerifier::sendVerificationEmail()` (symfony/mailer)
    a nastavit `DEFAULT_URI` na veřejnou adresu aplikace.
- Dokud ověření neběží, mohl by někdo založit účet na cizí e-mail. Když se pak skutečný vlastník přihlásí
  přes Google/Apple (ověřený e-mail), účet se mu propojí, **heslo neověřeného účtu se zruší** a e-mail se označí
  jako ověřený – útočník tak k účtu nezůstane mít přístup.

### Náklady na AI a vstupy
- **Denní limit volání OpenAI na uživatele** (`AiUsageLimiter`, env `AI_DAILY_LIMIT`, výchozí 50) pro
  `/api/calculate` i `/api/upload`; po vyčerpání 429 se srozumitelnou hláškou.
- Text záznamu max. 500 znaků (ukládá se 255).
- Fotka max. 10 MB, jen JPEG/PNG/WebP/GIF – typ podle obsahu souboru (fileinfo), ne podle přípony.
- Chyby z OpenAI jdou jen do logu, klient dostane obecnou hlášku.

### Co dostane OpenAI (slibujeme v podmínkách užívání)
- Vždy jen jeden záznam: text (+ systémový pokyn podle režimu komentáře), nebo fotku. Žádný e-mail, ID účtu,
  IP klienta, historie ani součty – požadavek jde ze serveru pod klíčem provozovatele, bez parametru `user`.
  **Při změně promptu nebo volání (`OpenAiClient`) to musí dál platit, jinak upravit `pages/terms.vue`.**
- Fotka se před odesláním na frontendu překreslí přes canvas (`utils/image.ts`): delší strana max. 1600 px, JPEG
  bez metadat (EXIF – poloha GPS, zařízení). Když to nejde, fotka se neodešle (originál s metadaty nikdy).
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
- GDPR: podmínky užívání a zásady ochrany osobních údajů jsou na `/terms` (souhlas při registraci). Před
  otevřením registrace veřejnosti **doplnit údaje provozovatele** v `pages/terms.vue` (`OPERATOR`). Smazání
  účtu a export dat zatím jen na žádost e-mailem – samoobslužně v aplikaci chybí.
- Přihlášení přes Google/Apple zakládá účet bez souhlasu s podmínkami – před zapnutím doplnit souhlas.
