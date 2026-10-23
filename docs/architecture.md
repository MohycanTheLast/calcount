# Architektura

## Technologie a rozhodnutí

- **Frontend: Nuxt 4 jako čistá SPA** (`ssr: false`, statický build). Výstup buildu se kopíruje do
  `backend/www-root` (`npm run build` → `scripts/copy-to-www-root.mjs`) a servíruje ho stejný Apache jako API.
  SSR nepotřebujeme: přihlášení je v prohlížeči a o SEO nejde.
- **Backend: Symfony 7.4 + Doctrine** (MySQL), JWT přihlášení (lexik) s rotujícími refresh tokeny (gesdinet).
- **AI: OpenAI** – odhad kalorií a vody z textu (`/api/calculate`), rozpoznání jídla na fotce (`/api/upload`).
- **Apple Health (HealthKit) nemá webové API** – web ani PWA k aktivním kaloriím z iPhonu/Apple Watch
  přístup nemají. Cesty:
  - krátkodobě iOS Zkratky (osobní automatizace pošle aktivní kalorie na naše API s osobním tokenem),
  - dlouhodobě Capacitor – zabalí stávající SPA do nativní aplikace s přístupem k HealthKit / Health Connect.
    Frontend se kvůli tomu nepřepisuje (viz „Příprava na nativní aplikaci“).
- **Přihlášení třetích stran:** Google a Apple přes ID token (OpenID Connect). App Store vyžaduje
  Sign in with Apple, pokud nativní iOS aplikace nabízí jiné přihlášení třetí strany.
  Strava je zamýšlená jako *propojení účtu* (zdroj aktivit), ne jako přihlášení.

## Frontend (`frontend/app`)

```
pages/        tenké stránky – skládají komponenty, nastavují meta (layout, middleware)
layouts/      tabs.vue (hlavní obrazovky se spodním tab barem), default.vue (bez tab baru)
components/   sdílené UI: AppTabBar (seznam záložek), DaySummary, AlertMessage
features/     funkce po složkách: diary/ (denní karty, zadávání, poslední záznam, řádek dne, výdej dne,
              queries.ts), calendar/ (měsíční mřížka, buňka dne, queries.ts), settings/ (výpočet BMR),
              integrations/ (osobní tokeny – queries.ts), onboarding/ (uvítání a průvodce); později stats/
api/          typované volání backendu (diary, settings, auth, activity) + types.ts se všemi typy odpovědí
composables/  useAuth (session), useApiFetch (token + obnova), useUserSettings (nastavení, téma),
              useCamera, useSpeechToText
lib/          storage.ts – úložiště zařízení (session, téma) za rozhraním
utils/        čisté funkce: dates (veškerá práce s daty), balance, bmr, format, theme, activity, tour – testované Vitestem
plugins/      vue-query (cache dat ze serveru), theme.client (téma při startu)
middleware/   auth (přihlášení), transition.global (směr přechodu detail ↔ seznam)
```

- **Data ze serveru přes TanStack Query** (`features/diary/queries.ts`). Klíče začínají `['diary', …]`;
  po vytvoření/úpravě/smazání záznamu se invaliduje celý prefix, takže se obnoví Deník, detail dne
  i budoucí kalendář a statistiky. Při odhlášení se celá cache maže.
- **Komponenty se auto-importují** i z `features/` (nastaveno v `nuxt.config.ts`), composables a api
  se importují explicitně.
- **Stránky bez přihlášení:** `/` (přihlášení), `/register`, `/terms` (podmínky užívání a GDPR),
  `/verify-email` (cíl odkazu z registračního e-mailu) – seznam `PUBLIC_PATHS` v `app.vue` a SPA přesměrování
  v `backend/www-root/.htaccess`.
- **Záložky:** Deník (posledních 7 dní, za nejstarší kartou odkaz do kalendáře), Kalendář (měsíc v URL `?m=YYYY-MM`,
  barvy dnů podle bilance), Nastavení. Nová záložka = položka v seznamu v `AppTabBar.vue` + stránka
  s `definePageMeta({ layout: 'tabs' })` + název routy v SPA přesměrování v `backend/www-root/.htaccess`.
  Podstránky nastavení jsou v `pages/settings/` (např. `/settings/apple-health` – tokeny a návod pro Zkratky).
- **Úvodní průvodce** (`features/onboarding`): nový účet (`/api/user/me` → `onboardingCompleted: false`) uvidí
  v Deníku uvítání a pak průvodce – zvýrazní prvky s atributem `data-tour` (kroky v `steps.ts`, krok bez prvku
  na stránce se vynechá). Dokončení i přeskočení se uloží na server (`POST /api/user/onboarding`), znovu ho jde
  spustit v Nastavení → Účet. Nový prvek do průvodce = atribut `data-tour` + krok v `steps.ts`.
- **Aktivní kalorie:** zdroj se volí v Nastavení (`activitySource`), konkrétní den jde ručně upravit v detailu dne
  (`DayExpenditureCard`). Ikona u výdeje v `DaySummary` ukazuje Apple Zdraví (hodinky) nebo ruční úpravu (tužka).
- **Přechody stránek** běží v režimu `out-in`. Stránka musí mít v `app.vue` i v layoutu vlastní obalový
  element – jinak Vue při současné změně okolí (tab bar) vloží novou stránku na špatné místo nebo spadne
  na `insertBefore`.
- **Téma:** výchozí tmavé; volba se drží v úložišti zařízení a inline skript v `<head>` ji aplikuje ještě
  před načtením JS (bez probliknutí). Tailwind `dark:` je navázaný na třídu `.dark`, ne na téma systému.

### Příprava na nativní aplikaci

- Adresa backendu je v `runtimeConfig.public.apiBase` (`NUXT_PUBLIC_API_BASE` při buildu, výchozí prázdná =
  stejný origin). Pro jiný origin bude potřeba CORS na backendu.
- Session a téma jdou přes `lib/storage.ts`. Bezpečné úložiště v Capacitoru je asynchronní – při startu se
  načte jednou do paměti a dál se čte synchronně stejným rozhraním.

## Backend (`backend/src`)

### Datový model

| Tabulka | Účel |
|---|---|
| `user` | účet; `password` může být prázdné u účtu jen přes externí přihlášení; `created_at`, `email_verified_at`, souhlas s podmínkami (`terms_accepted_at`, `terms_version`), `onboarding_completed_at` |
| `user_identity` | propojené externí účty (provider + `sub`), víc na uživatele |
| `user_settings` | 1:1 s uživatelem – BMR, průměrná aktivita, téma, režim komentáře AI, `activity_source` |
| `diet_record` | záznam jídla/pití: text, kcal, voda (ml), **`entry_date`** (den, ke kterému patří) |
| `activity_energy` | aktivní kalorie za den a zdroj (`manual`, `apple_health`, `strava`), unikátní (uživatel, den, zdroj) |
| `refresh_tokens` | refresh tokeny (gesdinet) |
| `personal_access_token` | osobní API tokeny pro iOS Zkratky – jen hash, scopes, poslední použití, odvolání |
| `burned_record` | stará tabulka výdeje bez vazby na uživatele – nepoužívá se, ke smazání |

- **`entry_date` je oddělené od `created_at`**, aby šlo zapisovat i zpětně a den nezávisel na časové zóně serveru.
- Text záznamu se ukládá tak, jak ho uživatel napsal (escapuje se až při výstupu).

### Souhrn dne – `GET /api/days?from=YYYY-MM-DD&to=YYYY-MM-DD`

Jeden prvek za každý den v rozsahu (i bez záznamu), max. 366 dní; bez parametrů posledních 14 dní.
Počítá `DaySummaryService` – jediné místo, kde se počítá výdej a bilance.

```
{ date, kcal, waterMl, entryCount, bmrKcal, activeKcal, activeSource, expenditureKcal, balanceKcal }
```

- **výdej** = BMR + aktivní kalorie dne; bez BMR je výdej i bilance `null`,
- **bilance** = příjem − výdej (záporná = deficit),
- **aktivní kalorie:** ruční zápis za den (`manual`) má vždy přednost; jinak podle `activitySource`:
  - `static` – průměrná denní aktivita z nastavení (výchozí),
  - `synced` – jen synchronizovaná data,
  - `synced_or_static` – synchronizovaná, a pokud za den chybí, průměrná aktivita.
  Víc synchronizovaných zdrojů za den se nesčítá (aktivity ze Stravy bývají i v Apple Health) – bere se maximum.
- BMR a průměrná aktivita jsou aktuální hodnoty z nastavení (historie nastavení se neukládá).

### Ostatní endpointy

| Endpoint | Popis |
|---|---|
| `POST /api/login`, `/api/token/refresh`, `/api/token/invalidate` | heslo, obnova a zneplatnění session |
| `POST /api/register`, `/api/register/verify` | registrace e-mailem a heslem, potvrzení e-mailu odkazem (zatím vypnuté) |
| `POST /api/activity` | aktivní kalorie z Apple Zdraví (`{date, activeKcal}` nebo pole až 7 dní, max. 31 dní zpět), upsert; JWT nebo osobní token |
| `PUT/DELETE /api/activity/manual/{YYYY-MM-DD}` | ruční úprava aktivních kalorií dne / její zrušení; vrací souhrn dne |
| `GET/POST /api/tokens`, `DELETE /api/tokens/{id}` | osobní API tokeny (vytvoření vrátí plný token jen jednou; první token přepne `activitySource` na `synced_or_static`) |
| `POST /api/auth/google`, `/api/auth/apple` | přihlášení/registrace přes ID token (bez client ID vypnuté) |
| `POST /api/calculate` | nový záznam z textu (AI odhad), uloží se k dnešnímu dni |
| `POST /api/save/{id}`, `/api/delete/{id}` | úprava / smazání záznamu (jen vlastník) |
| `GET /api/diary-day/{YYYY-MM-DD}` | záznamy dne |
| `POST /api/upload` | rozpoznání jídla na fotce (nic neukládá) |
| `GET/POST /api/user/settings`, `GET /api/user/me` | nastavení, přihlášený uživatel (vč. `onboardingCompleted`) |
| `POST /api/user/onboarding` | úvodní průvodce dokončený/přeskočený |
| `POST/GET /ops/cache-clear` | smazání cache na serveru (token v hlavičce, viz operations.md) |
