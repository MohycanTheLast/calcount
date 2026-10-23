# Modely AI a náklady (OpenAI)

Rozbor ze dne 2026-10-24. Ceny jsou z oficiálních stránek OpenAI (viz Zdroje). Počty tokenů na jeden záznam
jsou **odhady z promptů v kódu**, ne měření – viz doporučení 5 (logovat `usage`).

**Hlavní zjištění: oba modely, které aplikace používá, OpenAI vypíná 14. 1. 2027.** Přechod je nutný;
oficiální náhrada `gpt-6-luna` vyjde zhruba 8× levněji.

## Současný stav (`backend/src/Service/OpenAiClient.php`)

| Volání | Model | Odhad tokenů | Cena za volání |
|---|---|---|---|
| Odhad kalorií a vody z textu (`estimateNutrition`) | `gpt-4.1-mini` ($0,40 / $1,60 za 1M tokenů vstup/výstup) | ~250 vstup, ~60 výstup | **$0,0002** |
| Rozpoznání jídla na fotce (`analyzeImage`) | `gpt-4o-mini` ($0,15 / $0,60) | **~25 600 vstup**, ~25 výstup | **$0,0039** |

- **Fotky jsou dnes nečekaně drahé:** `gpt-4o-mini` účtuje obrázek jako 2 833 tokenů základ + 5 667 tokenů
  za každý čtverec 512 px. Fotka z iPhonu (po zmenšení na 1024×768) = 4 čtverce ≈ 25 500 tokenů.
  Jedna fotka stojí zhruba jako 20 textových záznamů.
- **Vypnutí modelů:** `gpt-4o-mini` i `gpt-4.1-mini` (snapshoty `2024-07-18` / `2025-04-14`) končí
  **14. 1. 2027**, náhrada podle OpenAI: `gpt-6-luna`.
- **Fotka se zpracovává ve dvou krocích** (foto → text → kalorie). Model, který odhaduje kalorie, fotku nevidí
  a přichází o informaci o velikosti porce.

## Alternativy (všechny umí obrázky i strukturovaný výstup)

| Model | Vstup / výstup za 1M tokenů | Text, cena za volání | Fotka, cena za volání | Poznámka |
|---|---|---|---|---|
| dnes (`4.1-mini` + `4o-mini`) | viz výše | $0,0002 | $0,0039 | končí 1/2027 |
| **`gpt-6-luna`**, uvažování `none` | $0,10 / $0,50 | **$0,00006** | **$0,00032** (detail `high`) / $0,00004 (`low`) | oficiální náhrada; „pro objemné, cenově citlivé úlohy“ |
| `gpt-5.6-luna` | $0,20 / $1,20 | $0,00012 | $0,00065 | mezistupeň |
| `gpt-6-sol`, uvažování `low` | $2 / $10 | ~$0,003 | ~$0,008 | přesnější, ~15× dražší než luna |

- **Uvažování (reasoning):** `gpt-6-luna` má výchozí míru `medium` – pro náš jednoduchý úkol nastavit `none`,
  jinak se platí i tokeny uvažování. `gpt-6-sol` má nejnižší úroveň `low`; počítáno s odhadem ~200 tokenů
  uvažování na volání.
- **Detail obrázku u nových modelů:** u řady 5.6/6 je `detail: auto` totéž co `original` (plné rozlišení) –
  fotka 4032×3024 by vyšla na ~14 000 tokenů. Detail je nutné nastavit výslovně:
  - `high` = max. 2 500 políček 32×32 px ≈ 3 000 tokenů,
  - `low` = max. 512×512 px ≈ 230 tokenů.
- Jak `gpt-6-luna` počítá obrázky, OpenAI zatím nezveřejnilo; počítáno podle řady 5.6 (32px políčka, násobek 1,2).
- Batch API (poloviční cena) se nehodí – volání jsou interaktivní. Cache promptů OpenAI uplatňuje až od
  1 024 tokenů; náš prompt má ~250.

## Modelové situace (měsíčně, USD)

- **Běžný uživatel:** 5 textových záznamů + 1 záznam z fotky denně (6 odhadů z textu + 1 rozpoznání fotky).
- **Aktivní uživatel:** 10 záznamů denně, z toho 3 z fotky.

| Varianta | 1 běžný | 1 aktivní | 10 běžných | 50 běžných | 200 běžných |
|---|---|---|---|---|---|
| dnes | $0,15 | $0,41 | $1,51 | $7,56 | $30,20 |
| `gpt-6-luna`, fotka `high` | **$0,02** | **$0,05** | **$0,20** | **$0,98** | **$3,90** |
| `gpt-6-luna`, fotka `low` | $0,01 | $0,02 | $0,11 | $0,56 | $2,24 |
| `gpt-5.6-luna` | $0,04 | $0,10 | $0,42 | $2,08 | $8,30 |
| `gpt-6-sol` | $0,81 | $1,69 | $8,10 | $40,50 | $162 |

- **Smíšená varianta podle typů účtů:** 40 free uživatelů na `gpt-6-luna` + 10 premium aktivních, kterým fotky
  analyzuje přesnější `gpt-6-sol` → **~$8,50 měsíčně**.
- **Nejhorší případ zneužití** při denním limitu 50 volání (`AI_DAILY_LIMIT`): dnes ~$5,80 měsíčně na uživatele,
  s `gpt-6-luna` ~$0,48.

## Doporučení

1. **[HOTOVO 2026-10-24] Přejít na `gpt-6-luna` s uvažováním `none` do 14. 1. 2027.** Nastavení je v konstantách
   `OpenAiClient::MODEL`, `REASONING_EFFORT` a `IMAGE_DETAIL`. Chyba API nebo prázdná odpověď teď vyhodí výjimku
   (dřív se tiše uložil záznam s 0 kcal). Zbývá ověřit kvalitu na skutečných datech, např. 20 typických českých
   jídel a 10 fotek, proti dřívějším modelům a `gpt-6-sol`.
2. **[HOTOVO 2026-10-24] U fotek nastavit `detail: "high"`**, nikdy `auto`; na testovací sadě zkusit, jestli
   nestačí `low` (dalších ~7× levnější).
3. **Zmenšovat fotku už v telefonu** na ~1 600 px před odesláním – rychlejší upload, předvídatelná cena.
4. **Fotku zpracovat jedním voláním:** obrázek → položky, porce, kcal a voda. Přesnější (model vidí porci)
   a o jedno volání méně. Stejné pole položek pak poslouží i pro makroživiny.
5. **Logovat skutečnou spotřebu:** Responses API vrací `usage` (vstupní, výstupní a uvažovací tokeny) –
   ukládat a odhady nahradit skutečnými čísly.
6. **Makroživiny cenu skoro nezvýší:** bílkoviny, tuky, sacharidy a cukry jsou ~20 tokenů výstupu navíc,
   u `gpt-6-luna` zlomek tisíciny dolaru za záznam. Náklady jsou hlavně ve vývoji (sloupce v DB, zobrazení).

## Zdroje

- [OpenAI – ceník API](https://developers.openai.com/api/docs/pricing)
- [OpenAI – výpočet tokenů u obrázků](https://developers.openai.com/api/docs/guides/images-vision)
- [OpenAI – vypínání modelů](https://developers.openai.com/api/docs/deprecations)
- [OpenAI – modely](https://developers.openai.com/api/docs/models)
- [OpenAI – gpt-6-luna](https://developers.openai.com/api/docs/models/gpt-6-luna)
- [OpenAI Community – výpočet tokenů u obrázků](https://community.openai.com/t/help-understand-token-usage-with-vision-api/893022)
- [usagepricing.com – ceny OpenAI](https://www.usagepricing.com/ai-token-pricing/openai)
