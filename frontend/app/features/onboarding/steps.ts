// Kroky úvodního průvodce. `target` = hodnota atributu data-tour u zvýrazněného prvku.
// Krok, jehož prvek na stránce není (např. diktování v prohlížeči bez rozpoznávání řeči), se vynechá.
export type TourStep = {
    target: string
    title: string
    text: string
}

export const TOUR_STEPS: TourStep[] = [
    {
        target: 'today-card',
        title: 'Tvůj den',
        text: 'Kolik jsi dnes snědl(a) a vypil(a) a kolik je denní výdej. Štítek ukazuje deficit nebo přebytek. '
            + 'Přejetím do strany se podíváš na předchozí dny, klepnutím otevřeš detail dne.',
    },
    {
        target: 'entry-text',
        title: 'Nový záznam',
        text: 'Napiš, co jsi snědl(a) nebo vypil(a) – volně, třeba „svíčková s 5 knedlíky a pivo“. '
            + 'Kalorie a vodu odhadne AI, hodnoty pak můžeš upravit.',
    },
    {
        target: 'entry-voice',
        title: 'Diktování',
        text: 'Záznam můžeš i nadiktovat. Po zastavení nahrávání se rovnou zapíše.',
    },
    {
        target: 'entry-photo',
        title: 'Fotka jídla',
        text: 'Nebo jídlo vyfoť – AI ho rozpozná a popis doplní do pole. Před zapsáním ho můžeš upravit.',
    },
    {
        target: 'tab-calendar',
        title: 'Kalendář',
        text: 'Přehled celých měsíců – zelené dny jsou v deficitu, oranžové v přebytku.',
    },
    {
        target: 'tab-settings',
        title: 'Nastavení',
        text: 'Tady začni: vyplň bazální metabolismus (BMR) a průměrnou denní aktivitu – bez nich nejde spočítat '
            + 'deficit. Najdeš tu i propojení s Apple Zdraví.',
    },
]
