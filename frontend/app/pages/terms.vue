<template>
    <div class="cc-page">
        <header class="cc-nav">
            <div class="cc-container cc-header">
                <div>
                    <button type="button" class="cc-header-action -ml-1" @click="goBack">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M15.7 4.3a1 1 0 0 1 0 1.4L9.42 12l6.3 6.3a1 1 0 0 1-1.42 1.4l-7-7a1 1 0 0 1 0-1.4l7-7a1 1 0 0 1 1.4 0Z"/>
                        </svg>
                        Zpět
                    </button>
                </div>
                <h1 class="cc-header-title">Podmínky užívání</h1>
                <div></div>
            </div>
        </header>

        <main class="cc-container pt-4 pb-16">
            <article class="cc-card cc-card-pad cc-prose max-w-2xl mx-auto">
                <p class="cc-subtle">Účinné od {{ EFFECTIVE_DATE }}</p>

                <p>
                    Víme, že podmínky užívání skoro nikdo nečte – a docela to chápeme. Věnuj jim ale prosím
                    aspoň trochu pozornosti, jde v nich o tvoje data. To nejdůležitější najdeš hned tady
                    v kostce, podrobnosti dál v textu.
                </p>

                <!-- V KOSTCE – hlavní body pro ty, kdo dál číst nebudou -->
                <div class="cc-terms-summary">
                    <h2>V kostce</h2>
                    <ul>
                        <li>
                            <strong>Všechno jsou odhady.</strong> Kalorie, vodu i komentáře k záznamům počítají
                            modely umělé inteligence. Jsou orientační a mohou se mýlit. Aplikace nenahrazuje lékaře
                            ani nutričního terapeuta.
                        </li>
                        <li>
                            <strong>Tvoje data jsou v bezpečí.</strong> K odhadu posíláme AI vždy jen jeden záznam –
                            jeho text, nebo fotku zbavenou metadat. Bez jména, e-mailu a bez tvých ostatních záznamů.
                            AI se tak nedozví, kdo si odhad přeje, ani v jakých souvislostech. Souhrnné informace
                            o tvých stravovacích návycích AI nikdy neposkytujeme.
                        </li>
                        <li>
                            <strong>Do záznamů nepiš osobní údaje.</strong> Co do textu napíšeš (třeba své jméno),
                            odejde k AI přesně tak. Za údaje, které takto do záznamu sám(a) vložíš, provozovatel
                            neodpovídá.
                        </li>
                        <li>
                            <strong>Žádná reklama ani prodej dat.</strong> Účet i všechna data k němu na požádání
                            smažeme.
                        </li>
                        <li>Aplikace je určena osobám starším 15 let.</li>
                    </ul>
                </div>

                <h2>1. Úvod</h2>
                <p>
                    Kalorický deník (dále „aplikace“) je webová aplikace pro vedení deníku jídla a pití, odhad přijaté
                    energie (kcal) a vody a sledování denní kalorické bilance.
                </p>
                <p>
                    Provozovatelem aplikace je {{ OPERATOR.name }}, IČO {{ OPERATOR.id }}, se sídlem
                    {{ OPERATOR.address }} (dále „provozovatel“). Kontakt: {{ OPERATOR.email }}.
                </p>
                <p>
                    Registrací potvrzuješ, že ses s těmito podmínkami seznámil(a) a souhlasíš s nimi. Pokud
                    s nimi nesouhlasíš, aplikaci prosím nepoužívej.
                </p>

                <h2>2. Účet</h2>
                <ul>
                    <li>Aplikace je určena osobám starším 15 let.</li>
                    <li>
                        Při registraci uveď platný e-mail, který ti patří. Účet je osobní a nepřenosný, jedna osoba
                        má mít jeden účet.
                    </li>
                    <li>
                        Heslo chraň a nesděluj ho dalším osobám. Za činnost provedenou pod tvým účtem odpovídáš ty.
                        Pokud máš podezření na zneužití účtu, dej nám vědět.
                    </li>
                    <li>
                        Účet můžeš kdykoli zrušit – napiš nám na {{ OPERATOR.email }} z e-mailu, se kterým jsi
                        zaregistrovaný(á). Účet i všechna data k němu smažeme.
                    </li>
                </ul>

                <h2>3. Odhady AI, ne zdravotnická rada</h2>
                <ul>
                    <li>
                        Kalorie a množství vody u záznamů i komentáře k nim jsou vždy jen odhady modelů umělé
                        inteligence (od společnosti OpenAI). AI vychází jen z toho, co napíšeš nebo vyfotíš –
                        skutečnou velikost porce ani přesné složení jídla nezná. Odhady proto mohou být nepřesné
                        nebo chybné a je dobré je brát jako orientační.
                    </li>
                    <li>
                        Přibližný je i výpočet bazálního metabolismu (BMR) podle vzorce. Aktivní kalorie pocházejí
                        z tvého nastavení nebo z Apple Zdraví a jsou tak přesné jako jejich zdroj.
                    </li>
                    <li>
                        Aplikace není zdravotnický prostředek a nenahrazuje lékaře, nutričního terapeuta ani jiného
                        odborníka.
                    </li>
                    <li>
                        Máš-li zdravotní potíže, jsi těhotná nebo kojíš, léčíš se s cukrovkou, poruchou příjmu
                        potravy nebo jiným onemocněním, poraď se před změnou stravování s lékařem.
                    </li>
                </ul>

                <h2>4. Pravidla používání</h2>
                <ul>
                    <li>Aplikaci používej jen pro osobní, nekomerční účely a v souladu s právními předpisy.</li>
                    <li>
                        Nepokoušej se narušit její bezpečnost nebo provoz, obcházet omezení (např. denní limit
                        odhadů pomocí AI), zakládat účty automatizovaně ani ji zahlcovat požadavky.
                    </li>
                    <li>
                        Nevkládej protiprávní obsah ani fotografie, na kterých jsou jiní lidé bez jejich souhlasu.
                    </li>
                    <li>
                        Do textu záznamů a na fotky nevkládej své jméno, kontakty ani jiné osobní či citlivé údaje –
                        k odhadu nejsou potřeba a odešlou se k AI spolu se záznamem. Za údaje, které takto do
                        záznamu sám(a) vložíš, provozovatel neodpovídá.
                    </li>
                    <li>Při porušení pravidel může provozovatel účet omezit nebo zrušit.</li>
                </ul>

                <h2>5. Dostupnost a odpovědnost</h2>
                <ul>
                    <li>
                        Aplikace se poskytuje zdarma a „tak, jak je“. Provozovatel nezaručuje její nepřetržitou
                        dostupnost ani bezchybnost a může ji měnit, rozšiřovat nebo omezovat.
                    </li>
                    <li>Ukončení provozu oznámí provozovatel registrovaným uživatelům e-mailem alespoň 30 dní předem.</li>
                    <li>
                        Provozovatel neodpovídá za škodu vzniklou rozhodnutím učiněným na základě odhadů z aplikace,
                        v rozsahu, v jakém to právní předpisy dovolují.
                    </li>
                </ul>

                <h2 id="gdpr" class="scroll-mt-24">6. Ochrana osobních údajů</h2>
                <p>
                    Osobní údaje zpracováváme podle nařízení (EU) 2016/679 (GDPR) a zákona č. 110/2019 Sb.,
                    o zpracování osobních údajů. Správcem údajů je provozovatel (viz bod 1).
                </p>

                <h3>Jaké údaje zpracováváme a proč</h3>
                <ul>
                    <li>
                        <strong>Účet</strong> – e-mail, heslo (jen v nevratně zašifrované podobě), datum registrace
                        a udělení souhlasu s podmínkami; při přihlášení přes Google nebo Apple identifikátor účtu
                        u tohoto poskytovatele. Účel: vedení účtu a přihlášení. Právní základ: plnění smlouvy
                        (čl. 6 odst. 1 písm. b GDPR).
                    </li>
                    <li>
                        <strong>Deník</strong> – texty záznamů o jídle a pití, odhad kalorií a vody, datum.
                        <strong>Tělo a aktivita</strong> – bazální metabolismus, průměrná denní aktivita, váha (jen
                        pokud ji uložíš), aktivní kalorie. Účel: poskytování služby (deník, denní bilance, komentáře).
                        Z těchto údajů lze usuzovat na tvůj zdravotní stav, jde proto o zvláštní kategorii údajů.
                        Právní základ: tvůj výslovný souhlas udělený při registraci (čl. 9 odst. 2 písm. a GDPR).
                    </li>
                    <li>
                        <strong>Fotografie jídla</strong> – posíláme k rozpoznání do služby OpenAI, předem zmenšené
                        a bez metadat (např. polohy GPS nebo údajů o telefonu). U nás se neukládají, uloží se jen
                        výsledný text záznamu.
                    </li>
                    <li>
                        <strong>Hlasové zadávání</strong> – převod řeči na text zajišťuje tvůj prohlížeč nebo telefon
                        (např. Google v Chrome, Apple v Safari) podle podmínek jejich výrobce. My dostaneme jen
                        výsledný text.
                    </li>
                    <li>
                        <strong>Technické údaje</strong> – IP adresa a záznamy v logu serveru. Účel: zabezpečení
                        a ochrana před zneužitím (např. omezení pokusů o přihlášení). Právní základ: oprávněný zájem
                        (čl. 6 odst. 1 písm. f GDPR).
                    </li>
                </ul>

                <h3>Komu údaje předáváme</h3>
                <ul>
                    <li>
                        <strong>OpenAI</strong> (OpenAI Ireland Ltd. a OpenAI, L.L.C., USA) – k odhadu kalorií
                        a vody. Pro každý záznam zvlášť posíláme jen jeho text (nebo fotku bez metadat) a pokyn,
                        jaký komentář má AI připojit. Požadavky odcházejí z našeho serveru pod účtem provozovatele:
                        OpenAI nedostane tvůj e-mail, jméno, identifikátor účtu ani IP adresu tvého zařízení a nevidí
                        tvé ostatní záznamy, součty ani nastavení. Neví tedy, kdo si odhad přeje, ani v jakých
                        souvislostech, a souhrnné informace o tvých stravovacích návycích nedostává. Podle podmínek
                        OpenAI se data zaslaná přes API nepoužívají k trénování modelů; OpenAI je může nejvýš 30 dní
                        uchovat kvůli odhalování zneužití své služby. Předání do USA probíhá na základě rámce pro
                        ochranu osobních údajů EU–USA, případně standardních smluvních doložek.
                    </li>
                    <li><strong>Poskytovatel hostingu</strong>, na jehož serverech aplikace a databáze běží.</li>
                    <li><strong>Google nebo Apple</strong>, pokud se přes ně přihlásíš.</li>
                </ul>
                <p>
                    Údaje neprodáváme a nepoužíváme je k reklamě. Aplikace neobsahuje reklamu ani analytické
                    a sledovací nástroje třetích stran.
                </p>

                <h3>Jak dlouho údaje uchováváme</h3>
                <ul>
                    <li>Po dobu existence účtu.</li>
                    <li>
                        Po zrušení účtu nebo odvolání souhlasu údaje smažeme do 30 dnů; ze záloh zmizí při jejich
                        pravidelném přepsání.
                    </li>
                    <li>Záznamy v logu serveru uchováváme jen po dobu nezbytnou pro zajištění bezpečnosti.</li>
                </ul>

                <h3>Cookies a úložiště zařízení</h3>
                <p>
                    Nepoužíváme reklamní ani analytické cookies. V úložišti prohlížeče ukládáme jen údaje nezbytné
                    pro fungování aplikace – přihlášení (session) a zvolený vzhled.
                </p>

                <h3>Zabezpečení</h3>
                <p>
                    Komunikace s aplikací je šifrovaná (HTTPS), hesla ukládáme jen v podobě, ze které je nelze
                    zpětně získat, a ke svým datům má přístup jen přihlášený uživatel.
                </p>

                <h3>Tvoje práva</h3>
                <ul>
                    <li>získat přístup ke svým údajům a jejich kopii,</li>
                    <li>požadovat opravu nepřesných údajů,</li>
                    <li>požadovat výmaz údajů („právo být zapomenut“) nebo omezení zpracování,</li>
                    <li>získat své údaje ve strojově čitelném formátu (přenositelnost),</li>
                    <li>vznést námitku proti zpracování na základě oprávněného zájmu,</li>
                    <li>
                        kdykoli odvolat souhlas se zpracováním údajů o stravování a zdraví – bez nich ale aplikaci
                        nelze používat, odvolání proto vede ke zrušení účtu. Zpracování do odvolání zůstává
                        v souladu se zákonem,
                    </li>
                    <li>
                        podat stížnost u Úřadu pro ochranu osobních údajů (Pplk. Sochora 27, 170 00 Praha 7,
                        <a href="https://uoou.gov.cz" target="_blank" rel="noopener">uoou.gov.cz</a>).
                    </li>
                </ul>
                <p>
                    Žádosti posílej na {{ OPERATOR.email }}. Vyřídíme je bez zbytečného odkladu, nejpozději
                    do jednoho měsíce.
                </p>
                <p>
                    K odhadu kalorií používáme umělou inteligenci, nedochází však k automatizovanému rozhodování,
                    které by pro tebe mělo právní účinky nebo se tě obdobně významně dotýkalo.
                </p>

                <h2>7. Změny podmínek</h2>
                <p>
                    Podmínky můžeme měnit, např. kvůli novým funkcím nebo změně právních předpisů. O podstatných
                    změnách tě předem informujeme e-mailem nebo v aplikaci. Pokud se změnou nesouhlasíš, můžeš účet
                    zrušit.
                </p>

                <h2>8. Závěrečná ustanovení</h2>
                <p>
                    Tyto podmínky se řídí právem České republiky, zejména zákonem č. 89/2012 Sb., občanský zákoník.
                    K mimosoudnímu řešení spotřebitelských sporů je příslušná Česká obchodní inspekce
                    (<a href="https://adr.coi.cz" target="_blank" rel="noopener">adr.coi.cz</a>).
                </p>
            </article>
        </main>
    </div>
</template>

<script setup lang="ts">
// Datum účinnosti = verze podmínek; při změně textu upravit i RegistrationController::TERMS_VERSION
const EFFECTIVE_DATE = '26. 10. 2026'

// TODO: doplnit údaje provozovatele před otevřením registrace veřejnosti
const OPERATOR = {
    name: '[jméno a příjmení / název]',
    id: '[IČO]',
    address: '[adresa]',
    email: '[kontaktní e-mail]',
}

const router = useRouter()
const { isAuthenticated } = useAuth()

// Zpět odkud uživatel přišel (registrace, nastavení); při přímém odkazu na úvod
const goBack = () => {
    if (window.history.state?.back) router.back()
    else navigateTo(isAuthenticated.value ? '/app' : '/')
}
</script>
