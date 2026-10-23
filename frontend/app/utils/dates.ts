// Veškerá práce s daty na jednom místě. Backend i URL používají YYYY-MM-DD (místní datum).
// Až přibude kalendář a statistiky (týdny, měsíce, rozsahy), lze vnitřek nahradit knihovnou date-fns
// s českou lokalizací – zbytek aplikace volá jen funkce odsud.

const pad = (n: number) => String(n).padStart(2, '0')

const WEEKDAYS = ['Ne', 'Po', 'Út', 'St', 'Čt', 'Pá', 'So']

// YYYY-MM-DD → Date (místní půlnoc); null pro neplatný formát nebo neexistující den (např. 2026-02-30)
export const parseIsoDate = (s: string): Date | null => {
    const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(s)
    if (!match) return null

    const [year, month, day] = [Number(match[1]), Number(match[2]), Number(match[3])]
    const date = new Date(year, month - 1, day)
    if (date.getFullYear() !== year || date.getMonth() !== month - 1 || date.getDate() !== day) return null

    return date
}

export const formatIsoDate = (d: Date) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`

// Nové datum posunuté o `days` dní (záporné = do minulosti); původní datum nemění
export const addDays = (d: Date, days: number) => {
    const result = new Date(d)
    result.setDate(result.getDate() + days)
    return result
}

const isSameDay = (a: Date, b: Date) =>
    a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate()

// Vždy s datem, např. „Út 22. 10.“ (pro titulek detailu)
export const dayTitle = (d: Date) => `${WEEKDAYS[d.getDay()]} ${d.getDate()}. ${d.getMonth() + 1}.`

// „Dnes“, „Včera“, jinak např. „Po 20. 10.“; `now` jen pro testy
export const dayLabel = (d: Date, now: Date = new Date()) => {
    if (isSameDay(d, now)) return 'Dnes'
    if (isSameDay(d, addDays(now, -1))) return 'Včera'
    return dayTitle(d)
}
