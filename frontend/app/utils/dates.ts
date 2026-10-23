// Veškerá práce s daty na jednom místě. Backend i URL používají YYYY-MM-DD (místní datum).
// Měsíce a týdny počítá date-fns s českou lokalizací – zbytek aplikace volá jen funkce odsud.
import {
    addMonths as addMonthsFns,
    eachDayOfInterval,
    endOfMonth,
    endOfWeek,
    format,
    isSameMonth,
    startOfMonth,
    startOfWeek,
} from 'date-fns'
import { cs } from 'date-fns/locale'

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

export const isToday = (d: Date, now: Date = new Date()) => isSameDay(d, now)

// ===== Měsíce (kalendář) =====

// Zkratky dnů v týdnu od pondělí – záhlaví mřížky kalendáře
export const WEEKDAYS_MONDAY_FIRST = ['Po', 'Út', 'St', 'Čt', 'Pá', 'So', 'Ne']

export const addMonths = (d: Date, months: number) => addMonthsFns(d, months)

// První a poslední den měsíce, do kterého patří `d`
export const monthRange = (d: Date) => ({ from: startOfMonth(d), to: endOfMonth(d) })

// „říjen 2026“
export const formatMonthTitle = (d: Date) => format(d, 'LLLL yyyy', { locale: cs })

// Měsíc v URL (?m=2026-10)
export const formatMonthParam = (d: Date) => format(d, 'yyyy-MM')

// '2026-10' → 1. 10. 2026; null pro neplatnou hodnotu
export const parseMonthParam = (s: unknown): Date | null => {
    const match = typeof s === 'string' ? /^(\d{4})-(\d{2})$/.exec(s) : null
    if (!match) return null

    const month = Number(match[2])
    if (month < 1 || month > 12) return null

    return new Date(Number(match[1]), month - 1, 1)
}

/**
 * Mřížka měsíce po týdnech (pondělí–neděle). Dny z vedlejších měsíců jsou null,
 * takže každý řádek má vždy 7 buněk a 1. den je pod správným dnem v týdnu.
 */
export const monthGrid = (month: Date): (Date | null)[][] => {
    const days = eachDayOfInterval({
        start: startOfWeek(startOfMonth(month), { weekStartsOn: 1 }),
        end: endOfWeek(endOfMonth(month), { weekStartsOn: 1 }),
    })

    const weeks: (Date | null)[][] = []
    for (let i = 0; i < days.length; i += 7) {
        weeks.push(days.slice(i, i + 7).map((day) => (isSameMonth(day, month) ? day : null)))
    }

    return weeks
}
