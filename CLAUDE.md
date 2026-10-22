# CLAUDE.md – Pravidla pro přístup k citlivým datům

## Zakázaný přístup

Claude nesmí číst, zobrazovat, kopírovat ani upravovat následující soubory:

- `backend/.env.local`
- `backend/.env.*.local`
- `backend/config/jwt/private.pem` – privátní JWT klíč
- `backend/config/jwt/public.pem` – veřejný JWT klíč
- Jakékoliv soubory s příponou `.pem`, `.key`, `.p12`, `.pfx`

## Citlivé proměnné prostředí

Claude nesmí zobrazovat ani kopírovat hodnoty těchto proměnných:

- `APP_SECRET`
- `DATABASE_URL` (obsahuje přihlašovací údaje k DB)
- `JWT_PASSPHRASE`
- `OPENAI_API_KEY`
- Jakékoliv proměnné obsahující v názvu: `SECRET`, `PASSWORD`, `PASS`, `TOKEN`, `KEY`, `CREDENTIALS`, `PRIVATE`

## Databázová data uživatelů

- Claude nesmí zobrazovat obsah tabulky `users` (e-maily, hashovaná hesla, OAuth ID)
- Claude nesmí vypisovat refresh tokeny ani access tokeny konkrétních uživatelů

## Obecné pravidlo

Pokud soubor nebo proměnná v názvu obsahuje slova jako `secret`, `password`, `pass`, `token`, `key`, `credentials` nebo `private`, Claude je nesmí číst ani zobrazovat jejich hodnoty.

## Co je povoleno

- Čtení a úprava veškerého aplikačního kódu (controllery, entity, composables, pages…)
- Čtení konfiguračních souborů bez citlivých hodnot (`nuxt.config.ts`, `composer.json`, `security.yaml` apod.)
- Čtení `backend/.env` pouze za účelem kontroly struktury proměnných (ne hodnot tajných klíčů)