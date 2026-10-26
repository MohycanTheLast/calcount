# Provoz

## Verze aplikace

- Číslo verze je jen v `frontend/package.json` (`version`, sémantické verzování). Při buildu se propíše do
  aplikace (`runtimeConfig.public.appVersion` v `nuxt.config.ts`) a zobrazí se v Nastavení → O aplikaci
  spolu s datem sestavení – podle něj jde na telefonu poznat, jestli se už načetl nový build.
- Dokud je verze 0.x: nová funkce = zvýšit prostřední číslo (0.1.0 → 0.2.0), oprava = poslední (0.2.0 → 0.2.1).
  Verze 1.0.0 = otevření aplikace veřejnosti.
- Vydání: v `frontend/` `npm version <x.y.z> --no-git-tag-version` (upraví i `package-lock.json`), commit
  „release x.y.z“, tag `vx.y.z` na tomto commitu, build a nasazení.
- Nativní aplikace (Capacitor, až bude): stejné číslo jako verze v obchodech (iOS `CFBundleShortVersionString`,
  Android `versionName`) – při buildu převzít z `package.json`. Číslo sestavení (iOS `CFBundleVersion`, Android
  `versionCode`) je zvlášť a musí růst s každým nahráním do obchodu, i u stejné verze.

## Typ účtu

Mění se zatím jen v databázi (`free`, `premium`, `superior`; funkce k typům viz `App\Model\AccountType`):

```sql
UPDATE user SET account_type = 'premium' WHERE email = '…';
```

Aplikace si typ načte po novém načtení stránky (nebo po odhlášení a přihlášení).

## Nasazení na produkci (FTP)

1. **Frontend:** `npm run build` ve `frontend/` – výsledek se zkopíruje do `backend/www-root`.
   Nahrát celý obsah `www-root` včetně `index.html` a složky `_nuxt/` (obojí git ignoruje, proto v diffu
   nejsou vidět).
2. **Backend:** nahrát změněné soubory (`src/`, `config/`, `migrations/`, případně `vendor/` po změně
   balíčků). Seznam změn od posledního nasazení: `git diff --name-status <nasazený-commit>..HEAD -- backend`.
   Smazané soubory smazat i na serveru. **Nezapomenout na `config/services.yaml`** – bez něj se nesestaví
   kontejner (např. „Cannot autowire … $dailyLimit“).
3. **Nenahrávat:** `backend/.env` (přepsal by produkční), `docker-compose.yml`, `php/Dockerfile`.
4. **Migrace:** `php bin/console doctrine:migrations:migrate` (stejným způsobem jako dřív).
5. **Cache:** `POST /ops/cache-clear` (viz níže). Pokud aplikace vrací 500 a endpoint nefunguje, smazat přes
   FTP složku `var/cache/prod` – při dalším requestu se sestaví znovu.
6. Ověřit, že odpověď stránky obsahuje hlavičku `Content-Security-Policy` (= hosting má `mod_headers`).

**Ověření e-mailu při registraci** se zapíná `EMAIL_VERIFICATION_ENABLED=1` v `.env.local` – až po doplnění
odesílání e-mailů a nastavení `DEFAULT_URI` (viz security.md, Registrace).

Na produkci musí být `APP_ENV=prod` (s `dev` uniká v chybových odpovědích třída a text výjimek).
Pokud server používá `.env.local.php` (`composer dump-env prod`), změny v `.env.local` se neprojeví,
dokud se soubor nepřegeneruje nebo nesmaže.

## Smazání cache na dálku – `/ops/cache-clear`

- `POST /ops/cache-clear` s hlavičkou `X-Ops-Token` → 202; po odeslání odpovědi proběhne `cache:clear`
  (s warmupem) a `opcache_reset()`. Maže se až po odpovědi – smazání uprostřed requestu by mohlo shodit
  zbytek jeho zpracování.
- `GET /ops/cache-clear` vrátí protokol posledního běhu (`var/ops/cache-clear-last.json`).
- Na serveru musí být `OPS_TOKEN` (min. 32 znaků) v `.env.local`, ne v `.env.docker.local`.
  Vygenerovat: `php -r "echo bin2hex(random_bytes(32));"`.
- Volání jsou připravená v `backend/http/ops.http` (PhpStorm HTTP Client); token patří do
  `backend/http/http-client.private.env.json` (mimo git).
- `opcache_reset()` vyprázdní opcache celého PHP na serveru (i dalších aplikací na stejném PHP).

## Výměna JWT klíčů

Chyba „An error occurred while trying to encode the JWT token“ = privátní klíč nejde odemknout aktuální
`JWT_PASSPHRASE` (typicky po změně passphrase bez přegenerování klíčů).

- **S SSH:** na serveru `php bin/console lexik:jwt:generate-keypair --overwrite`.
- **Bez SSH:** vygenerovat klíče s produkční passphrase lokálně v dočasné složce a nahrát `private.pem`
  + `public.pem` do `config/jwt/`:
  ```
  openssl genpkey -algorithm RSA -pkeyopt rsa_keygen_bits:4096 -aes256 -out private.pem
  openssl pkey -in private.pem -pubout -out public.pem
  ```
  Ověření, že klíč sedí k passphrase: `openssl pkey -in private.pem -noout`.
- Lokální vývoj má mít vlastní klíče (jiné než produkce).
- Uživatelé zůstanou přihlášení – refresh tokeny jsou v DB a na JWT klíčích nezávisí.

## Lokální vývoj (Docker)

- `backend/docker-compose.yml`: PHP 8.2 + Apache, MySQL 8 (port jen na `127.0.0.1`). Hesla MySQL jsou
  v `backend/.env.docker.local` (šablona `.env.docker.dist`).
- Po změně `php/Dockerfile` nebo `docker-compose.yml`: `docker compose up -d --build`.
- Migrace: `docker compose exec php php bin/console doctrine:migrations:migrate`.
- Frontend: `npm run dev` (proxy `/api` na `localhost:80`), testy `npm run test`, typy `npm run typecheck`.
